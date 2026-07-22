import { api } from "@/lib/api";
import type {
  DiseaseCategory,
  DiseaseCategoryFormValues,
} from "@/types/diseaseCategory";

export interface ListResponse<T> {
  data: T[];
  meta?: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
}

export interface DiseaseCategoryListParams {
  search?: string;
  status?: string;
  page?: number;
  per_page?: number;
  sort_by?: string;
  sort_direction?: "asc" | "desc";
}

export const diseaseCategoryApi = {
  list: (params: DiseaseCategoryListParams, signal?: AbortSignal) =>
    api
      .get<ListResponse<DiseaseCategory>>("/admin/disease-categories", {
        params,
        signal,
      })
      .then((r) => r.data),

  show: (id: string) =>
    api
      .get<{ data: DiseaseCategory }>(`/admin/disease-categories/${id}`)
      .then((r) => r.data.data),

  create: (payload: DiseaseCategoryFormValues) =>
    api
      .post<{ data: DiseaseCategory }>("/admin/disease-categories", payload)
      .then((r) => r.data.data),

  update: (id: string, payload: Partial<DiseaseCategoryFormValues>) =>
    api
      .put<{ data: DiseaseCategory }>(
        `/admin/disease-categories/${id}`,
        payload,
      )
      .then((r) => r.data.data),

  delete: (id: string) =>
    api
      .delete<{ message: string }>(`/admin/disease-categories/${id}`)
      .then((r) => r.data),
};