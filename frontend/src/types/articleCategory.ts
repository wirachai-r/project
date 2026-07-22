export interface ArticleCategory {
  article_category_id: string;
  category_name: string;
  category_name_en?: string | null;
  description?: string | null;
  icon?: string | null;
  status: "1" | "2";
  articles_count?: number;
  created_by?: string | null;
  updated_by?: string | null;
  created_at?: string;
  updated_at?: string;
}

export interface ArticleCategoryFormValues {
  category_name: string;
  category_name_en: string;
  description: string;
  icon: string;
  status: "1" | "2";
}