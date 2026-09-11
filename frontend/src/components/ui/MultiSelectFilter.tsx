import { useState } from "react";
import { ChevronDown, Search } from "lucide-react";
import {
  DropdownMenu,
  DropdownMenuCheckboxItem,
  DropdownMenuContent,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "./DropdownMenu";
import { fuzzyIncludes } from "@/lib/fuzzySearch";

interface Option {
  label: string;
  value: string;
  searchText?: string;
}

interface Props {
  label: string;
  values: string[];
  options: Option[];
  onChange: (values: string[]) => void;
  className?: string;
  emptyLabel?: string;
  searchable?: boolean;
  searchPlaceholder?: string;
}

export function MultiSelectFilter({
  label,
  values,
  options,
  onChange,
  className = "",
  emptyLabel,
  searchable = false,
  searchPlaceholder = "ค้นหา...",
}: Props) {
  const [search, setSearch] = useState("");
  const sortedOptions = [...options].sort((left, right) =>
    left.label.localeCompare(right.label, "th", {
      sensitivity: "base",
      numeric: true,
    }),
  );
  const visibleOptions = search.trim()
    ? sortedOptions.filter((option) =>
        fuzzyIncludes(`${option.label} ${option.searchText ?? ""}`, search),
      )
    : sortedOptions;
  const selectedLabel = values.length === 0
    ? emptyLabel ?? `ทุก${label}`
    : values.length === 1
      ? sortedOptions.find((option) => option.value === values[0])?.label ?? `1 ${label}`
      : `${values.length} ${label}`;

  return (
    <div className={`min-w-0 ${className}`}>
      <label className="mb-1.5 block text-sm font-medium text-[var(--color-text-primary)]">{label}</label>
      <DropdownMenu>
        <DropdownMenuTrigger asChild>
          <button type="button" className="flex h-9 w-full items-center justify-between gap-2 rounded-lg border border-[var(--color-border)] bg-white px-3 text-sm text-[var(--color-text-primary)] outline-none transition focus:border-[var(--color-primary)] focus:ring-2 focus:ring-[var(--color-primary-light)]">
            <span className="truncate">{selectedLabel}</span>
            <ChevronDown className="h-4 w-4 shrink-0 text-[var(--color-text-secondary)]" />
          </button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="start" className="max-h-80 min-w-60 overflow-y-auto">
          <DropdownMenuLabel className="flex items-center justify-between">
            เลือก{label}
            {values.length > 0 && <button type="button" onClick={() => onChange([])} className="text-xs text-red-600 hover:underline">ล้างทั้งหมด</button>}
          </DropdownMenuLabel>
          {searchable && (
            <div className="px-2 pb-2" onKeyDown={(event) => event.stopPropagation()}>
              <div className="flex h-9 items-center gap-2 rounded-lg border border-[var(--color-border)] bg-white px-3 focus-within:border-[var(--color-primary)] focus-within:ring-2 focus-within:ring-[var(--color-primary-light)]">
                <Search className="h-4 w-4 shrink-0 text-[var(--color-text-secondary)]" />
                <input
                  type="search"
                  value={search}
                  onChange={(event) => setSearch(event.target.value)}
                  placeholder={searchPlaceholder}
                  className="min-w-0 flex-1 bg-transparent text-sm outline-none placeholder:text-[var(--color-text-muted)]"
                />
              </div>
            </div>
          )}
          <DropdownMenuSeparator />
          {visibleOptions.map((option) => (
            <DropdownMenuCheckboxItem
              key={option.value}
              checked={values.includes(option.value)}
              onSelect={(event) => event.preventDefault()}
              onCheckedChange={() => onChange(
                values.includes(option.value)
                  ? values.filter((value) => value !== option.value)
                  : [...values, option.value],
              )}
            >
              {option.label}
            </DropdownMenuCheckboxItem>
          ))}
          {visibleOptions.length === 0 && (
            <p className="px-3 py-4 text-center text-sm text-[var(--color-text-secondary)]">
              ไม่พบรายการ
            </p>
          )}
        </DropdownMenuContent>
      </DropdownMenu>
    </div>
  );
}
