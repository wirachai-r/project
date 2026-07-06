export interface DiseaseCategory {
  disease_category_id: string;
  category_name: string;
  category_name_en: string | null;
  description: string | null;
  icon: string | null;
  status: "1" | "2";
  diseases_count?: number;
}

export interface TreatmentOrder {
  order_id: string;
  order_name: string;
  order_name_en: string | null;
  description: string | null;
  urgency_type: "R" | "P" | "Y" | "G" | "W";
  order_sequence: number;
  status: "1" | "2";
  disease_id: string;
}

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
  category: Pick<DiseaseCategory, "disease_category_id" | "category_name"> | null;
  treatment_orders?: TreatmentOrder[];
  created_at: string;
  updated_at: string;
}