import type { DiseaseCategory } from "@/types/diseaseCategory";

export interface Disease {
  disease_id: string;
  disease_name: string;
  disease_name_en: string | null;
  description: string | null;
  cause: string | null;
  symptom_description: string | null;
  complications: string | null;
  diagnosis: string | null;
  medical_treatment: string | null;
  self_care: string | null;
  when_to_see_doctor: string | null;
  prevention: string | null;
  recommendations: string | null;
  disease_image: string | null;
  status: "1" | "2";
  disease_category_id: string;
  category?: DiseaseCategory | null;
  created_by: string | null;
  updated_by: string | null;
  created_at: string;
  updated_at: string;
}

export interface DiseaseFormValues {
  disease_name: string;
  disease_name_en: string;
  description: string;
  cause: string;
  symptom_description: string;
  complications: string;
  diagnosis: string;
  medical_treatment: string;
  self_care: string;
  when_to_see_doctor: string;
  prevention: string;
  recommendations: string;
  disease_image: string;
  status: "1" | "2";
  disease_category_id: string;
}
