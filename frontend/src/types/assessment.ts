import type { Symptom } from "./symptom";
import type { Disease } from "./disease";

export type UrgencyLevel = "R" | "P" | "Y" | "G" | "W";

export interface AssessmentResult {
  id: number;
  urgency_level: UrgencyLevel;
  should_see_doctor: "Y" | "N";
  recommendation: string | null;
  assessment_id: number;
  rule_id: string;
  disease_id: string;
  disease?: Pick<Disease, "disease_id" | "disease_name" | "disease_name_en">;
  created_at: string;
}

export interface Assessment {
  id: number;
  assessment_status: "P" | "C";
  started_at: string | null;
  completed_at: string | null;
  user_id: string | null;
  session_token: string | null;
  symptom_id: string;
  diagram_id: string;
  symptom?: Pick<Symptom, "symptom_id" | "symptom_name">;
  results?: AssessmentResult[];
  created_at: string;
  updated_at: string;
}