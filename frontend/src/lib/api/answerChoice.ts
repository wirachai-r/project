import { api } from "@/lib/api";
import type { AnswerChoice, AnswerChoiceFormValues } from "@/types/answerChoice";

export const answerChoiceApi = {
  list: (boxId: string, status?: string, signal?: AbortSignal) =>
    api
      .get<{ data: AnswerChoice[] }>(`/admin/question-boxes/${boxId}/answer-choices`, {
        params: status ? { status } : undefined,
        signal,
      })
      .then((r) => r.data.data),

  show: (boxId: string, choiceId: string) =>
    api
      .get<{ data: AnswerChoice }>(`/admin/question-boxes/${boxId}/answer-choices/${choiceId}`)
      .then((r) => r.data.data),

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