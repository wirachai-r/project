import { api } from "@/lib/api";
import type { User } from "@/types/user";
import type { PaginatedResponse } from "@/types/pagination";

export interface UserListParams {
  search?: string;
  role?: string;
  status?: string;
  page?: number;
  per_page?: number;
  sort_by?: "name" | "last_login" | "role";
  sort_direction?: "asc" | "desc";
}

export interface UserStats {
  total: number;
  active: number;
  banned: number;
}

export interface UpdateUserPayload {
  first_name?: string;
  last_name?: string;
  email?: string;
  role?: "User" | "Admin";
}

export const userApi = {
  list: (params: UserListParams, signal?: AbortSignal) =>
    api
      .get<PaginatedResponse<User>>("/admin/users", { params, signal })
      .then((res) => res.data),

  stats: () =>
    api
      .get<{ data: UserStats }>("/admin/users/stats")
      .then((res) => res.data.data),

  update: (userId: string, payload: UpdateUserPayload) =>
    api
      .put<{ data: User }>(`/admin/users/${userId}`, payload)
      .then((res) => res.data.data),

  ban: (userId: string) =>
    api
      .patch<{ data: User }>(`/admin/users/${userId}/ban`)
      .then((res) => res.data.data),

  unban: (userId: string) =>
    api
      .patch<{ data: User }>(`/admin/users/${userId}/unban`)
      .then((res) => res.data.data),
};
