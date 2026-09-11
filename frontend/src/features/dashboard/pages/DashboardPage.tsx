import { useCallback, useEffect, useState } from "react";
import {
  Users,
  ClipboardList,
  Activity,
  Newspaper,
  Stethoscope,
  Heart,
  RefreshCw,
  Clock,
} from "lucide-react";
import {
  ResponsiveContainer,
  AreaChart,
  Area,
  XAxis,
  YAxis,
  Tooltip,
  CartesianGrid,
} from "recharts";
import { api } from "../../../lib/api";
import { Button } from "../../../components/ui/Button";
import { Card } from "../../../components/ui/Card";
import { SimpleSelect } from "../../../components/ui/SimpleSelect";
import { Skeleton } from "../../../components/ui/Skeleton";
import { StatCardSkeleton } from "../../../components/ui/StatCardSkeleton";
import { DataLoadError } from "../../../components/ui/DataLoadError";

interface DashboardStats {
  overview: {
    total_users: number;
    total_assessments: number;
    total_diseases: number;
    total_symptoms: number;
    total_articles: number;
    total_first_aids: number;
  };
  assessment_today: {
    total: number;
    completed: number;
    ongoing: number;
    abandoned: number;
  };
  top_diseases: {
    disease_id: string;
    disease_name: string;
    assessment_count: number;
    view_count: number;
    bookmark_count: number;
  }[];
  top_symptoms: {
    symptom_id: string;
    symptom_name: string;
    total: number;
    assessment_count: number;
    daily_record_count: number;
    follow_up_count: number;
  }[];
  recent_assessments: {
    assessment_id: number;
    symptom_name: string | null;
    assessment_status: string;
    urgency_level: string | null;
    created_at: string;
  }[];
  new_users_trend: { date: string; total: number }[];
  assessment_trend: { date: string; total: number }[];
}

// จับคู่การ์ดภาพรวมกับสีเฉพาะของแต่ละหมวด เพื่อให้สแกนหาข้อมูลได้เร็วขึ้น
const OVERVIEW_ACCENTS = [
  { bg: "#EEF2FF", fg: "#4F46E5" }, // ผู้ใช้งาน — indigo
  { bg: "#F0F5FF", fg: "var(--color-primary)" }, // การประเมิน — primary
  { bg: "#FEF2F2", fg: "#E11D48" }, // โรค — rose
  { bg: "#FFFBEB", fg: "#D97706" }, // อาการ — amber
  { bg: "#F0FDFA", fg: "#0D9488" }, // บทความ — teal
  { bg: "#ECFDF5", fg: "#059669" }, // ปฐมพยาบาล — emerald
];

const STATUS_LABELS: Record<string, string> = {
  P: "กำลังประเมิน",
  C: "เสร็จสิ้น",
  A: "ออกจากการประเมิน",
};

function formatRelativeThaiDate(iso: string): string {
  const date = new Date(iso);
  return date.toLocaleString("th-TH", {
    day: "numeric",
    month: "short",
    year: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  });
}

function formatShortThaiDate(iso: string): string {
  const date = new Date(iso);
  return date.toLocaleDateString("th-TH", {
    day: "numeric",
    month: "short",
    year: "numeric",
  });
}

function formatAxisThaiDate(iso: string): string {
  return new Date(iso).toLocaleDateString("th-TH", {
    day: "numeric",
    month: "short",
  });
}

function DashboardSkeleton() {
  return (
    <div className="space-y-6" aria-label="กำลังโหลดข้อมูลภาพรวมระบบ" role="status">
      <div className="flex items-center justify-between">
        <Skeleton className="h-7 w-36 bg-[var(--color-border)]" />
        <Skeleton className="h-9 w-24 bg-[var(--color-border)]" />
      </div>
      <div className="grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-6">
        {Array.from({ length: 6 }, (_, index) => (
          <StatCardSkeleton key={index} />
        ))}
      </div>
      <div className="grid grid-cols-1 gap-4 lg:grid-cols-3">
        {Array.from({ length: 3 }, (_, index) => (
          <Card key={index} className="h-80 gap-5 rounded-2xl p-5">
            <Skeleton className="h-5 w-40" />
            <Skeleton className="h-4 w-56 max-w-full" />
            <Skeleton className="min-h-0 flex-1 rounded-xl" />
          </Card>
        ))}
      </div>
      <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
        {Array.from({ length: 2 }, (_, index) => (
          <Card key={index} className="gap-4 rounded-2xl p-5">
            <Skeleton className="h-5 w-44" />
            {Array.from({ length: 5 }, (_, row) => (
              <div key={row} className="space-y-2">
                <Skeleton className="h-4 w-2/3" />
                <Skeleton className="h-2 w-full rounded-full" />
              </div>
            ))}
          </Card>
        ))}
      </div>
      <span className="sr-only">กำลังโหลดข้อมูลภาพรวมระบบ...</span>
    </div>
  );
}

