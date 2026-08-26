import { X } from "lucide-react";
import { Card } from "@/components/ui/Card";
import { SearchBar } from "@/components/ui/SearchBar";
import { SimpleSelect } from "@/components/ui/SimpleSelect";

export interface BodyAreaGroupFilterValue {
  search: string;
  status: string;
}

export function BodyAreaGroupFilters({
  value,
  onChange,
}: {
  value: BodyAreaGroupFilterValue;
  onChange: (value: BodyAreaGroupFilterValue) => void;
}) {
  const activeCount = [value.search, value.status !== "all"].filter(
    Boolean,
  ).length;

  return (
    <Card className="p-3 sm:p-4">
      <div className="flex flex-col gap-2.5 sm:flex-row sm:items-end sm:gap-3">
        <div className="min-w-0 flex-1">
          <SearchBar
            value={value.search}
            onChange={(search) => onChange({ ...value, search })}
            placeholder="ค้นหาชื่อกลุ่มหรือคำอธิบาย..."
          />
        </div>
        <div className="flex w-full items-end gap-2 sm:w-auto sm:gap-3">
          <SimpleSelect
            label="สถานะ"
            value={value.status}
            onChange={(status) => onChange({ ...value, status })}
            placeholder="สถานะ"
            options={[
              { label: "ทุกสถานะ", value: "all" },
              { label: "ใช้งาน", value: "1" },
              { label: "ปิดใช้งาน", value: "2" },
            ]}
            className="min-w-0 flex-1 sm:w-40 sm:flex-initial"
          />
          <button
            type="button"
            disabled={activeCount === 0}
            onClick={() => onChange({ search: "", status: "all" })}
            className="filter-clear-button group relative flex h-9 w-9 shrink-0 items-center justify-center self-end rounded-full text-[var(--color-text-secondary)] hover:bg-red-50 hover:text-red-600 disabled:cursor-not-allowed disabled:opacity-30"
            aria-label="ล้างตัวกรอง"
            title="ล้างตัวกรอง"
          >
            <X className="h-4 w-4" />
            {activeCount > 0 && (
              <span className="absolute -right-1 -top-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-[var(--color-primary)] px-1 text-[10px] font-semibold text-white">
                {activeCount}
              </span>
            )}
          </button>
        </div>
      </div>
    </Card>
  );
}
