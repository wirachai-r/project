import { api } from "@/lib/api";
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
    api
      .get<ListResponse<FirstAid>>("/admin/first-aids", { params, signal })
      .then((r) => r.data),

  show: (id: string) =>
    api
      .get<{ data: FirstAid }>(`/admin/first-aids/${id}`)
      .then((r) => r.data.data),

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
