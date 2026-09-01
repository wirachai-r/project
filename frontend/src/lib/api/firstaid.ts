import { api, queryGet } from "@/lib/api";
import { resourceKeys } from "@/lib/queryClient";
import type { FirstAid, FirstAidFormValues } from "@/types/firstaid";
import type { ListResponse } from "@/lib/api/diseaseCategory";

export interface FirstAidListParams {
  search?: string;
  status?: string;
  first_aid_category_id?: string;
  first_aid_category_ids?: string[];
  page?: number;
  per_page?: number;
  sort_by?: string;
  sort_direction?: "asc" | "desc";
}

export const firstAidApi = {
  list: (params: FirstAidListParams, signal?: AbortSignal) =>
    queryGet<ListResponse<FirstAid>>(resourceKeys("first-aids").list(params), "/admin/first-aids", { params, signal }, 5 * 60_000),

  show: (id: string) =>
    queryGet<{ data: FirstAid }>(resourceKeys("first-aids").detail(id), `/admin/first-aids/${id}`, {}, 5 * 60_000).then((r) => r.data),

  create: (payload: FirstAidFormValues) =>
    api
      .post<{ data: FirstAid }>("/admin/first-aids", payload)
      .then((r) => r.data.data),

  update: (id: string, payload: Partial<FirstAidFormValues>) =>
    api
      .put<{ data: FirstAid }>(`/admin/first-aids/${id}`, payload)
      .then((r) => r.data.data),

  delete: (id: string) =>
    api
      .delete<{ message: string }>(`/admin/first-aids/${id}`)
      .then((r) => r.data),
};
