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
import type { FirstAidCategory } from "@/types/firstAidCategory";
import { MultiSelectFilter } from "@/components/ui/MultiSelectFilter";

export interface FirstAidFilterValue {
  search: string;
  status: string;
  first_aid_category_ids: string[];
}

interface FirstAidFiltersProps {
  value: FirstAidFilterValue;
  onChange: (value: FirstAidFilterValue) => void;
  categories: FirstAidCategory[];
}

const STATUS_OPTIONS = [
  { label: "ทุกสถานะ", value: "" },
  { label: "เผยแพร่", value: "1" },
  { label: "ฉบับร่าง", value: "2" },
  { label: "เก็บถาวร", value: "3" },
];

export function FirstAidFilters({
  value,
  onChange,
  categories,
}: FirstAidFiltersProps) {
  const [searchInput, setSearchInput] = useState(value.search);

  useEffect(() => {
    const timer = setTimeout(() => {
      if (searchInput !== value.search) {
        onChange({ ...value, search: searchInput });
      }
    }, 400);
    return () => clearTimeout(timer);
  }, [onChange, searchInput, value]);

  const categoryOptions = [
    ...categories.map((c) => ({
      label: c.category_name,
      value: c.first_aid_category_id,
    })),
  ];

  const activeCount = [
    value.search,
    value.status,
  ].filter(Boolean).length + value.first_aid_category_ids.length;
  const hasActiveFilters = activeCount > 0;

  return (
    <Card className="p-3 sm:p-4">
      <div className="flex flex-col gap-2.5 sm:flex-row sm:items-center sm:gap-3">
        <div className="min-w-0 flex-1">
          <SearchBar
            value={searchInput}
            onChange={setSearchInput}
            placeholder="ค้นหา ID หรือชื่อเรื่องปฐมพยาบาล..."
          />
        </div>

        <div className="flex items-center gap-2 sm:gap-3">
          <MultiSelectFilter
            values={value.first_aid_category_ids}
            onChange={(first_aid_category_ids) => onChange({ ...value, first_aid_category_ids })}
            options={categoryOptions}
            label="หมวดหมู่"
            className="min-w-0 flex-1 sm:w-44 sm:flex-initial"
          />

          <SimpleSelect
            value={value.status}
            onChange={(status) => onChange({ ...value, status })}
            options={STATUS_OPTIONS}
            placeholder="สถานะ"
            className="min-w-0 flex-1 sm:w-36 sm:flex-initial"
          />

          <Tooltip>
            <TooltipTrigger asChild>
              <button
                type="button"
                onClick={() =>
                  onChange({
                    search: "",
                    status: "",
                    first_aid_category_ids: [],
                  })
                }
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
