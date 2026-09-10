import { useState, useEffect } from "react";
import { ChevronDown, X } from "lucide-react";
import { Card } from "../../../components/ui/Card";
import { SearchBar } from "../../../components/ui/SearchBar";
import { SimpleSelect } from "../../../components/ui/SimpleSelect";
import {
  Tooltip,
  TooltipTrigger,
  TooltipContent,
} from "../../../components/ui/Tooltip";
import type { DiseaseCategory } from "@/types/diseaseCategory";
import {
  DropdownMenu,
  DropdownMenuCheckboxItem,
  DropdownMenuContent,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "@/components/ui/DropdownMenu";

export interface DiseaseFilterValue {
  search: string;
  status: string;
  disease_category_ids: string[];
}

interface DiseaseFiltersProps {
  value: DiseaseFilterValue;
  onChange: (value: DiseaseFilterValue) => void;
  categories: DiseaseCategory[];
}

const STATUS_OPTIONS = [
  { label: "ทุกสถานะ", value: "" },
  { label: "ใช้งานได้", value: "1" },
  { label: "ปิดใช้งาน", value: "2" },
];

export function DiseaseFilters({
  value,
  onChange,
  categories,
}: DiseaseFiltersProps) {
  const [searchInput, setSearchInput] = useState(value.search);

  useEffect(() => {
    const timer = setTimeout(() => {
      if (searchInput !== value.search) {
        onChange({ ...value, search: searchInput });
      }
    }, 400);
    return () => clearTimeout(timer);
  }, [onChange, searchInput, value]);

  const sortedCategories = [...categories].sort((left, right) =>
    left.category_name.localeCompare(right.category_name, "th", {
      sensitivity: "base",
      numeric: true,
    }),
  );
  const selectedCategoryLabel = value.disease_category_ids.length === 0
    ? "ทุกหมวดหมู่"
    : value.disease_category_ids.length === 1
      ? sortedCategories.find((category) => category.disease_category_id === value.disease_category_ids[0])?.category_name ?? "1 หมวดหมู่"
      : `${value.disease_category_ids.length} หมวดหมู่`;
  const activeCount = [value.search, value.status].filter(Boolean).length
    + value.disease_category_ids.length;
  const hasActiveFilters = activeCount > 0;

  return (
    <Card className="p-3 sm:p-4">
      <div className="flex flex-col gap-2.5 sm:flex-row sm:items-center sm:gap-3">
        <div className="min-w-0 flex-1">
          <SearchBar
            value={searchInput}
            onChange={setSearchInput}
            placeholder="ค้นหาชื่อโรค..."
          />
        </div>

        <div className="flex items-center gap-2 sm:gap-3">
          <div className="min-w-0 flex-1 sm:w-44 sm:flex-initial">
            <label className="mb-1.5 block text-sm font-medium text-[var(--color-text-primary)]">หมวดหมู่</label>
            <DropdownMenu>
              <DropdownMenuTrigger asChild>
                <button type="button" className="flex h-9 w-full items-center justify-between gap-2 rounded-lg border border-[var(--color-border)] bg-white px-3 text-sm text-[var(--color-text-primary)] outline-none transition focus:border-[var(--color-primary)] focus:ring-2 focus:ring-[var(--color-primary-light)]">
                  <span className="truncate">{selectedCategoryLabel}</span>
                  <ChevronDown className="h-4 w-4 shrink-0 text-[var(--color-text-secondary)]" />
                </button>
              </DropdownMenuTrigger>
              <DropdownMenuContent align="start" className="max-h-80 min-w-60 overflow-y-auto">
                <DropdownMenuLabel className="flex items-center justify-between">
                  เลือกหมวดหมู่
                  {value.disease_category_ids.length > 0 && (
                    <button type="button" onClick={() => onChange({ ...value, disease_category_ids: [] })} className="text-xs text-red-600 hover:underline">ล้างทั้งหมด</button>
                  )}
                </DropdownMenuLabel>
                <DropdownMenuSeparator />
                {sortedCategories.map((category) => (
                  <DropdownMenuCheckboxItem
                    key={category.disease_category_id}
                    checked={value.disease_category_ids.includes(category.disease_category_id)}
                    onSelect={(event) => event.preventDefault()}
                    onCheckedChange={() => onChange({
                      ...value,
                      disease_category_ids: value.disease_category_ids.includes(category.disease_category_id)
                        ? value.disease_category_ids.filter((id) => id !== category.disease_category_id)
                        : [...value.disease_category_ids, category.disease_category_id],
                    })}
                  >
                    {category.category_name}
                  </DropdownMenuCheckboxItem>
                ))}
              </DropdownMenuContent>
            </DropdownMenu>
          </div>

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
                  onChange({ search: "", status: "", disease_category_ids: [] })
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
