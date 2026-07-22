export interface SymptomCategory {
  symptom_category_id: string;
  category_name: string;
  category_name_en: string | null;
  description: string | null;
  icon: string | null;
  status: "1" | "2";
}

export interface Symptom {
  symptom_id: string;
  symptom_name: string;
  symptom_name_en: string | null;
  description: string | null;
  symptom_image: string | null;
  status: "1" | "2";
  symptom_category_id: string;
  category?: SymptomCategory;
  created_by: string | null;
  updated_by: string | null;
  created_at: string;
  updated_at: string;
}

export interface SymptomFormValues {
  symptom_name: string;
  symptom_name_en: string;
  description: string;
  symptom_image: string;
  symptom_category_id: string;
  status: "1" | "2";
}