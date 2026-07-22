import type { FirstAidCategory } from "./firstAidCategory";

export interface FirstAid {
  first_aid_id: string;
  title: string;
  title_en: string | null;
  content: string;
  content_en: string | null;
  thumbnail: string | null;
  status: "1" | "2" | "3"; // 1=Published, 2=Draft, 3=Archived
  published_at: string | null;
  first_aid_category_id: string;
  category?: FirstAidCategory;
  created_by: string | null;
  updated_by: string | null;
  created_at: string;
  updated_at: string;
}

export interface FirstAidFormValues {
  title: string;
  title_en: string;
  content: string;
  content_en: string;
  thumbnail: string;
  status: "1" | "2" | "3";
  first_aid_category_id: string;
}