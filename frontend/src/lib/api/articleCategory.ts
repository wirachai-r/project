import { api, queryGet } from "@/lib/api";
import { resourceKeys } from "@/lib/queryClient";
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
    queryGet<ListResponse<ArticleCategory>>(resourceKeys("article-categories").list(params), "/admin/article-categories", { params, signal }, 5 * 60_000),

  listCached: (params: ArticleCategoryListParams) =>
    queryGet<ListResponse<ArticleCategory>>(
      resourceKeys("article-categories").list(params),
      "/admin/article-categories",
      { params },
      5 * 60_000,
    ),

  show: (id: string) =>
    queryGet<{ data: ArticleCategory }>(resourceKeys("article-categories").detail(id), `/admin/article-categories/${id}`, {}, 15 * 60_000).then((r) => r.data),

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
