import { api, queryGet } from "@/lib/api";
import { resourceKeys } from "@/lib/queryClient";
import type { FollowUpQuestionPayload, FollowUpQuestionTemplate } from "@/types/followUpQuestion";

const base = "/admin/follow-up-question-templates";

export const followUpQuestionApi = {
  list: () => queryGet<{ data: FollowUpQuestionTemplate[] }>(resourceKeys("follow-up-question-templates").list({}), base).then((r) => r.data),
  create: (payload: FollowUpQuestionPayload) => api.post(base, payload).then((r) => r.data.data),
  update: (id: number, payload: FollowUpQuestionPayload) => api.put(`${base}/${id}`, payload).then((r) => r.data.data),
  delete: (id: number) => api.delete(`${base}/${id}`).then((r) => r.data),
};
