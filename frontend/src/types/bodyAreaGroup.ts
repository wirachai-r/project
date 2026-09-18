export interface BodyAreaGroup {
  id: number;
  name: string;
  name_en: string | null;
  description: string | null;
  image_url: string | null;
  display_order: number;
  status: "1" | "2";
  symptoms_count: number;
  symptom_ids: string[];
  subgroups: BodyAreaSubgroup[];
}

export interface BodyAreaSubgroup {
  id?: number;
  name: string;
  name_en: string;
  description: string;
  image_url?: string | null;
  image?: File | null;
  remove_image?: boolean;
  display_order?: number;
  status: "1" | "2";
  symptom_ids: string[];
}

export interface BodyAreaGroupForm {
  name: string;
  name_en: string;
  description: string;
  display_order: number;
  status: "1" | "2";
  symptom_ids: string[];
  image: File | null;
  remove_image?: boolean;
  subgroups: BodyAreaSubgroup[];
}
