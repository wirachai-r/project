import type { ArticleCategory } from "./articleCategory";

export interface Article {
  article_id: string;
  title: string;
  title_en: string | null;
  content: string;
  content_en: string | null;
  thumbnail: string | null;
  status: "1" | "2" | "3"; // 1=Published, 2=Draft, 3=Archived
  published_at: string | null;
  article_category_id: string;
  category?: ArticleCategory;
  created_by: string | null;
  updated_by: string | null;
  created_at: string;
  updated_at: string;
}

export interface ArticleFormValues {
  title: string;
  title_en: string;
  content: string;
  content_en: string;
  thumbnail: string;
  status: "1" | "2" | "3";
  article_category_id: string;
}