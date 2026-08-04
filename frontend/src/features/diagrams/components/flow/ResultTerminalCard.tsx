import { Plus } from "lucide-react";
import type { DiagnosisRule } from "@/types/diagnosisRule";
import { URGENCY_COLORS, getRuleDisplayName } from "@/types/diagnosisRule";

interface ResultTerminalCardProps {
  rules: DiagnosisRule[];
  onConfigure: () => void;
}

export function ResultTerminalCard({ rules, onConfigure }: ResultTerminalCardProps) {
  return (
    <div className="flex flex-col gap-1.5">
      {rules.length === 0 ? (
        <div className="rounded-lg border border-dashed border-[var(--color-border)] bg-white px-2.5 py-2 text-[11px] text-[var(--color-text-secondary)]">
          ยังไม่ได้กำหนดผลลัพธ์
        </div>
      ) : (
        rules.map((rule) => (
          <div
            key={rule.rule_id}
            className={`rounded-lg border px-2.5 py-1.5 text-[11px] font-medium ${URGENCY_COLORS[rule.urgency_level]}`}
          >
            {getRuleDisplayName(rule)}
            {(rule.next_diagrams?.length ?? 0) > 0 && (
              <div className="mt-1 font-normal">
                ประเมินต่อ: {rule.next_diagrams!.map((diagram) => diagram.diagram_name).join(", ")}
              </div>
            )}
          </div>
        ))
      )}

      <button
        type="button"
        onClick={onConfigure}
        className="flex items-center justify-center gap-1 rounded-lg border border-dashed border-[var(--color-primary)] px-2.5 py-1.5 text-[11px] font-medium text-[var(--color-primary)] hover:bg-[var(--color-primary)]/5"
      >
        <Plus className="h-3 w-3" />
        กำหนดผลลัพธ์
      </button>
    </div>
  );
}
