import { api } from "@/lib/api";
import type { Diagram, DiagramFormValues } from "@/types/diagram";
import type { ListResponse } from "@/lib/api/diseaseCategory";

export interface DiagramListParams {
  search?: string;
  status?: string;
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
    api
      .get<ListResponse<Diagram>>("/admin/diagrams", { params, signal })
      .then((r) => r.data),

    show: (id: string, signal?: AbortSignal) =>
    api
      .get<{ data: Diagram }>(`/admin/diagrams/${id}`, { signal })
      .then((r) => r.data.data),

  create: (payload: DiagramFormValues) =>
    api
      .post<{ data: Diagram }>("/admin/diagrams", payload)
      .then((r) => r.data.data),

  update: (id: string, payload: DiagramUpdatePayload) =>
    api
      .put<{ data: Diagram }>(`/admin/diagrams/${id}`, payload)
      .then((r) => r.data.data),

  delete: (id: string) =>
    api.delete<{ message: string }>(`/admin/diagrams/${id}`).then((r) => r.data),
};