import { Card } from "@/components/ui/Card";
import { MultiSelectFilter } from "@/components/ui/MultiSelectFilter";
import { SearchBar } from "@/components/ui/SearchBar";
import { SimpleSelect } from "@/components/ui/SimpleSelect";
import {
  Tooltip,
  TooltipContent,
  TooltipTrigger,
} from "@/components/ui/Tooltip";
import { X } from "lucide-react";

export interface AdaptiveQuestionFilterValue {
  search: string;
  answerTypes: string[];
  status: string;
}

interface AdaptiveQuestionFiltersProps {
  value: AdaptiveQuestionFilterValue;
  onChange: (value: AdaptiveQuestionFilterValue) => void;
}

export const answerTypeOptions = [
  { value: "yes_no_unsure", label: "ใช่ / ไม่ใช่ / ไม่แน่ใจ" },
  { value: "single_choice", label: "เลือกหนึ่งข้อ" },
  { value: "multiple_choice", label: "เลือกหลายข้อ" },
];

export const statusOptions = [
  { value: "", label: "ทุกสถานะ" },
  { value: "draft", label: "ฉบับร่าง" },
  { value: "reviewed", label: "ตรวจแหล่งอ้างอิงแล้ว" },
  { value: "approved", label: "อนุมัติแล้ว" },
  { value: "inactive", label: "ปิดใช้งาน" },
];

export function AdaptiveQuestionFilters({
  value,
  onChange,
}: AdaptiveQuestionFiltersProps) {
  const activeCount = [
    value.search,
    value.answerTypes.length > 0,
    value.status,
  ].filter(Boolean).length;

  return (
    <Card className="p-3 sm:p-4">
      <div className="flex flex-col gap-2.5 sm:flex-row sm:items-center sm:gap-3">
        <div className="min-w-0 flex-1">
          <SearchBar
            value={value.search}
            onChange={(search) => onChange({ ...value, search })}
            placeholder="ค้นหาคำถาม..."
          />
        </div>
        <MultiSelectFilter
          label="รูปแบบคำตอบ"
          values={value.answerTypes}
          onChange={(answerTypes) => onChange({ ...value, answerTypes })}
          options={answerTypeOptions}
          className="sm:w-56"
        />
        <SimpleSelect
          label="สถานะ"
          placeholder="ทุกสถานะ"
          value={value.status}
          onChange={(status) => onChange({ ...value, status })}
          options={statusOptions}
          className="sm:w-48"
        />
        <Tooltip>
          <TooltipTrigger asChild>
            <button
              type="button"
              onClick={() =>
                onChange({ search: "", answerTypes: [], status: "" })
              }
              disabled={activeCount === 0}
              aria-label="ล้างตัวกรอง"
              className={`filter-clear-button group relative flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-transparent transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-primary)] ${
                activeCount
                  ? "text-[var(--color-text-secondary)] hover:bg-red-50 hover:text-red-600"
                  : "cursor-not-allowed text-[var(--color-border)]"
              }`}
            >
              <X className="h-4 w-4" />
              {activeCount > 0 && (
                <span className="absolute -right-1 -top-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-[var(--color-primary)] px-1 text-[10px] font-semibold text-white">
                  {activeCount}
                </span>
              )}
            </button>
          </TooltipTrigger>
          <TooltipContent>ล้างตัวกรอง</TooltipContent>
        </Tooltip>
      </div>
    </Card>
  );
}
