export interface SymptomCategory {
  symptom_category_id: string;
  category_name: string;
  category_name_en: string | null;
  description: string | null;
  icon: string | null;
  status: "1" | "2";
  created_at: string;
}

export interface Symptom {
  symptom_id: string;
  symptom_name: string;
  symptom_name_en: string | null;
  description: string | null;
  symptom_image: string | null;
  status: "1" | "2";
  symptom_category_id: string;
  category: Pick<SymptomCategory, "symptom_category_id" | "category_name"> | null;
  created_at: string;
  updated_at: string;
}