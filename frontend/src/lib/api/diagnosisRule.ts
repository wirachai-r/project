import { api } from "@/lib/api";
import type { DiagnosisRule, DiagnosisRuleFormValues } from "@/types/diagnosisRule";
import type { ListResponse } from "@/lib/api/diseaseCategory";

export interface DiagnosisRuleListParams {
  search?: string;
  status?: string;
  diagram_id?: string;
  disease_id?: string;
  urgency_level?: string;
  page?: number;
  per_page?: number;
  sort_by?: "urgency_level";
  sort_direction?: "asc" | "desc";
}

export const diagnosisRuleApi = {
  list: (params: DiagnosisRuleListParams, signal?: AbortSignal) =>
    api
      .get<ListResponse<DiagnosisRule>>("/admin/diagnosis-rules", { params, signal })
      .then((r) => r.data),

  show: (id: string) =>
    api.get<{ data: DiagnosisRule }>(`/admin/diagnosis-rules/${id}`).then((r) => r.data.data),

  create: (payload: DiagnosisRuleFormValues) =>
    api
      .post<{ data: DiagnosisRule }>("/admin/diagnosis-rules", payload)
      .then((r) => r.data.data),

  update: (id: string, payload: Partial<DiagnosisRuleFormValues>) =>
    api
      .put<{ data: DiagnosisRule }>(`/admin/diagnosis-rules/${id}`, payload)
      .then((r) => r.data.data),

  delete: (id: string) =>
    api.delete<{ message: string }>(`/admin/diagnosis-rules/${id}`).then((r) => r.data),
};
