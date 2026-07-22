import { api } from "@/lib/api";
import type {
  ArticleCategory,
  ArticleCategoryFormValues,
} from "@/types/articleCategory";
import type { ListResponse } from "@/lib/api/diseaseCategory";

export interface ArticleCategoryListParams {
  search?: string;
  status?: string;
  page?: number;
  per_page?: number;
  sort_by?: string;
  sort_direction?: "asc" | "desc";
}

export const articleCategoryApi = {
  list: (params: ArticleCategoryListParams, signal?: AbortSignal) =>
    api
      .get<ListResponse<ArticleCategory>>("/admin/article-categories", {
        params,
        signal,
      })
      .then((r) => r.data),

  show: (id: string) =>
    api
      .get<{ data: ArticleCategory }>(`/admin/article-categories/${id}`)
      .then((r) => r.data.data),

  create: (payload: ArticleCategoryFormValues) =>
    api
      .post<{ data: ArticleCategory }>("/admin/article-categories", payload)
      .then((r) => r.data.data),

  update: (id: string, payload: Partial<ArticleCategoryFormValues>) =>
    api
      .put<{ data: ArticleCategory }>(`/admin/article-categories/${id}`, payload)
      .then((r) => r.data.data),

  delete: (id: string) =>
    api
      .delete<{ message: string }>(`/admin/article-categories/${id}`)
      .then((r) => r.data),
};