import { api, queryGet } from "@/lib/api";
import { resourceKeys } from "@/lib/queryClient";
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
    queryGet<ListResponse<DiseaseCategory>>(resourceKeys("disease-categories").list(params), "/admin/disease-categories", { params, signal }, 5 * 60_000),

  show: (id: string) =>
    queryGet<{ data: DiseaseCategory }>(resourceKeys("disease-categories").detail(id), `/admin/disease-categories/${id}`, {}, 15 * 60_000).then((r) => r.data),

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
