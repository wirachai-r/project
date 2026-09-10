import { useState, useEffect } from "react";
import { X } from "lucide-react";
import { Card } from "../../../components/ui/Card";
import { SearchBar } from "../../../components/ui/SearchBar";
import { SimpleSelect } from "../../../components/ui/SimpleSelect";
import {
  Tooltip,
  TooltipTrigger,
  TooltipContent,
} from "../../../components/ui/Tooltip";

export interface ArticleCategoryFilterValue {
  search: string;
  status: string;
}

interface ArticleCategoryFiltersProps {
  value: ArticleCategoryFilterValue;
  onChange: (value: ArticleCategoryFilterValue) => void;
}

const STATUS_OPTIONS = [
  { label: "ทุกสถานะ", value: "" },
  { label: "ใช้งานได้", value: "1" },
  { label: "ปิดใช้งาน", value: "2" },
];

export function ArticleCategoryFilters({
  value,
  onChange,
}: ArticleCategoryFiltersProps) {
  const [searchInput, setSearchInput] = useState(value.search);

  useEffect(() => {
    const timer = setTimeout(() => {
      if (searchInput !== value.search) {
        onChange({ ...value, search: searchInput });
      }
    }, 400);
    return () => clearTimeout(timer);
  }, [onChange, searchInput, value]);

  const activeCount = [value.search, value.status].filter(Boolean).length;
  const hasActiveFilters = activeCount > 0;

  return (
    <Card className="p-3 sm:p-4">
      <div className="flex flex-col gap-2.5 sm:flex-row sm:items-center sm:gap-3">
        <div className="min-w-0 flex-1">
          <SearchBar
            value={searchInput}
            onChange={setSearchInput}
            placeholder="ค้นหาชื่อหมวดหมู่..."
          />
        </div>

        <div className="flex items-center gap-2 sm:gap-3">
          <SimpleSelect
            value={value.status}
            onChange={(status) => onChange({ ...value, status })}
            options={STATUS_OPTIONS}
            placeholder="สถานะ"
            className="min-w-0 flex-1 sm:w-40 sm:flex-initial"
          />

          <Tooltip>
            <TooltipTrigger asChild>
              <button
                type="button"
                onClick={() => onChange({ search: "", status: "" })}
                disabled={!hasActiveFilters}
                className={`filter-clear-button group relative flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-transparent transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-primary)] focus-visible:ring-offset-1 ${
                  hasActiveFilters
                    ? "text-[var(--color-text-secondary)] hover:bg-red-50 hover:text-red-600"
                    : "cursor-not-allowed text-[var(--color-border)]"
                }`}
              >
                <X className="h-4 w-4" />
                {hasActiveFilters && (
                  <span className="absolute -right-1 -top-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-[var(--color-primary)] px-1 text-[10px] font-semibold leading-none text-white group-hover:bg-red-600">
                    {activeCount}
                  </span>
                )}
              </button>
            </TooltipTrigger>
            <TooltipContent>ล้างตัวกรอง</TooltipContent>
          </Tooltip>
        </div>
      </div>
    </Card>
  );
}
