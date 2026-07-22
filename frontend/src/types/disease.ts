import type { DiseaseCategory } from "@/types/diseaseCategory";

export interface Disease {
  disease_id: string;
  disease_name: string;
  disease_name_en: string | null;
  description: string | null;
  cause: string | null;
  symptom_description: string | null;
  prevention: string | null;
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
  prevention: string;
  disease_image: string;
  status: "1" | "2";
  disease_category_id: string;
}