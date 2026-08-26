import { useEffect, useState } from "react";
import { X } from "lucide-react";
import { Card } from "@/components/ui/Card";
import { SearchBar } from "@/components/ui/SearchBar";
import { SimpleSelect } from "@/components/ui/SimpleSelect";
import { Tooltip, TooltipContent, TooltipTrigger } from "@/components/ui/Tooltip";
import { URGENCY_OPTIONS } from "@/types/diagnosisRule";

export interface DiagnosisRuleFilterValue {
  search: string;
  status: string;
  diagram_id: string;
  urgency_level: string;
}

interface DiagramOption {
  diagram_id: string;
  diagram_name: string;
}

interface DiagnosisRuleFiltersProps {
  value: DiagnosisRuleFilterValue;
  onChange: (value: DiagnosisRuleFilterValue) => void;
  diagrams: DiagramOption[];
}

export function DiagnosisRuleFilters({ value, onChange, diagrams }: DiagnosisRuleFiltersProps) {
  const [searchInput, setSearchInput] = useState(value.search);

  useEffect(() => {
    const timer = window.setTimeout(() => {
      if (searchInput !== value.search) onChange({ ...value, search: searchInput });
    }, 400);
    return () => window.clearTimeout(timer);
  }, [onChange, searchInput, value]);

  useEffect(() => setSearchInput(value.search), [value.search]);

  const activeCount = [value.search, value.status, value.diagram_id, value.urgency_level].filter(Boolean).length;

  return (
    <Card className="p-3 sm:p-4">
      <div className="flex flex-col gap-2.5 sm:flex-row sm:items-center sm:gap-3">
        <div className="min-w-0 flex-1">
          <SearchBar value={searchInput} onChange={setSearchInput} placeholder="ค้นหา ID หรือข้อมูลกฎ..." />
        </div>
        <div className="flex items-center gap-2 sm:gap-3">
          <SimpleSelect
            value={value.diagram_id}
            onChange={(diagram_id) => onChange({ ...value, diagram_id })}
            placeholder="แผนภูมิ"
            options={diagrams.map((diagram) => ({ label: diagram.diagram_name, value: diagram.diagram_id }))}
            className="min-w-0 flex-1 sm:w-52 sm:flex-initial"
          />
          <SimpleSelect
            value={value.urgency_level}
            onChange={(urgency_level) => onChange({ ...value, urgency_level })}
            placeholder="ระดับความเร่งด่วน"
            options={URGENCY_OPTIONS.map((urgency) => ({ label: urgency.label, value: urgency.value }))}
            className="min-w-0 flex-1 sm:w-44 sm:flex-initial"
          />
          <SimpleSelect
            value={value.status}
            onChange={(status) => onChange({ ...value, status })}
            placeholder="สถานะ"
            options={[{ label: "ใช้งานได้", value: "1" }, { label: "ปิดใช้งาน", value: "2" }]}
            className="min-w-0 flex-1 sm:w-40 sm:flex-initial"
          />
          <Tooltip>
            <TooltipTrigger asChild>
              <button
                type="button"
                disabled={activeCount === 0}
                onClick={() => {
                  setSearchInput("");
                  onChange({ search: "", status: "", diagram_id: "", urgency_level: "" });
                }}
                className="filter-clear-button group relative flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-transparent text-[var(--color-text-secondary)] transition-colors hover:bg-red-50 hover:text-red-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-primary)] focus-visible:ring-offset-1 disabled:cursor-not-allowed disabled:opacity-30"
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
    </Card>
  );
}
