import { api, queryGet } from "@/lib/api";
import { resourceKeys } from "@/lib/queryClient";
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
    queryGet<ListResponse<Symptom>>(resourceKeys("symptoms").list(params), "/admin/symptoms", { params, signal }, 5 * 60_000),

  listAll: async (params: Omit<SymptomListParams, "page" | "per_page"> = {}) => {
    const firstParams = { ...params, page: 1, per_page: 100 };
    const first = await queryGet<ListResponse<Symptom>>(
      resourceKeys("symptoms").list(firstParams),
      "/admin/symptoms",
      { params: firstParams },
      5 * 60_000,
    );
    const lastPage = first.meta?.last_page ?? 1;
    if (lastPage === 1) return first.data;

    const remaining = await Promise.all(
      Array.from({ length: lastPage - 1 }, (_, index) => {
        const pageParams = { ...params, page: index + 2, per_page: 100 };
        return queryGet<ListResponse<Symptom>>(
          resourceKeys("symptoms").list(pageParams),
          "/admin/symptoms",
          { params: pageParams },
          5 * 60_000,
        );
      }),
    );

    return [first, ...remaining].flatMap((response) => response.data);
  },

  show: (id: string) =>
    queryGet<{ data: Symptom }>(resourceKeys("symptoms").detail(id), `/admin/symptoms/${id}`, {}, 5 * 60_000).then((r) => r.data),

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
    queryGet<ListResponse<SymptomCategory>>(
      resourceKeys("symptom-categories").list({ per_page: 100 }),
      "/admin/symptom-categories",
      { params: { per_page: 100 } },
      30 * 60_000,
    ).then((r) => r.data),
};
