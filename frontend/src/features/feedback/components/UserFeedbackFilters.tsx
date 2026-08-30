import { useEffect, useState } from "react";
import { X } from "lucide-react";
import { Card } from "@/components/ui/Card";
import { SearchBar } from "@/components/ui/SearchBar";
import { SimpleSelect } from "@/components/ui/SimpleSelect";
import { FEEDBACK_TYPE_LABELS, type UserFeedbackFilterValue } from "@/types/userFeedback";
import { Tooltip, TooltipContent, TooltipTrigger } from "@/components/ui/Tooltip";

const statuses = [
  { label: "ทุกสถานะ", value: "all" },
  { label: "รอตรวจสอบ", value: "pending" },
  { label: "กำลังตรวจสอบ", value: "in_review" },
  { label: "ดำเนินการแล้ว", value: "resolved" },
  { label: "ปิดรายงาน", value: "dismissed" },
];
const types = [
  { label: "ทุกประเภท", value: "all" },
  ...Object.entries(FEEDBACK_TYPE_LABELS).map(([value, label]) => ({ value, label })),
];

export function UserFeedbackFilters({ value, onChange }: { value: UserFeedbackFilterValue; onChange: (value: UserFeedbackFilterValue) => void }) {
  const [search, setSearch] = useState(value.search);
  const activeCount = [
    value.search,
    value.status !== "all",
    value.feedbackType !== "all",
    value.sortDirection !== "desc",
  ].filter(Boolean).length;
  useEffect(() => {
    const timer = window.setTimeout(() => {
      if (search !== value.search) onChange({ ...value, search });
    }, 400);
    return () => window.clearTimeout(timer);
  }, [onChange, search, value]);
  return (
    <Card className="p-3 sm:p-4">
      <div className="flex flex-col gap-2.5 sm:flex-row sm:items-end sm:gap-3">
        <div className="min-w-0 flex-1"><SearchBar value={search} onChange={setSearch} placeholder="ค้นหาข้อความ ชื่อ หรืออีเมล..." /></div>
        <div className="grid grid-cols-2 items-end gap-2 sm:flex sm:items-end sm:gap-3">
        <SimpleSelect label="สถานะ" placeholder="สถานะ" value={value.status} onChange={(status) => onChange({ ...value, status })} options={statuses} className="min-w-0 sm:w-44 sm:flex-initial" />
        <SimpleSelect label="ประเภท" placeholder="ประเภท" value={value.feedbackType} onChange={(feedbackType) => onChange({ ...value, feedbackType })} options={types} className="min-w-0 sm:w-52 sm:flex-initial" />
        <div className="col-span-2 flex items-end gap-2 sm:contents">
        <SimpleSelect label="เรียงตาม" placeholder="เรียงตาม" value={value.sortDirection} onChange={(sortDirection) => onChange({ ...value, sortDirection: sortDirection as "asc" | "desc" })} options={[{ label: "ใหม่ล่าสุด", value: "desc" }, { label: "เก่าที่สุด", value: "asc" }]} className="min-w-0 flex-1 sm:w-40 sm:flex-initial" />
        <Tooltip>
          <TooltipTrigger asChild>
            <button
              type="button"
              disabled={activeCount === 0}
              onClick={() => {
                setSearch("");
                onChange({ search: "", status: "all", feedbackType: "all", sortDirection: "desc" });
              }}
              className="filter-clear-button group relative flex h-9 w-9 shrink-0 items-center justify-center self-end rounded-full border border-transparent text-[var(--color-text-secondary)] transition-colors hover:bg-red-50 hover:text-red-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-primary)] focus-visible:ring-offset-1 disabled:cursor-not-allowed disabled:opacity-30"
              aria-label="ล้างตัวกรอง"
            >
              <X className="h-4 w-4" />
              {activeCount > 0 && <span className="absolute -right-1 -top-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-[var(--color-primary)] px-1 text-[10px] font-semibold leading-none text-white group-hover:bg-red-600">{activeCount}</span>}
            </button>
          </TooltipTrigger>
          <TooltipContent>ล้างตัวกรอง</TooltipContent>
        </Tooltip>
        </div>
        </div>
      </div>
    </Card>
  );
}
