import { useEffect, useState } from "react";
import { X } from "lucide-react";
import { Card } from "@/components/ui/Card";
import { SearchBar } from "@/components/ui/SearchBar";
import { MultiSelectFilter } from "@/components/ui/MultiSelectFilter";
import { SimpleSelect } from "@/components/ui/SimpleSelect";
import {
  Tooltip,
  TooltipContent,
  TooltipTrigger,
} from "@/components/ui/Tooltip";
import {
  NOTIFICATION_TYPES,
  type NotificationFilters as Value,
} from "@/types/notification";

export function NotificationFilters({
  value,
  onChange,
}: {
  value: Value;
  onChange: (value: Value) => void;
}) {
  const [searchInput, setSearchInput] = useState(value.search);
  const selectedTypes = value.types ?? [];

  useEffect(() => {
    const timer = window.setTimeout(() => {
      if (searchInput !== value.search)
        onChange({ ...value, search: searchInput });
    }, 400);
    return () => window.clearTimeout(timer);
  }, [onChange, searchInput, value]);

  const activeCount = [
    value.search,
    selectedTypes.length > 0,
    value.status !== "all",
    value.sortDirection !== "desc",
  ].filter(Boolean).length;

  return (
    <Card className="p-3 sm:p-4">
      <div className="flex flex-col gap-2.5 sm:flex-row sm:items-end sm:gap-3">
        <div className="min-w-0 flex-1">
          <SearchBar
            value={searchInput}
            onChange={setSearchInput}
            placeholder="ค้นหาหัวข้อการแจ้งเตือน..."
          />
        </div>
        <div className="grid grid-cols-2 items-end gap-2 sm:flex sm:items-end sm:gap-3">
          <MultiSelectFilter
            label="ประเภท"
            emptyLabel="ทุกประเภท"
            values={selectedTypes}
            onChange={(types) => onChange({ ...value, types })}
            options={NOTIFICATION_TYPES}
            className="min-w-0 flex-1 sm:w-40 sm:flex-initial"
          />
          <SimpleSelect
            label="สถานะ"
            placeholder="สถานะ"
            value={value.status}
            onChange={(status) => onChange({ ...value, status })}
            options={[
              { label: "ทุกสถานะ", value: "all" },
              { label: "ฉบับร่าง", value: "draft" },
              { label: "รอส่ง", value: "scheduled" },
              { label: "เข้าคิว", value: "queued" },
              { label: "กำลังส่ง", value: "processing" },
              { label: "ส่งแล้ว", value: "sent" },
              { label: "ส่งไม่ครบ", value: "partially_failed" },
              { label: "ยกเลิก", value: "cancelled" },
            ]}
            className="min-w-0 flex-1 sm:w-40 sm:flex-initial"
          />
          <div className="col-span-2 flex items-end gap-2 sm:contents">
            <SimpleSelect
              label="เรียงตาม"
              placeholder="เรียงตาม"
              value={value.sortDirection}
              onChange={(sortDirection) =>
                onChange({
                  ...value,
                  sortDirection: sortDirection as "asc" | "desc",
                })
              }
              options={[
                { label: "ใหม่ล่าสุด", value: "desc" },
                { label: "เก่าที่สุด", value: "asc" },
              ]}
              className="min-w-0 flex-1 sm:w-40 sm:flex-initial"
            />
            <Tooltip>
              <TooltipTrigger asChild>
                <button
                  type="button"
                  disabled={activeCount === 0}
                  onClick={() => {
                    setSearchInput("");
                    onChange({
                      search: "",
                      types: [],
                      status: "all",
                      sortDirection: "desc",
                    });
                  }}
                  className="filter-clear-button group relative flex h-9 w-9 shrink-0 items-center justify-center self-end rounded-full border border-transparent text-[var(--color-text-secondary)] transition-colors hover:bg-red-50 hover:text-red-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-primary)] focus-visible:ring-offset-1 disabled:cursor-not-allowed disabled:opacity-30"
                  aria-label="ล้างตัวกรอง"
                >
                  <X className="h-4 w-4" />
                  {activeCount > 0 && (
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
      </div>
    </Card>
  );
}
