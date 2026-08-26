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
  TrendingUp,
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
import { Spinner } from "../../../components/ui/Spinner";
import { Button } from "../../../components/ui/Button";
import { Card } from "../../../components/ui/Card";
import { URGENCY_LEVELS, type UrgencyCode } from "../../../lib/constants";

interface DashboardStats {
  overview: {
    total_users: number;
    total_assessments: number;
    total_diseases: number;
    total_symptoms: number;
    total_articles: number;
    total_first_aids: number;
  };
  assessment_today: { total: number; completed: number };
  urgency_summary: Record<UrgencyCode, number>;
  top_diseases: { disease_id: string; disease_name: string; total: number }[];
  top_symptoms: { symptom_id: string; symptom_name: string; total: number }[];
  recent_assessments: {
    assessment_id: number;
    symptom_name: string | null;
    assessment_status: string;
    urgency_level: UrgencyCode | null;
    created_at: string;
  }[];
  new_users_trend: { date: string; total: number }[];
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

function CustomTrendTooltip({
  active,
  payload,
  label,
}: {
  active?: boolean;
  payload?: { value: number }[];
  label?: string;
}) {
  if (!active || !payload?.length) return null;
  return (
    <div className="rounded-lg border border-[var(--color-border)] bg-white px-3 py-2 shadow-sm">
      <p className="text-xs text-[var(--color-text-secondary)]">
        {label ? formatShortThaiDate(label) : ""}
      </p>
      <p className="text-sm font-semibold text-[var(--color-text-primary)]">
        +{payload[0].value} คนใหม่
      </p>
    </div>
  );
}

export function DashboardPage() {
  const [stats, setStats] = useState<DashboardStats | null>(null);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [lastUpdated, setLastUpdated] = useState<Date | null>(null);

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

  if (loading) return <Spinner fullscreen label="กำลังโหลดข้อมูลภาพรวมระบบ..." />;
  if (error || !stats) {
    return (
      <Card className="mx-auto max-w-lg items-center p-8 text-center">
        <p className="text-sm text-[var(--color-danger)]">
          {error ?? "ไม่พบข้อมูลภาพรวมระบบ"}
        </p>
        <Button className="mt-4" onClick={() => loadDashboard()}>
          <RefreshCw className="h-4 w-4" />
          ลองใหม่
        </Button>
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

  const totalUrgency = Object.values(stats.urgency_summary).reduce((a, b) => a + b, 0) || 1;
  const completionRate = stats.assessment_today.total
    ? Math.round((stats.assessment_today.completed / stats.assessment_today.total) * 100)
    : 0;

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

      <div className="grid grid-cols-1 gap-4 lg:grid-cols-3">
        {/* Assessment today */}
        <div className="rounded-2xl border border-[var(--color-border)] bg-white p-5">
          <p className="text-sm font-medium text-[var(--color-text-primary)]">การประเมินวันนี้</p>

          <div className="mt-4 flex items-center gap-5">
            {/* Progress ring */}
            <div className="relative h-20 w-20 shrink-0">
              <svg viewBox="0 0 80 80" className="h-20 w-20 -rotate-90">
                <circle cx="40" cy="40" r="34" fill="none" stroke="var(--color-surface)" strokeWidth="8" />
                <circle
                  cx="40"
                  cy="40"
                  r="34"
                  fill="none"
                  stroke="var(--color-primary)"
                  strokeWidth="8"
                  strokeLinecap="round"
                  strokeDasharray={2 * Math.PI * 34}
                  strokeDashoffset={2 * Math.PI * 34 * (1 - completionRate / 100)}
                  className="transition-all duration-700 ease-out"
                />
              </svg>
              <div className="absolute inset-0 flex items-center justify-center">
                <span className="text-sm font-semibold text-[var(--color-text-primary)]">
                  {completionRate}%
                </span>
              </div>
            </div>

            <div className="flex flex-1 flex-col gap-2">
              <div className="flex items-baseline justify-between">
                <span className="text-xs text-[var(--color-text-secondary)]">ทั้งหมด</span>
                <span className="text-lg font-semibold text-[var(--color-text-primary)]">
                  {stats.assessment_today.total}
                </span>
              </div>
              <div className="flex items-baseline justify-between">
                <span className="text-xs text-[var(--color-text-secondary)]">เสร็จสิ้น</span>
                <span className="text-lg font-semibold text-[var(--color-success)]">
                  {stats.assessment_today.completed}
                </span>
              </div>
            </div>
          </div>
        </div>

        {/* Urgency triage bar — signature element */}
        <div className="rounded-2xl border border-[var(--color-border)] bg-white p-5 lg:col-span-2">
          <p className="mb-4 text-sm font-medium text-[var(--color-text-primary)]">
            สรุประดับความเร่งด่วน
          </p>
          <div className="flex h-3 w-full overflow-hidden rounded-full bg-[var(--color-surface)]">
            {(Object.keys(URGENCY_LEVELS) as UrgencyCode[]).map((code) => {
              const count = stats.urgency_summary[code] ?? 0;
              const pct = (count / totalUrgency) * 100;
              return (
                <div
                  key={code}
                  className="transition-all duration-500 ease-out first:rounded-l-full last:rounded-r-full"
                  style={{ width: `${pct}%`, backgroundColor: URGENCY_LEVELS[code].hex }}
                  title={`${URGENCY_LEVELS[code].short}: ${count}`}
                />
              );
            })}
          </div>
          <div className="mt-4 grid grid-cols-5 gap-2">
            {(Object.keys(URGENCY_LEVELS) as UrgencyCode[]).map((code) => {
              const count = stats.urgency_summary[code] ?? 0;
              const pct = Math.round((count / totalUrgency) * 100);
              return (
                <div key={code} className="flex flex-col gap-1 rounded-lg bg-[var(--color-surface)] px-2 py-1.5">
                  <div className="flex items-center gap-1.5">
                    <span
                      className="h-2 w-2 shrink-0 rounded-full"
                      style={{ backgroundColor: URGENCY_LEVELS[code].hex }}
                    />
                    <span className="truncate text-xs text-[var(--color-text-secondary)]">
                      {URGENCY_LEVELS[code].short}
                    </span>
                  </div>
                  <span className="text-sm font-semibold text-[var(--color-text-primary)]">
                    {count} <span className="text-xs font-normal text-[var(--color-text-secondary)]">({pct}%)</span>
                  </span>
                </div>
              );
            })}
          </div>
        </div>
      </div>

      <div className="grid grid-cols-1 gap-4 lg:grid-cols-3">
        {/* New users trend */}
        <div className="rounded-2xl border border-[var(--color-border)] bg-white p-5 lg:col-span-2">
          <div className="mb-4 flex items-center justify-between">
            <p className="text-sm font-medium text-[var(--color-text-primary)]">
              ผู้ใช้งานใหม่ (30 วันล่าสุด)
            </p>
            <span className="flex items-center gap-1 text-xs text-[var(--color-text-secondary)]">
              <TrendingUp className="h-3.5 w-3.5" />
              รวม {stats.new_users_trend.reduce((a, b) => a + b.total, 0)} คน
            </span>
          </div>
          <ResponsiveContainer width="100%" height={220}>
            <AreaChart data={stats.new_users_trend}>
              <defs>
                <linearGradient id="userTrend" x1="0" y1="0" x2="0" y2="1">
                  <stop offset="5%" stopColor="var(--color-primary-mid)" stopOpacity={0.35} />
                  <stop offset="95%" stopColor="var(--color-primary-mid)" stopOpacity={0} />
                </linearGradient>
              </defs>
              <CartesianGrid strokeDasharray="3 3" stroke="var(--color-border)" vertical={false} />
              <XAxis
                dataKey="date"
                tickFormatter={formatShortThaiDate}
                tick={{ fontSize: 11, fill: "#6B7280" }}
                axisLine={false}
                tickLine={false}
              />
              <YAxis tick={{ fontSize: 11, fill: "#6B7280" }} axisLine={false} tickLine={false} allowDecimals={false} />
              <Tooltip content={<CustomTrendTooltip />} />
              <Area
                type="monotone"
                dataKey="total"
                stroke="var(--color-primary-mid)"
                strokeWidth={2}
                fill="url(#userTrend)"
                activeDot={{ r: 5 }}
              />
            </AreaChart>
          </ResponsiveContainer>
        </div>

        {/* Top diseases */}
        <div className="rounded-2xl border border-[var(--color-border)] bg-white p-5">
          <p className="mb-4 text-sm font-medium text-[var(--color-text-primary)]">
            โรคที่พบบ่อย
          </p>
          {stats.top_diseases.length === 0 ? (
            <p className="text-sm text-[var(--color-text-secondary)]">ยังไม่มีข้อมูล</p>
          ) : (
            <ul className="space-y-3">
              {stats.top_diseases.slice(0, 6).map((d, i) => {
                const maxTotal = stats.top_diseases[0]?.total || 1;
                const barPct = Math.max((d.total / maxTotal) * 100, 8);
                return (
                  <li key={d.disease_id}>
                    <div className="mb-1 flex items-center justify-between text-sm">
                      <span className="flex items-center gap-2 truncate text-[var(--color-text-primary)]">
                        <span className="text-xs text-[var(--color-text-secondary)]">{i + 1}.</span>
                        <span className="truncate">{d.disease_name ?? d.disease_id}</span>
                      </span>
                      <span className="shrink-0 pl-2 font-medium text-[var(--color-primary)]">{d.total}</span>
                    </div>
                    <div className="h-1.5 w-full overflow-hidden rounded-full bg-[var(--color-surface)]">
                      <div
                        className="h-full rounded-full bg-[var(--color-primary)] transition-all duration-500 ease-out"
                        style={{ width: `${barPct}%` }}
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
        <p className="mb-4 text-sm font-medium text-[var(--color-text-primary)]">
          การประเมินล่าสุด
        </p>
        {stats.recent_assessments.length === 0 ? (
          <p className="text-sm text-[var(--color-text-secondary)]">ยังไม่มีข้อมูล</p>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-[var(--color-border)] text-left text-xs text-[var(--color-text-secondary)]">
                  <th className="pb-2 pr-4 font-medium">อาการ</th>
                  <th className="pb-2 pr-4 font-medium">สถานะ</th>
                  <th className="pb-2 pr-4 font-medium">ความเร่งด่วน</th>
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
                            : "bg-[var(--color-surface)] text-[var(--color-text-secondary)]"
                        }`}
                      >
                        {STATUS_LABELS[a.assessment_status] ?? a.assessment_status}
                      </span>
                    </td>
                    <td className="py-2.5 pr-4">
                      {a.urgency_level ? (
                        <span className="inline-flex items-center gap-1.5">
                          <span
                            className="h-2 w-2 rounded-full"
                            style={{ backgroundColor: URGENCY_LEVELS[a.urgency_level].hex }}
                          />
                          <span className="text-[var(--color-text-primary)]">
                            {URGENCY_LEVELS[a.urgency_level].short}
                          </span>
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