import { useEffect, useState } from "react";
import { X } from "lucide-react";
import { Card } from "@/components/ui/Card";
import { SearchBar } from "@/components/ui/SearchBar";
import { SimpleSelect } from "@/components/ui/SimpleSelect";
import { Tooltip, TooltipContent, TooltipTrigger } from "@/components/ui/Tooltip";
import { ARTICLE_COMMENT_REPORT_REASONS, type ArticleCommentReportFilters as FilterValue } from "@/types/articleComment";

const statusOptions = [
  { label: "ทุกสถานะ", value: "all" },
  { label: "รอตรวจสอบ", value: "pending" },
  { label: "ดำเนินการแล้ว", value: "resolved" },
  { label: "ไม่พบการละเมิด", value: "dismissed" },
];

const reasonOptions = [
  { label: "ทุกเหตุผล", value: "all" },
  ...Object.entries(ARTICLE_COMMENT_REPORT_REASONS).map(([value, label]) => ({ value, label })),
];

type Props = { value: FilterValue; onChange: (value: FilterValue) => void };

export function ArticleCommentReportFilters({ value, onChange }: Props) {
  const [searchInput, setSearchInput] = useState(value.search);

  useEffect(() => {
    const timer = window.setTimeout(() => {
      if (searchInput !== value.search) onChange({ ...value, search: searchInput });
    }, 400);
    return () => window.clearTimeout(timer);
  }, [onChange, searchInput, value]);

  const activeCount = [value.search, value.status !== "all", value.reason !== "all", value.sortDirection !== "desc"].filter(Boolean).length;

  return (
    <Card className="p-3 sm:p-4">
      <div className="flex flex-col gap-2.5 sm:flex-row sm:items-end sm:gap-3">
        <div className="min-w-0 flex-1">
          <SearchBar value={searchInput} onChange={setSearchInput} placeholder="ค้นหาบทความ ความคิดเห็น หรือชื่อผู้รายงาน..." />
        </div>
        <div className="grid grid-cols-2 items-end gap-2 sm:flex sm:gap-3">
          <SimpleSelect label="สถานะรายงาน" placeholder="สถานะรายงาน" value={value.status} onChange={(status) => onChange({ ...value, status })} options={statusOptions} className="min-w-0 sm:w-44" />
          <SimpleSelect label="เหตุผลที่รายงาน" placeholder="เหตุผลที่รายงาน" value={value.reason} onChange={(reason) => onChange({ ...value, reason })} options={reasonOptions} className="min-w-0 sm:w-56" />
          <div className="col-span-2 flex items-end gap-2 sm:contents">
            <SimpleSelect label="เรียงตาม" placeholder="เรียงตาม" value={value.sortDirection} onChange={(sortDirection) => onChange({ ...value, sortDirection: sortDirection as "asc" | "desc" })} options={[{ label: "รายงานล่าสุด", value: "desc" }, { label: "รายงานเก่าสุด", value: "asc" }]} className="min-w-0 flex-1 sm:w-40 sm:flex-initial" />
            <Tooltip>
              <TooltipTrigger asChild>
                <button type="button" disabled={activeCount === 0} onClick={() => { setSearchInput(""); onChange({ search: "", status: "all", reason: "all", sortDirection: "desc" }); }} className="filter-clear-button group relative flex h-9 w-9 shrink-0 items-center justify-center self-end rounded-full border border-transparent text-[var(--color-text-secondary)] transition-colors hover:bg-red-50 hover:text-red-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-primary)] focus-visible:ring-offset-1 disabled:cursor-not-allowed disabled:opacity-30" aria-label="ล้างตัวกรอง">
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
