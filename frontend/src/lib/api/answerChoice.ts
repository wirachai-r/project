import { api, queryGet } from "@/lib/api";
import { resourceKeys } from "@/lib/queryClient";
import type { AnswerChoice, AnswerChoiceFormValues } from "@/types/answerChoice";

export const answerChoiceApi = {
  list: (boxId: string, status?: string, signal?: AbortSignal) =>
    queryGet<{ data: AnswerChoice[] }>(
      ["question-boxes", "detail", boxId, "answer-choices", status ?? "all"],
      `/admin/question-boxes/${boxId}/answer-choices`,
      { params: status ? { status } : undefined, signal },
      60_000,
    ).then((r) => r.data),

  show: (boxId: string, choiceId: string) =>
    queryGet<{ data: AnswerChoice }>(resourceKeys("answer-choices").detail(choiceId), `/admin/question-boxes/${boxId}/answer-choices/${choiceId}`, {}, 60_000).then((r) => r.data),

  create: (boxId: string, payload: AnswerChoiceFormValues) =>
    api
      .post<{ data: AnswerChoice }>(`/admin/question-boxes/${boxId}/answer-choices`, payload)
      .then((r) => r.data.data),

  update: (boxId: string, choiceId: string, payload: Partial<AnswerChoiceFormValues>) =>
    api
      .put<{ data: AnswerChoice }>(
        `/admin/question-boxes/${boxId}/answer-choices/${choiceId}`,
        payload,
      )
      .then((r) => r.data.data),

  delete: (boxId: string, choiceId: string) =>
    api
      .delete<{ message: string }>(`/admin/question-boxes/${boxId}/answer-choices/${choiceId}`)
      .then((r) => r.data),
};
