export type ArticleCommentReport = {
  id: number;
  reason: string;
  details?: string | null;
  status: "pending" | "resolved" | "dismissed";
  created_at: string;
  comment?: {
    id: number;
    article_id: string;
    content: string;
    user?: { first_name?: string; last_name?: string };
    article?: { article_id: string; title: string };
  };
  reporter?: { first_name?: string; last_name?: string };
};

export type ArticleCommentReportFilters = {
  search: string;
  status: string;
  reason: string;
  sortDirection: "asc" | "desc";
};

export const ARTICLE_COMMENT_REPORT_REASONS: Record<string, string> = {
  spam: "สแปม",
  inappropriate: "เนื้อหาไม่เหมาะสม",
  misleading: "ข้อมูลสุขภาพที่อาจทำให้เข้าใจผิด",
  harassment: "คุกคามหรือใช้ถ้อยคำไม่สุภาพ",
  other: "อื่น ๆ",
};
