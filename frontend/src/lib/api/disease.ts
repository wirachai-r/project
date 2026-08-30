import { api } from "@/lib/api";
import type { Disease, DiseaseFormValues } from "@/types/disease";
import type { ListResponse } from "@/lib/api/diseaseCategory";

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
    api
      .get<ListResponse<Disease>>("/admin/diseases", { params, signal })
      .then((r) => r.data),

  show: (id: string) =>
    api.get<{ data: Disease }>(`/admin/diseases/${id}`).then((r) => r.data.data),

  create: (payload: DiseaseFormValues) =>
    api
      .post<{ data: Disease }>("/admin/diseases", payload)
      .then((r) => r.data.data),

  update: (id: string, payload: Partial<DiseaseFormValues>) =>
    api
      .put<{ data: Disease }>(`/admin/diseases/${id}`, payload)
      .then((r) => r.data.data),

  delete: (id: string) =>
    api.delete<{ message: string }>(`/admin/diseases/${id}`).then((r) => r.data),
};
