import type {
  DiagnosisRule,
  DiagnosisRuleFormValues,
  RuleCondition,
  RuleConditionInput,
  RuleDisease,
  UrgencyLevel,
  LogicOperator,
} from "@/types/diagnosisRule";
import {
  URGENCY_OPTIONS,
  EMPTY_DIAGNOSIS_RULE_FORM,
  getRuleDisplayName,
} from "@/types/diagnosisRule";

export type {
  DiagnosisRule,
  DiagnosisRuleFormValues,
  RuleCondition,
  RuleConditionInput,
  RuleDisease,
  UrgencyLevel,
  LogicOperator,
};
export { URGENCY_OPTIONS, EMPTY_DIAGNOSIS_RULE_FORM, getRuleDisplayName };

// เงื่อนไขระหว่างแก้ไขในฟอร์ม ต้องมี key ไว้ผูกกับปุ่มบนกราฟ
export interface ConditionDraft extends RuleConditionInput {
  key: string;
}

export function makeConditionKey(boxId: string, choiceId: string) {
  return `${boxId}::${choiceId}`;
}

const URGENCY_META: Record<UrgencyLevel, { color: string; bg: string }> = {
  R: { color: "#b91c1c", bg: "#fee2e2" },
  P: { color: "#be185d", bg: "#fce7f3" },
  Y: { color: "#a16207", bg: "#fef9c3" },
  G: { color: "#15803d", bg: "#dcfce7" },
  W: { color: "#374151", bg: "#f3f4f6" },
};

export function urgencyMeta(level: UrgencyLevel) {
  const opt = URGENCY_OPTIONS.find((u) => u.value === level);
  return { label: opt?.label ?? level, ...URGENCY_META[level] };
}