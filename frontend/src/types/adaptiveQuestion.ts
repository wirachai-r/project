export type AdaptiveAnswerType = "yes_no_unsure" | "single_choice" | "multiple_choice";
export type AdaptiveQuestionStatus = "draft" | "approved" | "inactive";
export type AdaptiveQuestionStage = "local" | "associated" | "safety";
export type AdaptiveAnswerEffect = "present" | "absent" | "unknown";

export interface AdaptiveQuestionOption {
  id?: number;
  option_text: string;
  option_value: string;
  target_symptom_id: string | null;
  answer_effect: AdaptiveAnswerEffect;
  display_order: number;
  status?: "0" | "1";
}

export interface AdaptiveQuestionRule {
  id?: number;
  initial_symptom_id: string;
  question_stage: AdaptiveQuestionStage;
  priority: number;
  is_required: boolean;
  status?: "0" | "1";
}

export interface AdaptiveQuestionPayload {
  question_symptom_id: string;
  question_text: string;
  explanation_text: string;
  answer_type: AdaptiveAnswerType;
  status: AdaptiveQuestionStatus;
  evidence_source: string;
  options: AdaptiveQuestionOption[];
  rules: AdaptiveQuestionRule[];
}

export interface AdaptiveQuestion extends AdaptiveQuestionPayload {
  id: number;
  symptom?: { symptom_id: string; symptom_name: string };
  approved_at?: string | null;
}
