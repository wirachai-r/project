import type { QuestionBox } from "@/types/questionBox";
import type { Diagram } from "@/types/diagram";

export interface AnswerChoice {
  choice_id: string;
  choice_text: string;
  choice_text_en: string | null;
  choice_image: string | null;
  order: number;
  status: "1" | "2";
  box_id: string;
  /** ใช้เฉพาะเมื่อ parent question_box.question_type === "S" — เป็น null เสมอถ้า type คือ M */
  next_box_id: string | null;
  next_box?: QuestionBox | null;
  /** ใช้เฉพาะเมื่อ parent question_box.question_type === "S" — เป็น null เสมอถ้า type คือ M */
  next_diagram_id: string | null;
  next_diagram?: Diagram | null;
  created_by: string | null;
  updated_by: string | null;
  created_at: string;
  updated_at: string;
}

export interface AnswerChoiceFormValues {
  choice_text: string;
  choice_text_en?: string | null;
  choice_image?: string | null;
  order?: number;
  status?: "1" | "2";
  /** ส่งได้เฉพาะ box type S เท่านั้น — ถ้า type เป็น M ห้ามส่ง (backend จะ reject) */
  next_box_id?: string | null;
  next_diagram_id?: string | null;
}