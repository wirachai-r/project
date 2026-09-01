export type FollowUpAnswerType =
  | "boolean"
  | "single_choice"
  | "multiple_choice"
  | "scale"
  | "number"
  | "text"
  | "date"
  | "time";

export interface FollowUpQuestionTemplate {
  id: number;
  question_text: string;
  description: string | null;
  answer_type: FollowUpAnswerType;
  options: string[] | null;
  unit: string | null;
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
  is_required: boolean;
  applies_to_all_symptoms: boolean;
  status: "0" | "1";
  symptoms: Array<{ symptom_id: string; sequence: number; is_required: boolean | null }>;
}
