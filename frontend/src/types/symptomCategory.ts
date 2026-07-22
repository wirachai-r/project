export interface SymptomCategory {
  symptom_category_id: string;
  category_name: string;
  category_name_en: string | null;
  description: string | null;
  icon: string | null;
  status: "1" | "2";
  symptoms_count?: number;
  created_by: string | null;
  updated_by: string | null;
  created_at: string;
  updated_at: string;
}

export interface SymptomCategoryFormValues {
  category_name: string;
  category_name_en: string;
  description: string;
  icon: string;
  status: "1" | "2";
}