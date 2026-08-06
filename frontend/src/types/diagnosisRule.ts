export type UrgencyLevel = "R" | "P" | "Y" | "G" | "W";
export type LogicOperator = "AND" | "OR";

export const URGENCY_OPTIONS: { label: string; value: UrgencyLevel }[] = [
  { label: "แดง (Red) - ฉุกเฉินวิกฤต", value: "R" },
  { label: "ชมพู (Pink) - ฉุกเฉินเร่งด่วน", value: "P" },
  { label: "เหลือง (Yellow) - เร่งด่วน", value: "Y" },
  { label: "เขียว (Green) - ไม่เร่งด่วน", value: "G" },
  { label: "ขาว (White) - ทั่วไป", value: "W" },
];

export const URGENCY_COLORS: Record<UrgencyLevel, string> = {
  R: "bg-red-100 text-red-700 border-red-200",
  P: "bg-pink-100 text-pink-700 border-pink-200",
  Y: "bg-yellow-100 text-yellow-700 border-yellow-200",
  G: "bg-green-100 text-green-700 border-green-200",
  W: "bg-gray-100 text-gray-600 border-gray-200",
};

export interface RuleDisease {
  disease_id: string;
  disease_name: string;
  reference?: string | null;
  order: number;
}

export interface RuleNextDiagram {
  diagram_id: string;
  diagram_name: string;
  prompt_text?: string | null;
  order: number;
}

export interface RuleCondition {
  condition_id: string;
  status: "1" | "2";
  rule_id: string;
  box_id: string;
  choice_id: string;
  logic_operator: LogicOperator;
  question_box?: { box_id: string; question_text: string };
  answer_choice?: { choice_id: string; choice_text: string };
}

export interface DiagnosisRule {
  rule_id: string;
  threshold_outcome?: "yes" | "no" | null;
  threshold_box_id?: string | null;
  medical_reference: string | null;
  urgency_level: UrgencyLevel;
  time_frame: string | null;
  time_frame_en: string | null;
  note: string | null;
  note_en: string | null;
  status: "1" | "2";
  diagram_id: string;
  pos_x: number | null;
  pos_y: number | null;
  diagram?: { diagram_id: string; diagram_name: string };
  diseases?: RuleDisease[];
  next_diagrams?: RuleNextDiagram[];
  conditions?: RuleCondition[];
  created_by: string | null;
  updated_by: string | null;
  created_at: string;
  updated_at: string;
}

export interface RuleConditionInput {
  status?: "1" | "2";
  box_id: string;
  choice_id: string;
  logic_operator?: LogicOperator;
}

export interface DiagnosisRuleFormValues {
  medical_reference: string;
  urgency_level: UrgencyLevel;
  time_frame: string;
  time_frame_en: string;
  note: string;
  note_en: string;
  status: "1" | "2";
  diagram_id: string;
  threshold_outcome?: "yes" | "no" | null;
  threshold_box_id?: string | null;
  disease_ids: string[];
  next_diagrams?: Array<{
    diagram_id: string;
    prompt_text?: string | null;
    display_order?: number;
  }>;
  conditions: RuleConditionInput[];
  pos_x?: number | null;
  pos_y?: number | null;
}

export const EMPTY_DIAGNOSIS_RULE_FORM: DiagnosisRuleFormValues = {
  medical_reference: "",
  urgency_level: "G",
  time_frame: "",
  time_frame_en: "",
  note: "",
  note_en: "",
  status: "1",
  diagram_id: "",
  disease_ids: [],
  next_diagrams: [],
  conditions: [],
};

// ใช้แสดงชื่อ node/แถวตาราง แทน rule_name ที่ไม่มีแล้ว
export function getRuleDisplayName(rule: DiagnosisRule): string {
  if (rule.diseases && rule.diseases.length > 0) {
    return rule.diseases.map((d) => d.disease_name).join(", ");
  }
  return rule.time_frame ? `ไม่ระบุโรค (${rule.time_frame})` : "ไม่ระบุโรค";
}
