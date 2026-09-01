import { api, queryGet } from "@/lib/api";
import type { Disease, DiseaseFormValues } from "@/types/disease";
import type { ListResponse } from "@/lib/api/diseaseCategory";
import { queryClient, queryKeys, resourceKeys } from "@/lib/queryClient";

export interface DiseaseListParams {
  search?: string;
  status?: string;
  disease_category_id?: string;
  disease_category_ids?: string[];
  page?: number;
  per_page?: number;
  sort_by?: string;
  sort_direction?: "asc" | "desc";
}

export const diseaseApi = {
  list: (params: DiseaseListParams, signal?: AbortSignal) =>
    queryGet<ListResponse<Disease>>(resourceKeys("diseases").list(params), "/admin/diseases", { params, signal }, 5 * 60_000),

  show: (id: string) =>
    queryGet<{ data: Disease }>(resourceKeys("diseases").detail(id), `/admin/diseases/${id}`, {}, 5 * 60_000).then((r) => r.data),

  create: (payload: DiseaseFormValues) =>
    api
      .post<{ data: Disease }>("/admin/diseases", payload)
      .then((r) => {
        void queryClient.invalidateQueries({ queryKey: queryKeys.lookups.activeDiseases });
        return r.data.data;
      }),

  update: (id: string, payload: Partial<DiseaseFormValues>) =>
    api
      .put<{ data: Disease }>(`/admin/diseases/${id}`, payload)
      .then((r) => {
        void queryClient.invalidateQueries({ queryKey: queryKeys.lookups.activeDiseases });
        return r.data.data;
      }),

  delete: (id: string) =>
    api.delete<{ message: string }>(`/admin/diseases/${id}`).then((r) => {
      void queryClient.invalidateQueries({ queryKey: queryKeys.lookups.activeDiseases });
      return r.data;
    }),
};
