import type { AnswerChoice } from "@/types/answerChoice";

export type QuestionType = "S" | "M";

export interface QuestionBox {
  box_id: string;
  question_text: string;
  question_text_en: string | null;
  question_image: string | null;
  question_type: QuestionType;

  /** ใช้เฉพาะเมื่อ question_type === "M" */
  min_required: number | null;
  yes_next_box_id: string | null;
  yes_next_diagram_id: string | null;
  no_next_box_id: string | null;
  no_next_diagram_id: string | null;

  // ➕ เพิ่มคุณสมบัติรายละเอียดที่จะส่งกลับมาจาก API
  detail: string | null;

  status: "1" | "2";
  diagram_id: string;
  choices?: AnswerChoice[];
  yes_next_box?: QuestionBox | null;
  no_next_box?: QuestionBox | null;
  created_by: string | null;
  updated_by: string | null;
  created_at: string;
  updated_at: string;
}

export interface QuestionBoxFormValues {
  question_text: string;
  question_text_en?: string | null;
  question_image?: string | null;
  question_type?: QuestionType;
  status?: "1" | "2";

  min_required?: number | null;
  yes_next_box_id?: string | null;
  yes_next_diagram_id?: string | null;
  no_next_box_id?: string | null;
  no_next_diagram_id?: string | null;

  // ➕ เพิ่มคุณสมบัติสำหรับรองรับการกรอกข้อมูลและส่งผ่านฟอร์ม
  detail?: string | null;
}