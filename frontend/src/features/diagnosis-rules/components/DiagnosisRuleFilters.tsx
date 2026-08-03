import { Search } from "lucide-react";
import { Input } from "../../../components/ui/Input";
import { SimpleSelect } from "../../../components/ui/SimpleSelect";
import { URGENCY_OPTIONS } from "../types";

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

export function DiagnosisRuleFilters({
  value,
  onChange,
  diagrams,
}: DiagnosisRuleFiltersProps) {
  return (
    <div className="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center">
      <div className="relative flex-1 sm:max-w-xs">
        <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-[var(--color-text-secondary)]" />
        <Input
          value={value.search}
          onChange={(e) => onChange({ ...value, search: e.target.value })}
          placeholder="ค้นหากฎการวินิจฉัย..."
          className="pl-9"
        />
      </div>

      <div className="w-full sm:w-52">
        <SimpleSelect
          value={value.diagram_id}
          onChange={(v) => onChange({ ...value, diagram_id: v })}
          placeholder="ทุกแผนภูมิ"
          options={diagrams.map((d) => ({
            label: d.diagram_name,
            value: d.diagram_id,
          }))}
        />
      </div>

      <div className="w-full sm:w-44">
        <SimpleSelect
          value={value.urgency_level}
          onChange={(v) => onChange({ ...value, urgency_level: v })}
          placeholder="ทุกระดับความเร่งด่วน"
          options={URGENCY_OPTIONS.map((u) => ({ label: u.label, value: u.value }))}
        />
      </div>

      <div className="w-full sm:w-40">
        <SimpleSelect
          value={value.status}
          onChange={(v) => onChange({ ...value, status: v })}
          placeholder="ทุกสถานะ"
          options={[
            { label: "ใช้งานได้", value: "1" },
            { label: "ปิดใช้งาน", value: "2" },
          ]}
        />
      </div>
    </div>
  );
}