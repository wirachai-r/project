import { api, queryGet } from "@/lib/api";
import type { Diagram, DiagramFormValues } from "@/types/diagram";
import type { ListResponse } from "@/lib/api/diseaseCategory";
import { queryClient, queryKeys, resourceKeys } from "@/lib/queryClient";

export interface DiagramListParams {
  search?: string;
  status?: string;
  symptom_ids?: string[];
  symptom_id?: string;
  page?: number;
  per_page?: number;
  sort_by?: string;
  sort_direction?: "asc" | "desc";
}

type DiagramUpdatePayload = Partial<DiagramFormValues> & {
  entry_box_id?: string | null;
};

export const diagramApi = {
  list: (params: DiagramListParams, signal?: AbortSignal) =>
    queryGet<ListResponse<Diagram>>(resourceKeys("diagrams").list(params), "/admin/diagrams", { params, signal }, 2 * 60_000),

    show: (id: string, signal?: AbortSignal) =>
    queryGet<{ data: Diagram }>(resourceKeys("diagrams").detail(id), `/admin/diagrams/${id}`, { signal }, 2 * 60_000).then((r) => r.data),

  create: (payload: DiagramFormValues) =>
    api
      .post<{ data: Diagram }>("/admin/diagrams", payload)
      .then((r) => {
        void queryClient.invalidateQueries({ queryKey: queryKeys.lookups.activeDiagrams });
        return r.data.data;
      }),

  update: (id: string, payload: DiagramUpdatePayload) =>
    api
      .put<{ data: Diagram }>(`/admin/diagrams/${id}`, payload)
      .then((r) => {
        void queryClient.invalidateQueries({ queryKey: queryKeys.lookups.activeDiagrams });
        return r.data.data;
      }),

  delete: (id: string) =>
    api.delete<{ message: string }>(`/admin/diagrams/${id}`).then((r) => {
      void queryClient.invalidateQueries({ queryKey: queryKeys.lookups.activeDiagrams });
      return r.data;
    }),
};
