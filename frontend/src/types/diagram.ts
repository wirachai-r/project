import type { Symptom } from "@/types/symptom";
import type { QuestionBox } from "@/types/questionBox";

export interface Diagram {
  diagram_id: string;
  diagram_name: string;
  diagram_name_en: string | null;
  description: string | null;
  status: "1" | "2";
  entry_box_id: string | null;
  symptoms?: Symptom[];
  entry_box?: QuestionBox | null;
  question_boxes?: QuestionBox[];
  created_by: string | null;
  updated_by: string | null;
  created_at: string;
  updated_at: string;
}

export interface DiagramFormValues {
  diagram_name: string;
  diagram_name_en: string;
  description: string;
  status: "1" | "2";
  symptom_ids: string[];
}

export const EMPTY_DIAGRAM_FORM: DiagramFormValues = {
  diagram_name: "",
  diagram_name_en: "",
  description: "",
  status: "1",
  symptom_ids: [],
};