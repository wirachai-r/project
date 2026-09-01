import { api, queryGet } from "@/lib/api";
import { resourceKeys } from "@/lib/queryClient";
import type {
  SymptomCategory,
  SymptomCategoryFormValues,
} from "@/types/symptomCategory";

export interface ListResponse<T> {
  data: T[];
  meta?: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
}

export interface SymptomCategoryListParams {
  search?: string;
  status?: string;
  page?: number;
  per_page?: number;
  sort_by?: string;
  sort_direction?: "asc" | "desc";
}

export const symptomCategoryApi = {
  list: (params: SymptomCategoryListParams, signal?: AbortSignal) =>
    queryGet<ListResponse<SymptomCategory>>(resourceKeys("symptom-categories").list(params), "/admin/symptom-categories", { params, signal }, 5 * 60_000),

  show: (id: string) =>
    queryGet<{ data: SymptomCategory }>(resourceKeys("symptom-categories").detail(id), `/admin/symptom-categories/${id}`, {}, 15 * 60_000).then((r) => r.data),

  create: (payload: SymptomCategoryFormValues) =>
    api
      .post<{ data: SymptomCategory }>("/admin/symptom-categories", payload)
      .then((r) => r.data.data),

  update: (id: string, payload: Partial<SymptomCategoryFormValues>) =>
    api
      .put<{ data: SymptomCategory }>(
        `/admin/symptom-categories/${id}`,
        payload,
      )
      .then((r) => r.data.data),

  delete: (id: string) =>
    api
      .delete<{ message: string }>(`/admin/symptom-categories/${id}`)
      .then((r) => r.data),
};
