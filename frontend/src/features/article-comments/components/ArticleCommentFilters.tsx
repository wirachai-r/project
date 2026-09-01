import { X } from "lucide-react";
import { Card } from "@/components/ui/Card";
import { SearchBar } from "@/components/ui/SearchBar";
import { SimpleSelect } from "@/components/ui/SimpleSelect";

export type ArticleCommentFilterValue = {
  search: string;
  visibility: string;
  reportStatus: string;
  sortBy: string;
};

const visibilityOptions = [
  { label: "ทุกสถานะ", value: "all" },
  { label: "แสดงอยู่", value: "visible" },
  { label: "ถูกซ่อน", value: "hidden" },
];

const reportOptions = [
  { label: "ทุกรายงาน", value: "all" },
  { label: "มีรายงานรอตรวจสอบ", value: "reported" },
  { label: "ไม่มีรายงาน", value: "unreported" },
];

const sortOptions = [
  { label: "แสดงความคิดเห็นล่าสุด", value: "latest_comment" },
  { label: "รายงานล่าสุด", value: "latest_report" },
];

type Props = {
  value: ArticleCommentFilterValue;
  onChange: (value: ArticleCommentFilterValue) => void;
};

export function ArticleCommentFilters({ value, onChange }: Props) {
  const activeCount = [
    value.search.trim(),
    value.visibility !== "all",
    value.reportStatus !== "all",
    value.sortBy !== "latest_comment",
  ].filter(Boolean).length;

  return (
    <Card className="p-3 sm:p-4">
      <div className="flex flex-col gap-2.5 sm:flex-row sm:items-end sm:gap-3">
        <div className="min-w-0 flex-1">
          <SearchBar
            value={value.search}
            onChange={(search) => onChange({ ...value, search })}
            placeholder="ค้นหาบทความ ความคิดเห็น ชื่อ หรืออีเมล..."
          />
        </div>
        <div className="grid grid-cols-2 items-end gap-2 sm:flex sm:gap-3">
          <SimpleSelect
            label="สถานะการแสดงผล"
            value={value.visibility}
            onChange={(visibility) => onChange({ ...value, visibility })}
            options={visibilityOptions}
            placeholder="สถานะการแสดงผล"
            className="min-w-0 sm:w-40"
          />
          <SimpleSelect
            label="สถานะรายงาน"
            value={value.reportStatus}
            onChange={(reportStatus) => onChange({ ...value, reportStatus })}
            options={reportOptions}
            placeholder="สถานะรายงาน"
            className="min-w-0 sm:w-52"
          />
          <div className="col-span-2 flex items-end gap-2 sm:contents">
            <SimpleSelect
              label="เรียงตาม"
              value={value.sortBy}
              onChange={(sortBy) => onChange({ ...value, sortBy })}
              options={sortOptions}
              placeholder="เรียงตาม"
              className="min-w-0 flex-1 sm:w-52 sm:flex-initial"
            />
            <button
              type="button"
              disabled={activeCount === 0}
              onClick={() =>
                onChange({
                  search: "",
                  visibility: "all",
                  reportStatus: "all",
                  sortBy: "latest_comment",
                })
              }
              className="filter-clear-button group relative flex h-9 w-9 shrink-0 items-center justify-center self-end rounded-full border border-transparent text-[var(--color-text-secondary)] transition-colors hover:bg-red-50 hover:text-red-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-primary)] focus-visible:ring-offset-1 disabled:cursor-not-allowed disabled:opacity-30"
              aria-label="ล้างตัวกรอง"
              title="ล้างตัวกรอง"
            >
              <X className="h-4 w-4" />
              {activeCount > 0 && (
                <span className="absolute -right-1 -top-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-[var(--color-primary)] px-1 text-[10px] font-semibold leading-none text-white group-hover:bg-red-600">
                  {activeCount}
                </span>
              )}
            </button>
          </div>
        </div>
      </div>
    </Card>
  );
}
