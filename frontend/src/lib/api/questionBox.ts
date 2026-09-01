import { api, queryGet } from "@/lib/api";
import { resourceKeys } from "@/lib/queryClient";
import type { QuestionBox, QuestionBoxFormValues } from "@/types/questionBox";
import type { ListResponse } from "@/lib/api/diseaseCategory";

export interface QuestionBoxListParams {
  status?: string;
  page?: number;
  per_page?: number;
}

export const questionBoxApi = {
  list: (diagramId: string, params: QuestionBoxListParams = {}, signal?: AbortSignal) =>
    queryGet<ListResponse<QuestionBox>>(
      ["diagrams", "detail", diagramId, "question-boxes", params],
      `/admin/diagrams/${diagramId}/question-boxes`,
      { params, signal },
      60_000,
    ),

  show: (diagramId: string, boxId: string) =>
    queryGet<{ data: QuestionBox }>(resourceKeys("question-boxes").detail(boxId), `/admin/diagrams/${diagramId}/question-boxes/${boxId}`, {}, 60_000).then((r) => r.data),

  create: (diagramId: string, payload: QuestionBoxFormValues) =>
    api
      .post<{ data: QuestionBox }>(`/admin/diagrams/${diagramId}/question-boxes`, payload)
      .then((r) => r.data.data),

  update: (diagramId: string, boxId: string, payload: Partial<QuestionBoxFormValues>) =>
    api
      .put<{ data: QuestionBox }>(
        `/admin/diagrams/${diagramId}/question-boxes/${boxId}`,
        payload,
      )
      .then((r) => r.data.data),

  delete: (diagramId: string, boxId: string) =>
    api
      .delete<{ message: string }>(`/admin/diagrams/${diagramId}/question-boxes/${boxId}`)
      .then((r) => r.data),
};
