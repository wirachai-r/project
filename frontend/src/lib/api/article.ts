import { api } from "@/lib/api";
import type { Article, ArticleFormValues } from "@/types/article";
import type { ListResponse } from "@/lib/api/diseaseCategory";

export interface ArticleListParams {
  search?: string;
  status?: string;
  article_category_id?: string;
  page?: number;
  per_page?: number;
  sort_by?: string;
  sort_direction?: "asc" | "desc";
}

export const articleApi = {
  list: (params: ArticleListParams, signal?: AbortSignal) =>
    api
      .get<ListResponse<Article>>("/admin/articles", { params, signal })
      .then((r) => r.data),

  show: (id: string) =>
    api.get<{ data: Article }>(`/admin/articles/${id}`).then((r) => r.data.data),

  create: (payload: ArticleFormValues) =>
    api
      .post<{ data: Article }>("/admin/articles", payload)
      .then((r) => r.data.data),

  update: (id: string, payload: Partial<ArticleFormValues>) =>
    api
      .put<{ data: Article }>(`/admin/articles/${id}`, payload)
      .then((r) => r.data.data),

  delete: (id: string) =>
    api.delete<{ message: string }>(`/admin/articles/${id}`).then((r) => r.data),
};