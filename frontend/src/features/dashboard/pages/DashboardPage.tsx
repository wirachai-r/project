import { useEffect, useState } from "react";
import {
  Users,
  ClipboardList,
  Activity,
  Newspaper,
  HeartPulse,
  LifeBuoy,
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

export function DashboardPage() {
  const [stats, setStats] = useState<DashboardStats | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    api
      .get<DashboardStats>("/admin/dashboard/stats")
      .then(({ data }) => setStats(data))
      .finally(() => setLoading(false));
  }, []);

  if (loading) return <Spinner fullscreen label="กำลังโหลดข้อมูลแดชบอร์ด..." />;
  if (!stats) return null;

  const overviewCards = [
    { label: "ผู้ใช้งานทั้งหมด", value: stats.overview.total_users, icon: Users },
    { label: "การประเมินทั้งหมด", value: stats.overview.total_assessments, icon: ClipboardList },
    { label: "โรคที่เปิดใช้งาน", value: stats.overview.total_diseases, icon: HeartPulse },
    { label: "อาการที่เปิดใช้งาน", value: stats.overview.total_symptoms, icon: Activity },
    { label: "บทความ", value: stats.overview.total_articles, icon: Newspaper },
    { label: "ข้อมูลปฐมพยาบาล", value: stats.overview.total_first_aids, icon: LifeBuoy },
  ];

  const totalUrgency = Object.values(stats.urgency_summary).reduce((a, b) => a + b, 0) || 1;

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-xl font-semibold text-[var(--color-text-primary)]">แดชบอร์ด</h1>
        <p className="mt-1 text-sm text-[var(--color-text-secondary)]">
          ภาพรวมระบบประเมินอาการเบื้องต้น
        </p>
      </div>

      {/* Overview cards */}
      <div className="grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-6">
        {overviewCards.map(({ label, value, icon: Icon }) => (
          <div
            key={label}
            className="rounded-2xl border border-[var(--color-border)] bg-white p-4"
          >
            <div className="mb-3 flex h-9 w-9 items-center justify-center rounded-lg bg-[var(--color-primary-light)]">
              <Icon className="h-4.5 w-4.5 text-[var(--color-primary)]" />
            </div>
            <p className="text-2xl font-semibold text-[var(--color-text-primary)]">{value}</p>
            <p className="mt-0.5 text-xs text-[var(--color-text-secondary)]">{label}</p>
          </div>
        ))}
      </div>

      <div className="grid grid-cols-1 gap-4 lg:grid-cols-3">
        {/* Assessment today */}
        <div className="rounded-2xl border border-[var(--color-border)] bg-white p-5">
          <p className="text-sm font-medium text-[var(--color-text-primary)]">การประเมินวันนี้</p>
          <div className="mt-4 flex items-end gap-6">
            <div>
              <p className="text-3xl font-semibold text-[var(--color-primary)]">
                {stats.assessment_today.total}
              </p>
              <p className="mt-1 text-xs text-[var(--color-text-secondary)]">ทั้งหมด</p>
            </div>
            <div>
              <p className="text-3xl font-semibold text-[var(--color-success)]">
                {stats.assessment_today.completed}
              </p>
              <p className="mt-1 text-xs text-[var(--color-text-secondary)]">เสร็จสิ้น</p>
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
                  style={{ width: `${pct}%`, backgroundColor: URGENCY_LEVELS[code].hex }}
                  title={`${URGENCY_LEVELS[code].short}: ${count}`}
                />
              );
            })}
          </div>
          <div className="mt-4 grid grid-cols-5 gap-2">
            {(Object.keys(URGENCY_LEVELS) as UrgencyCode[]).map((code) => (
              <div key={code} className="flex items-center gap-1.5">
                <span
                  className="h-2 w-2 rounded-full"
                  style={{ backgroundColor: URGENCY_LEVELS[code].hex }}
                />
                <span className="text-xs text-[var(--color-text-secondary)]">
                  {URGENCY_LEVELS[code].short} {stats.urgency_summary[code] ?? 0}
                </span>
              </div>
            ))}
          </div>
        </div>
      </div>

      <div className="grid grid-cols-1 gap-4 lg:grid-cols-3">
        {/* New users trend */}
        <div className="rounded-2xl border border-[var(--color-border)] bg-white p-5 lg:col-span-2">
          <p className="mb-4 text-sm font-medium text-[var(--color-text-primary)]">
            ผู้ใช้งานใหม่ (30 วันล่าสุด)
          </p>
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
                tick={{ fontSize: 11, fill: "#6B7280" }}
                axisLine={false}
                tickLine={false}
              />
              <YAxis tick={{ fontSize: 11, fill: "#6B7280" }} axisLine={false} tickLine={false} allowDecimals={false} />
              <Tooltip />
              <Area
                type="monotone"
                dataKey="total"
                stroke="var(--color-primary-mid)"
                strokeWidth={2}
                fill="url(#userTrend)"
              />
            </AreaChart>
          </ResponsiveContainer>
        </div>

        {/* Top diseases */}
        <div className="rounded-2xl border border-[var(--color-border)] bg-white p-5">
          <p className="mb-4 text-sm font-medium text-[var(--color-text-primary)]">
            โรคที่พบบ่อย
          </p>
          <ul className="space-y-3">
            {stats.top_diseases.slice(0, 6).map((d, i) => (
              <li key={d.disease_id} className="flex items-center justify-between text-sm">
                <span className="flex items-center gap-2 text-[var(--color-text-primary)]">
                  <span className="text-xs text-[var(--color-text-secondary)]">{i + 1}.</span>
                  {d.disease_name ?? d.disease_id}
                </span>
                <span className="font-medium text-[var(--color-primary)]">{d.total}</span>
              </li>
            ))}
            {stats.top_diseases.length === 0 && (
              <p className="text-sm text-[var(--color-text-secondary)]">ยังไม่มีข้อมูล</p>
            )}
          </ul>
        </div>
      </div>
    </div>
  );
}