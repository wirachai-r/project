import { ArrowUpDown, ArrowUp, ArrowDown, Pencil, Trash2, GitBranch } from "lucide-react";
import type { DiagnosisRule } from "@/types/diagnosisRule";
import { urgencyMeta, getRuleDisplayName } from "@/types/diagnosisRule";

interface DiagnosisRuleTableProps {
  data: DiagnosisRule[];
  loading: boolean;
  onEdit: (rule: DiagnosisRule) => void;
  onToggleStatus: (rule: DiagnosisRule) => void;
  onDelete: (rule: DiagnosisRule) => void;
  sortKey: string | null;
  sortDirection: "asc" | "desc" | null;
  onSortChange: (key: string, direction: "asc" | "desc" | null) => void;
  sequenceStart: number;
}

const COLUMNS: { key: string; label: string; sortable?: boolean }[] = [
  { key: "sequence", label: "ลำดับ" },
  { key: "diseases", label: "โรคที่วินิจฉัย" },
  { key: "diagram", label: "แผนภูมิ" },
  { key: "urgency_level", label: "ความเร่งด่วน" },
  { key: "time_frame", label: "กรอบเวลา" },
  { key: "conditions", label: "เงื่อนไข" },
  { key: "status", label: "สถานะ" },
  { key: "actions", label: "" },
];

export function DiagnosisRuleTable({
  data,
  loading,
  onEdit,
  onToggleStatus,
  onDelete,
  sortKey,
  sortDirection,
  onSortChange,
  sequenceStart,
}: DiagnosisRuleTableProps) {
  function handleHeaderClick(key: string) {
    if (sortKey !== key) return onSortChange(key, "asc");
    if (sortDirection === "asc") return onSortChange(key, "desc");
    return onSortChange(key, null);
  }

  function SortIcon({ column }: { column: string }) {
    if (sortKey !== column) return <ArrowUpDown className="h-3.5 w-3.5 opacity-40" />;
    return sortDirection === "asc" ? (
      <ArrowUp className="h-3.5 w-3.5" />
    ) : (
      <ArrowDown className="h-3.5 w-3.5" />
    );
  }

  if (!loading && data.length === 0) {
    return (
      <div className="flex flex-col items-center justify-center gap-2 py-16 text-center">
        <GitBranch className="h-8 w-8 text-[var(--color-text-secondary)]" />
        <p className="text-sm text-[var(--color-text-secondary)]">
          ยังไม่มีกฎการวินิจฉัยที่ตรงกับเงื่อนไขการค้นหา
        </p>
      </div>
    );
  }

  return (
    <div className="overflow-x-auto">
      <table className="w-full text-sm">
        <thead>
          <tr className="border-b border-[var(--color-border)] text-left text-xs text-[var(--color-text-secondary)]">
            {COLUMNS.map((col) => (
              <th key={col.key} className="px-4 py-3 font-medium">
                {col.sortable ? (
                  <button
                    type="button"
                    onClick={() => handleHeaderClick(col.key)}
                    className="flex items-center gap-1 hover:text-[var(--color-text-primary)]"
                  >
                    {col.label}
                    <SortIcon column={col.key} />
                  </button>
                ) : (
                  col.label
                )}
              </th>
            ))}
          </tr>
        </thead>
        <tbody>
          {data.map((rule, index) => {
            const urgency = urgencyMeta(rule.urgency_level);
            return (
              <tr
                key={rule.rule_id}
                className="border-b border-[var(--color-border)] last:border-0 hover:bg-[var(--color-bg-subtle,#f8fafc)]"
              >
                <td className="px-4 py-3 text-[var(--color-text-secondary)]">
                  {sequenceStart + index}
                </td>
                <td className="px-4 py-3">
                  <p className="max-w-xs truncate font-medium text-[var(--color-text-primary)]">
                    {getRuleDisplayName(rule)}
                  </p>
                  {rule.medical_reference && (
                    <p className="max-w-xs truncate text-xs text-[var(--color-text-secondary)]">
                      {rule.medical_reference}
                    </p>
                  )}
                </td>
                <td className="px-4 py-3 text-[var(--color-text-secondary)]">
                  {rule.diagram?.diagram_name ?? "-"}
                </td>
                <td className="px-4 py-3">
                  <span
                    className="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium"
                    style={{ color: urgency.color, backgroundColor: urgency.bg }}
                  >
                    {urgency.label}
                  </span>
                </td>
                <td className="px-4 py-3 text-[var(--color-text-secondary)]">
                  {rule.time_frame ?? "-"}
                </td>
                <td className="px-4 py-3 text-[var(--color-text-secondary)]">
                  {rule.conditions?.length ?? 0} เงื่อนไข
                </td>
                <td className="px-4 py-3">
                  <button
                    type="button"
                    onClick={() => onToggleStatus(rule)}
                    className={`inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium ${
                      rule.status === "1"
                        ? "bg-green-100 text-green-700"
                        : "bg-gray-100 text-gray-500"
                    }`}
                  >
                    {rule.status === "1" ? "ใช้งานได้" : "ปิดใช้งาน"}
                  </button>
                </td>
                <td className="px-4 py-3">
                  <div className="flex items-center justify-end gap-1">
                    <button
                      type="button"
                      onClick={() => onEdit(rule)}
                      className="rounded-md p-1.5 text-[var(--color-text-secondary)] hover:bg-[var(--color-bg-subtle,#f1f5f9)] hover:text-[var(--color-primary)]"
                      aria-label="แก้ไข"
                    >
                      <Pencil className="h-4 w-4" />
                    </button>
                    <button
                      type="button"
                      onClick={() => onDelete(rule)}
                      className="rounded-md p-1.5 text-[var(--color-text-secondary)] hover:bg-red-50 hover:text-red-600"
                      aria-label="ลบ"
                    >
                      <Trash2 className="h-4 w-4" />
                    </button>
                  </div>
                </td>
              </tr>
            );
          })}
        </tbody>
      </table>
    </div>
  );
}
