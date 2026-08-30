import { api } from "@/lib/api";
import type { Symptom, SymptomCategory, SymptomFormValues } from "@/types/symptom";

export interface ListResponse<T> {
  data: T[];
  meta?: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
}

export interface SymptomListParams {
  search?: string;
  symptom_category_id?: string;
  symptom_category_ids?: string[];
  status?: string;
  page?: number;
  per_page?: number;
  sort_by?: string;
  sort_direction?: "asc" | "desc";
}

export const symptomApi = {
  list: (params: SymptomListParams, signal?: AbortSignal) =>
    api
      .get<ListResponse<Symptom>>("/admin/symptoms", { params, signal })
      .then((r) => r.data),

  show: (id: string) =>
    api.get<{ data: Symptom }>(`/admin/symptoms/${id}`).then((r) => r.data.data),

  create: (payload: SymptomFormValues) =>
    api
      .post<{ data: Symptom }>("/admin/symptoms", payload)
      .then((r) => r.data.data),

  update: (id: string, payload: Partial<SymptomFormValues>) =>
    api
      .put<{ data: Symptom }>(`/admin/symptoms/${id}`, payload)
      .then((r) => r.data.data),

  delete: (id: string) =>
    api.delete<{ message: string }>(`/admin/symptoms/${id}`).then((r) => r.data),
};

export const symptomCategoryApi = {
  listAll: () =>
    api
      .get<ListResponse<SymptomCategory>>("/admin/symptom-categories", {
        params: { per_page: 100 },
      })
      .then((r) => r.data.data),
};
