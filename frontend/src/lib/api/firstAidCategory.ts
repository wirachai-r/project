import { api } from "@/lib/api";
import type {
  FirstAidCategory,
  FirstAidCategoryFormValues,
} from "@/types/firstAidCategory";
import type { ListResponse } from "@/lib/api/diseaseCategory";

export interface FirstAidCategoryListParams {
  search?: string;
  status?: string;
  page?: number;
  per_page?: number;
  sort_by?: string;
  sort_direction?: "asc" | "desc";
}

export const firstAidCategoryApi = {
  list: (params: FirstAidCategoryListParams, signal?: AbortSignal) =>
    api
      .get<ListResponse<FirstAidCategory>>("/admin/first-aid-categories", {
        params,
        signal,
      })
      .then((r) => r.data),

  show: (id: string) =>
    api
      .get<{ data: FirstAidCategory }>(`/admin/first-aid-categories/${id}`)
      .then((r) => r.data.data),

  create: (payload: FirstAidCategoryFormValues) =>
    api
      .post<{ data: FirstAidCategory }>("/admin/first-aid-categories", payload)
      .then((r) => r.data.data),

  update: (id: string, payload: Partial<FirstAidCategoryFormValues>) =>
    api
      .put<{ data: FirstAidCategory }>(`/admin/first-aid-categories/${id}`, payload)
      .then((r) => r.data.data),

  delete: (id: string) =>
    api
      .delete<{ message: string }>(`/admin/first-aid-categories/${id}`)
      .then((r) => r.data),
};