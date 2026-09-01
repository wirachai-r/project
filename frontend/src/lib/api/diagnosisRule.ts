import { api, queryGet } from "@/lib/api";
import { resourceKeys } from "@/lib/queryClient";
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
  sort_by?: "urgency_level" | "created_at";
  sort_direction?: "asc" | "desc";
}

export const diagnosisRuleApi = {
  list: (params: DiagnosisRuleListParams, signal?: AbortSignal) =>
    queryGet<ListResponse<DiagnosisRule>>(resourceKeys("diagnosis-rules").list(params), "/admin/diagnosis-rules", { params, signal }, 60_000),

  show: (id: string) =>
    queryGet<{ data: DiagnosisRule }>(resourceKeys("diagnosis-rules").detail(id), `/admin/diagnosis-rules/${id}`, {}, 60_000).then((r) => r.data),

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
