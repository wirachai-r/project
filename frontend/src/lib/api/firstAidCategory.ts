import { api, queryGet } from "@/lib/api";
import { resourceKeys } from "@/lib/queryClient";
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
    queryGet<ListResponse<FirstAidCategory>>(resourceKeys("first-aid-categories").list(params), "/admin/first-aid-categories", { params, signal }, 5 * 60_000),

  show: (id: string) =>
    queryGet<{ data: FirstAidCategory }>(resourceKeys("first-aid-categories").detail(id), `/admin/first-aid-categories/${id}`, {}, 15 * 60_000).then((r) => r.data),

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
