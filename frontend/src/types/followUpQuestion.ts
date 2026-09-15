export type FollowUpAnswerType =
  | "boolean"
  | "single_choice"
  | "multiple_choice"
  | "scale"
  | "number"
  | "text"
  | "date"
  | "time";

export type FollowUpResponseAction = "prompt_end_tracking" | "prompt_add_symptom" | "show_alert";
export type FollowUpResponseOperator =
  | "equals" | "not_equals" | "greater_than" | "greater_than_or_equal"
  | "less_than" | "less_than_or_equal" | "between"
  | "contains" | "not_contains" | "is_empty" | "is_not_empty";

export interface FollowUpResponseRule {
  operator?: FollowUpResponseOperator;
  value: string | boolean | number | null;
  value_to?: string | number | null;
  action: FollowUpResponseAction;
  alert_level?: "info" | "warning" | "important";
  title?: string | null;
  message?: string | null;
  requires_acknowledgement?: boolean;
}

export interface FollowUpQuestionTemplate {
  id: number;
  question_text: string;
  description: string | null;
  answer_type: FollowUpAnswerType;
  options: string[] | null;
  unit: string | null;
  response_rules: FollowUpResponseRule[] | null;
  is_required: boolean;
  applies_to_all_symptoms: boolean;
  status: "0" | "1";
  symptoms: Array<{ symptom_id: string; symptom_name: string }>;
}

export interface FollowUpQuestionPayload {
  question_text: string;
  description: string;
  answer_type: FollowUpAnswerType;
  options: string[] | null;
  unit: string | null;
  response_rules: FollowUpResponseRule[] | null;
  is_required: boolean;
  applies_to_all_symptoms: boolean;
  status: "0" | "1";
  symptoms: Array<{ symptom_id: string; sequence: number; is_required: boolean | null }>;
}
