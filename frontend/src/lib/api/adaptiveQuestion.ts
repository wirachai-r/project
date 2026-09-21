import { api, queryGet } from "@/lib/api";
import { resourceKeys } from "@/lib/queryClient";
import type { AdaptiveQuestion, AdaptiveQuestionPayload } from "@/types/adaptiveQuestion";

const base = "/admin/adaptive-questions";

export const adaptiveQuestionApi = {
  list: () => queryGet<{ data: AdaptiveQuestion[] }>(resourceKeys("adaptive-questions").list({}), base).then((response) => response.data),
  create: (payload: AdaptiveQuestionPayload) => api.post<{ data: AdaptiveQuestion }>(base, payload).then((response) => response.data.data),
  update: (id: number, payload: AdaptiveQuestionPayload) => api.put<{ data: AdaptiveQuestion }>(`${base}/${id}`, payload).then((response) => response.data.data),
  delete: (id: number) => api.delete(`${base}/${id}`).then((response) => response.data),
};