function CustomTrendTooltip({
  active,
  payload,
  label,
}: {
  active?: boolean;
  payload?: { value: number; dataKey?: string }[];
  label?: string;
}) {
  if (!active || !payload?.length) return null;
  return (
    <div className="rounded-lg border border-[var(--color-border)] bg-white px-3 py-2 shadow-sm">
      <p className="text-xs text-[var(--color-text-secondary)]">
        {label ? (/^\d{4}-\d{2}-\d{2}$/.test(label) ? formatShortThaiDate(label) : label) : ""}
      </p>
      {payload.map((item) => (
        <p key={item.dataKey} className="text-sm font-semibold text-[var(--color-text-primary)]">
          {item.dataKey === "assessments" ? "การประเมิน" : "ผู้ใช้ใหม่"} {item.value}{" "}
          {item.dataKey === "assessments" ? "ครั้ง" : "คน"}
        </p>
      ))}
    </div>
  );
}

export function DashboardPage() {
  const [stats, setStats] = useState<DashboardStats | null>(null);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [lastUpdated, setLastUpdated] = useState<Date | null>(null);
  const [diseaseSort, setDiseaseSort] = useState<
    "assessment_count" | "view_count" | "bookmark_count"
  >("assessment_count");

  const loadDashboard = useCallback(async (isRefresh = false) => {
    if (isRefresh) {
      setRefreshing(true);
    } else {
      setLoading(true);
    }
    setError(null);
    try {
      const { data } = await api.get<DashboardStats>("/admin/dashboard/stats");
      setStats(data);
      setLastUpdated(new Date());
    } catch {
      setStats(null);
      setError("ไม่สามารถโหลดข้อมูลภาพรวมระบบได้ กรุณาลองใหม่");
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }, []);

  useEffect(() => {
    void loadDashboard();
  }, [loadDashboard]);

  if (loading) return <DashboardSkeleton />;
  if (error || !stats) {
    return (
      <Card className="p-0">
        <DataLoadError
          title="โหลดข้อมูลภาพรวมระบบไม่สำเร็จ"
          description={error ?? "ไม่พบข้อมูลภาพรวมระบบ"}
          onRetry={() => void loadDashboard()}
        />
      </Card>
    );
  }

  const overviewCards = [
    { label: "ผู้ใช้งานทั้งหมด", value: stats.overview.total_users, icon: Users },
    { label: "การประเมินทั้งหมด", value: stats.overview.total_assessments, icon: ClipboardList },
    { label: "โรคที่เปิดใช้งาน", value: stats.overview.total_diseases, icon: Stethoscope },
    { label: "อาการที่เปิดใช้งาน", value: stats.overview.total_symptoms, icon: Activity },
    { label: "บทความ", value: stats.overview.total_articles, icon: Newspaper },
    { label: "ข้อมูลปฐมพยาบาล", value: stats.overview.total_first_aids, icon: Heart },
  ];

  const ongoingToday = stats.assessment_today.ongoing;
  const abandonedToday = stats.assessment_today.abandoned;
  const trendByDate = new Map<string, { date: string; users: number; assessments: number }>();
  for (let offset = 29; offset >= 0; offset -= 1) {
    const date = new Date();
    date.setHours(0, 0, 0, 0);
    date.setDate(date.getDate() - offset);
    const key = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, "0")}-${String(date.getDate()).padStart(2, "0")}`;
    trendByDate.set(key, { date: key, users: 0, assessments: 0 });
  }
  stats.new_users_trend.forEach((item) => {
    const point = trendByDate.get(item.date);
    if (point) point.users = Number(item.total);
  });
  stats.assessment_trend.forEach((item) => {
    const point = trendByDate.get(item.date);
    if (point) point.assessments = Number(item.total);
  });
  const activityTrend = Array.from(trendByDate.values());
  const dailyTickDates = [0, 7, 14, 21, activityTrend.length - 1]
    .filter((index, position, indexes) => index >= 0 && indexes.indexOf(index) === position)
    .map((index) => activityTrend[index].date);
  const newUsers30Days = activityTrend.reduce((sum, item) => sum + item.users, 0);
  const assessments30Days = activityTrend.reduce((sum, item) => sum + item.assessments, 0);
  const rankedDiseases = [...stats.top_diseases]
    .filter((item) => item[diseaseSort] > 0)
    .sort((a, b) => b[diseaseSort] - a[diseaseSort])
    .slice(0, 5);

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex flex-wrap items-end justify-between gap-3">
        <div>
          <h1 className="text-xl font-semibold text-[var(--color-text-primary)]">ภาพรวมระบบ</h1>
          {/* <p className="mt-1 text-sm text-[var(--color-text-secondary)]">
            ภาพรวมระบบประเมินอาการเบื้องต้น
          </p> */}
        </div>
        <div className="flex items-center gap-3">
          {lastUpdated && (
            <span className="flex items-center gap-1.5 text-xs text-[var(--color-text-secondary)]">
              <Clock className="h-3.5 w-3.5" />
              อัปเดตล่าสุด {lastUpdated.toLocaleTimeString("th-TH", { hour: "2-digit", minute: "2-digit" })}
            </span>
          )}
          <Button variant="outline" onClick={() => loadDashboard(true)} disabled={refreshing}>
            <RefreshCw className={`h-4 w-4 ${refreshing ? "animate-spin" : ""}`} />
            รีเฟรช
          </Button>
        </div>
      </div>

      {/* Overview cards */}
      <div className="grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-6">
        {overviewCards.map(({ label, value, icon: Icon }, i) => {
          const accent = OVERVIEW_ACCENTS[i % OVERVIEW_ACCENTS.length];
          return (
            <div
              key={label}
              className="group rounded-2xl border border-[var(--color-border)] bg-white p-4 transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md"
            >
              <div
                className="mb-3 flex h-9 w-9 items-center justify-center rounded-lg transition-transform duration-200 group-hover:scale-105"
                style={{ backgroundColor: accent.bg }}
              >
                <Icon className="h-4.5 w-4.5" style={{ color: accent.fg }} />
              </div>
              <p className="text-2xl font-semibold tabular-nums text-[var(--color-text-primary)]">
                {value.toLocaleString("th-TH")}
              </p>
              <p className="mt-0.5 text-xs text-[var(--color-text-secondary)]">{label}</p>
            </div>
          );
        })}
      </div>

      <div className="grid grid-cols-1 items-stretch gap-4 lg:grid-cols-3">
        {/* Assessment today */}
        <div className="flex flex-col rounded-2xl border border-[var(--color-border)] bg-white p-5">
          <p className="text-sm font-medium text-[var(--color-text-primary)]">การประเมินวันนี้</p>

          <div className="mt-4 flex flex-1 items-center gap-5">
            {/* Completion distribution */}
            <div className="relative h-20 w-20 shrink-0">
              <svg
                viewBox="0 0 80 80"
                className="h-20 w-20 -rotate-90"
                role="img"
                aria-label={`การประเมินวันนี้ทั้งหมด ${stats.assessment_today.total} ครั้ง เสร็จสิ้น ${stats.assessment_today.completed} ครั้ง กำลังทำ ${ongoingToday} ครั้ง ออกจากการประเมิน ${abandonedToday} ครั้ง`}
              >
                <circle cx="40" cy="40" r="34" fill="none" stroke="var(--color-surface)" strokeWidth="8" />
                {stats.assessment_today.total > 0 && (
                  <>
                    <circle
                      cx="40"
                      cy="40"
                      r="34"
                      fill="none"
                      stroke="var(--color-success)"
                      strokeWidth="8"
                      strokeDasharray={`${2 * Math.PI * 34 * (stats.assessment_today.completed / stats.assessment_today.total)} ${2 * Math.PI * 34}`}
                      className="transition-all duration-700 ease-out"
                    />
                    <circle
                      cx="40"
                      cy="40"
                      r="34"
                      fill="none"
                      stroke="#94A3B8"
                      strokeWidth="8"
                      strokeDasharray={`${2 * Math.PI * 34 * (abandonedToday / stats.assessment_today.total)} ${2 * Math.PI * 34}`}
                      strokeDashoffset={-2 * Math.PI * 34 * ((stats.assessment_today.completed + ongoingToday) / stats.assessment_today.total)}
                      className="transition-all duration-700 ease-out"
                    />
                    <circle
                      cx="40"
                      cy="40"
                      r="34"
                      fill="none"
                      stroke="#D97706"
                      strokeWidth="8"
                      strokeDasharray={`${2 * Math.PI * 34 * (ongoingToday / stats.assessment_today.total)} ${2 * Math.PI * 34}`}
                      strokeDashoffset={-2 * Math.PI * 34 * (stats.assessment_today.completed / stats.assessment_today.total)}
                      className="transition-all duration-700 ease-out"
                    />
                  </>
                )}
              </svg>
              <div className="absolute inset-0 flex flex-col items-center justify-center">
                <span className="text-lg font-semibold tabular-nums text-[var(--color-text-primary)]">
                  {stats.assessment_today.total}
                </span>
                <span className="text-[10px] text-[var(--color-text-secondary)]">ทั้งหมด</span>
              </div>
            </div>

            <div className="flex flex-1 flex-col gap-2">
              <div className="flex items-baseline justify-between">
                <span className="flex items-center gap-1.5 text-xs text-[var(--color-text-secondary)]">
                  <span className="h-2.5 w-2.5 rounded-full bg-[var(--color-success)]" />
                  เสร็จสิ้น
                </span>
                <span className="text-lg font-semibold text-[var(--color-success)]">
                  {stats.assessment_today.completed}
                </span>
              </div>
              <div className="flex items-baseline justify-between">
                <span className="flex items-center gap-1.5 text-xs text-[var(--color-text-secondary)]">
                  <span className="h-2.5 w-2.5 rounded-full bg-amber-600" />
                  กำลังทำ
                </span>
                <span className="text-lg font-semibold text-amber-600">
                  {ongoingToday}
                </span>
              </div>
              <div className="flex items-baseline justify-between">
                <span className="flex items-center gap-1.5 text-xs text-[var(--color-text-secondary)]">
                  <span className="h-2.5 w-2.5 rounded-full bg-slate-400" />
                  ออกจากการประเมิน
                </span>
                <span className="text-lg font-semibold text-slate-500">
                  {abandonedToday}
                </span>
              </div>
            </div>
          </div>
        </div>

        {/* Assessment trend */}
        <div className="rounded-2xl border border-[var(--color-border)] bg-white p-5">
          <div className="mb-4 flex items-center justify-between">
            <div>
              <p className="text-sm font-medium text-[var(--color-text-primary)]">
                การประเมินอาการรายวัน (30 วันล่าสุด)
              </p>
              <p className="mt-1 text-xs text-[var(--color-text-secondary)]">
                ข้อมูลแต่ละจุดแทนจำนวนการประเมินใน 1 วัน
              </p>
            </div>
            <span className="text-xs text-[var(--color-text-secondary)]">
              รวม {assessments30Days.toLocaleString("th-TH")} ครั้ง
            </span>
          </div>
          <ResponsiveContainer width="100%" height={250}>
            <AreaChart data={activityTrend} margin={{ left: 8, right: 16, bottom: 8 }}>
              <defs>
                <linearGradient id="assessmentTrend" x1="0" y1="0" x2="0" y2="1">
                  <stop offset="5%" stopColor="var(--color-primary-mid)" stopOpacity={0.35} />
                  <stop offset="95%" stopColor="var(--color-primary-mid)" stopOpacity={0} />
                </linearGradient>
              </defs>
              <CartesianGrid strokeDasharray="3 3" stroke="var(--color-border)" vertical={false} />
              <XAxis
                dataKey="date"
                ticks={dailyTickDates}
                tickFormatter={formatAxisThaiDate}
                tick={{ fontSize: 11, fill: "#6B7280" }}
                axisLine={false}
                tickLine={false}
                padding={{ left: 8, right: 8 }}
                height={44}
                label={{
                  value: "วันที่บันทึก",
                  position: "insideBottom",
                  offset: -4,
                  style: { fontSize: 11, fill: "#6B7280" },
                }}
              />
              <YAxis
                tick={{ fontSize: 11, fill: "#6B7280" }}
                axisLine={false}
                tickLine={false}
                allowDecimals={false}
                width={58}
                label={{
                  value: "จำนวน (ครั้ง)",
                  angle: -90,
                  position: "insideLeft",
                  style: { fontSize: 11, fill: "#6B7280", textAnchor: "middle" },
                }}
              />
              <Tooltip content={<CustomTrendTooltip />} />
              <Area
                type="monotone"
                dataKey="assessments"
                stroke="var(--color-primary-mid)"
                strokeWidth={2}
                fill="url(#assessmentTrend)"
                activeDot={{ r: 5 }}
              />
            </AreaChart>
          </ResponsiveContainer>
        </div>

        {/* New users trend */}
        <div className="rounded-2xl border border-[var(--color-border)] bg-white p-5">
          <div className="mb-4 flex items-center justify-between">
            <div>
              <p className="text-sm font-medium text-[var(--color-text-primary)]">
                ผู้ใช้ใหม่รายวัน (30 วันล่าสุด)
              </p>
              <p className="mt-1 text-xs text-[var(--color-text-secondary)]">
                ข้อมูลแต่ละจุดแทนจำนวนผู้สมัครใหม่ใน 1 วัน
              </p>
            </div>
            <span className="text-xs text-[var(--color-text-secondary)]">
              รวม {newUsers30Days.toLocaleString("th-TH")} คน
            </span>
          </div>
          <ResponsiveContainer width="100%" height={250}>
            <AreaChart data={activityTrend} margin={{ left: 8, right: 16, bottom: 8 }}>
              <defs>
                <linearGradient id="userTrend" x1="0" y1="0" x2="0" y2="1">
                  <stop offset="5%" stopColor="#10B981" stopOpacity={0.25} />
                  <stop offset="95%" stopColor="#10B981" stopOpacity={0} />
                </linearGradient>
              </defs>
              <CartesianGrid strokeDasharray="3 3" stroke="var(--color-border)" vertical={false} />
              <XAxis
                dataKey="date"
                ticks={dailyTickDates}
                tickFormatter={formatAxisThaiDate}
                tick={{ fontSize: 11, fill: "#6B7280" }}
                axisLine={false}
                tickLine={false}
                padding={{ left: 8, right: 8 }}
                height={44}
                label={{
                  value: "วันที่สมัคร",
                  position: "insideBottom",
                  offset: -4,
                  style: { fontSize: 11, fill: "#6B7280" },
                }}
              />
              <YAxis
                tick={{ fontSize: 11, fill: "#6B7280" }}
                axisLine={false}
                tickLine={false}
                allowDecimals={false}
                width={58}
                label={{
                  value: "จำนวน (คน)",
                  angle: -90,
                  position: "insideLeft",
                  style: { fontSize: 11, fill: "#6B7280", textAnchor: "middle" },
                }}
              />
              <Tooltip content={<CustomTrendTooltip />} />
              <Area
                type="monotone"
                dataKey="users"
                stroke="#10B981"
                strokeWidth={2}
                fill="url(#userTrend)"
                activeDot={{ r: 5 }}
              />
            </AreaChart>
          </ResponsiveContainer>
        </div>
      </div>

      <div className="grid grid-cols-1 items-stretch gap-4 md:grid-cols-2">

        {/* Top diseases */}
        <div className="min-w-0 rounded-2xl border border-[var(--color-border)] bg-white p-5">
          <div className="mb-4 flex flex-wrap items-start justify-between gap-3">
            <div>
              <p className="text-sm font-medium text-[var(--color-text-primary)]">โรคที่ได้รับความสนใจ</p>
              <p className="mt-1 text-xs text-[var(--color-text-secondary)]">
                แสดง {rankedDiseases.length} อันดับแรก โดยไม่รวมตัวเลขต่างประเภทเข้าด้วยกัน
              </p>
            </div>
            <SimpleSelect
              label="เรียงตาม"
              value={diseaseSort}
              onChange={(value) => setDiseaseSort(value as typeof diseaseSort)}
              options={[
                { value: "assessment_count", label: "ผลประเมิน" },
                { value: "view_count", label: "เปิดดู" },
                { value: "bookmark_count", label: "บันทึก" },
              ]}
              className="w-36"
            />
          </div>
          {rankedDiseases.length === 0 ? (
            <p className="text-sm text-[var(--color-text-secondary)]">ยังไม่มีข้อมูล</p>
          ) : (
            <ul className="space-y-3.5">
              {rankedDiseases.map((d, i) => {
                const maxTotal = rankedDiseases[0]?.[diseaseSort] || 1;
                const selectedTotal = d[diseaseSort];
                const barPct = selectedTotal === 0 ? 0 : Math.max((selectedTotal / maxTotal) * 100, 8);
                return (
                  <li key={d.disease_id}>
                    <div className="flex items-center justify-between text-sm">
                      <span className="flex items-center gap-2 truncate text-[var(--color-text-primary)]">
                        <span className="text-xs text-[var(--color-text-secondary)]">{i + 1}.</span>
                        <span className="truncate">{d.disease_name ?? d.disease_id}</span>
                      </span>
                      <span className="shrink-0 pl-2 font-medium text-[var(--color-primary)]">
                        {selectedTotal.toLocaleString("th-TH")}
                      </span>
                    </div>
                    <div className="my-1 flex flex-wrap gap-x-3 gap-y-1 text-xs text-[var(--color-text-secondary)]">
                      <span className="flex items-center gap-1">
                        <span className="h-2 w-2 rounded-full bg-[var(--color-primary)]" />
                        ผลประเมิน {d.assessment_count.toLocaleString("th-TH")}
                      </span>
                      <span className="flex items-center gap-1">
                        <span className="h-2 w-2 rounded-full bg-emerald-500" />
                        เปิดดู {d.view_count.toLocaleString("th-TH")}
                      </span>
                      <span className="flex items-center gap-1">
                        <span className="h-2 w-2 rounded-full bg-amber-400" />
                        บันทึก {d.bookmark_count.toLocaleString("th-TH")}
                      </span>
                    </div>
                    <div className="h-1.5 w-full overflow-hidden rounded-full bg-[var(--color-surface)]">
                      <div
                        className="h-full rounded-full transition-all duration-500 ease-out"
                        style={{ width: `${barPct}%`, backgroundColor: "var(--color-primary)" }}
                      />
                    </div>
                  </li>
                );
              })}
            </ul>
          )}
        </div>

        {/* Top symptoms */}
        <div className="min-w-0 rounded-2xl border border-[var(--color-border)] bg-white p-5">
          <div className="mb-4">
            <p className="text-sm font-medium text-[var(--color-text-primary)]">อาการที่พบบ่อย</p>
            <p className="mt-1 text-xs text-[var(--color-text-secondary)]">
              แสดง {Math.min(stats.top_symptoms.length, 5)} อันดับแรก รวมจากทุกแหล่งข้อมูล
            </p>
          </div>
          {stats.top_symptoms.length === 0 ? (
            <p className="text-sm text-[var(--color-text-secondary)]">ยังไม่มีข้อมูล</p>
          ) : (
            <ul className="space-y-3.5">
              {stats.top_symptoms.slice(0, 5).map((symptom, index) => {
                const maxTotal = stats.top_symptoms[0]?.total || 1;
                const barPct = Math.max((symptom.total / maxTotal) * 100, 8);
                return (
                  <li key={symptom.symptom_id}>
                    <div className="flex items-center justify-between text-sm">
                      <span className="flex min-w-0 items-center gap-2 text-[var(--color-text-primary)]">
                        <span className="text-xs text-[var(--color-text-secondary)]">{index + 1}.</span>
                        <span className="truncate">{symptom.symptom_name ?? symptom.symptom_id}</span>
                      </span>
                      <span className="shrink-0 pl-2 font-medium text-[#0D9488]">{symptom.total} ครั้ง</span>
                    </div>
                    <div className="my-1 flex flex-wrap gap-x-3 gap-y-1 text-xs text-[var(--color-text-secondary)]">
                      {symptom.assessment_count > 0 && (
                        <span className="flex items-center gap-1">
                          <span className="h-2 w-2 rounded-full bg-[var(--color-primary)]" />
                          ประเมิน {symptom.assessment_count}
                        </span>
                      )}
                      {symptom.daily_record_count > 0 && (
                        <span className="flex items-center gap-1">
                          <span className="h-2 w-2 rounded-full bg-emerald-500" />
                          บันทึกสุขภาพ {symptom.daily_record_count}
                        </span>
                      )}
                      {symptom.follow_up_count > 0 && (
                        <span className="flex items-center gap-1">
                          <span className="h-2 w-2 rounded-full bg-amber-400" />
                          ติดตาม {symptom.follow_up_count}
                        </span>
                      )}
                    </div>
                    <div className="h-1.5 w-full overflow-hidden rounded-full bg-[var(--color-surface)]">
                      <div
                        className="h-full rounded-full transition-all duration-500 ease-out"
                        style={{ width: `${barPct}%`, backgroundColor: "#0D9488" }}
                      />
                    </div>
                  </li>
                );
              })}
            </ul>
          )}
        </div>
      </div>

      {/* Recent assessments */}
      <div className="rounded-2xl border border-[var(--color-border)] bg-white p-5">
        <div className="mb-4 flex flex-wrap items-baseline justify-between gap-2">
          <p className="text-sm font-medium text-[var(--color-text-primary)]">
            การประเมินล่าสุด
          </p>
          <p className="text-xs text-[var(--color-text-secondary)]">
            แสดง {stats.recent_assessments.length.toLocaleString("th-TH")} รายการล่าสุด
          </p>
        </div>
        {stats.recent_assessments.length === 0 ? (
          <p className="text-sm text-[var(--color-text-secondary)]">ยังไม่มีข้อมูล</p>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-[var(--color-border)] text-left text-xs text-[var(--color-text-secondary)]">
                  <th className="pb-2 pr-4 font-medium">อาการ</th>
                  <th className="pb-2 pr-4 font-medium">สถานะ</th>
                  <th className="pb-2 pr-4 font-medium">ระดับผล</th>
                  <th className="pb-2 font-medium">เวลา</th>
                </tr>
              </thead>
              <tbody>
                {stats.recent_assessments.map((a) => (
                  <tr
                    key={a.assessment_id}
                    className="border-b border-[var(--color-border)] last:border-0 hover:bg-[var(--color-surface)]"
                  >
                    <td className="py-2.5 pr-4 text-[var(--color-text-primary)]">
                      {a.symptom_name ?? "—"}
                    </td>
                    <td className="py-2.5 pr-4">
                      <span
                        className={`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ${
                          a.assessment_status === "C"
                            ? "bg-[var(--color-primary-light)] text-[var(--color-primary)]"
                            : a.assessment_status === "A"
                              ? "bg-slate-100 text-slate-600"
                              : "bg-amber-50 text-amber-700"
                        }`}
                      >
                        {STATUS_LABELS[a.assessment_status] ?? a.assessment_status}
                      </span>
                    </td>
                    <td className="py-2.5 pr-4">
                      {a.urgency_level ? (
                        <span className="inline-flex rounded-full bg-[var(--color-surface)] px-2 py-0.5 text-xs font-medium text-[var(--color-text-secondary)]">
                          ระดับ {a.urgency_level}
                        </span>
                      ) : (
                        <span className="text-[var(--color-text-secondary)]">—</span>
                      )}
                    </td>
                    <td className="py-2.5 text-[var(--color-text-secondary)]">
                      {formatRelativeThaiDate(a.created_at)}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>
    </div>
  );
}
