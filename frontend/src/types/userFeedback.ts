export type UserFeedback = {
  id: number;
  feedback_type: "general" | "content_error" | "assessment";
  target_type?: string | null;
  target_id?: string | null;
  target_name?: string | null;
  rating?: number | null;
  category?: string | null;
  message: string;
  status: "pending" | "in_review" | "resolved" | "dismissed";
  created_at: string;
  updated_at: string;
  user?: { first_name?: string; last_name?: string; email?: string };
};

export type UserFeedbackFilterValue = {
  search: string;
  status: string;
  feedbackType: string;
  sortDirection: "asc" | "desc";
};

export const FEEDBACK_TYPE_LABELS: Record<string, string> = {
  general: "ความคิดเห็นทั่วไป",
  content_error: "รายงานข้อมูลผิด",
  assessment: "รายงานผลประเมิน",
};
