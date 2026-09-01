import axios from "axios";
import type { AxiosRequestConfig } from "axios";
import { useAuthStore } from "@/stores/authStore";
import { queryClient } from "@/lib/queryClient";
import type { QueryKey } from "@tanstack/react-query";

export const api = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL ?? "http://localhost:8000/api",
  // Let Axios choose Content-Type from the payload. In particular, FormData
  // needs a browser-generated multipart boundary or PHP will not receive an
  // UploadedFile instance.
  headers: { Accept: "application/json" },
});

/** Shared TanStack-backed GET for API services that have not moved to hooks yet. */
export async function queryGet<T>(
  queryKey: QueryKey,
  url: string,
  config: AxiosRequestConfig = {},
  staleTime = 60_000,
): Promise<T> {
  const callerSignal = config.signal;
  const data = await queryClient.fetchQuery({
    queryKey,
    staleTime,
    queryFn: ({ signal }) =>
      api
        .get<T>(url, { ...config, signal })
        .then((response) => response.data),
  });
  if (callerSignal?.aborted) throw new axios.CanceledError();
  return data;
}

// ใส่ token อัตโนมัติ
api.interceptors.request.use((config) => {
  const token = useAuthStore.getState().token;
  if (token) config.headers.Authorization = `Bearer ${token}`;
  return config;
});

// 401 → clear auth แล้ว redirect login
api.interceptors.response.use(
  async (res) => {
    const method = res.config.method?.toLowerCase();
    if (method && method !== "get") {
      const invalidations: Promise<unknown>[] = [];
      const path = (res.config.url ?? "").split("?")[0];
      const adminResource = path.match(/^\/admin\/([^/]+)/)?.[1];
      if (adminResource) {
        invalidations.push(queryClient.invalidateQueries({ queryKey: [adminResource] }));
        const relatedResources: Record<string, string[]> = {
          "symptom-categories": ["symptoms"],
          "disease-categories": ["diseases"],
          "article-categories": ["articles"],
          "first-aid-categories": ["first-aids"],
          "body-area-groups": ["symptoms"],
        };
        for (const related of relatedResources[adminResource] ?? []) {
          invalidations.push(queryClient.invalidateQueries({ queryKey: [related] }));
        }
      }
      if (path.includes("/question-boxes") || path.includes("/answer-choices")) {
        invalidations.push(queryClient.invalidateQueries({ queryKey: ["diagrams"] }));
        invalidations.push(queryClient.invalidateQueries({ queryKey: ["diagnosis-rules"] }));
      }
      if (path.includes("article-comment")) {
        invalidations.push(queryClient.invalidateQueries({ queryKey: ["article-comments"] }));
        invalidations.push(queryClient.invalidateQueries({ queryKey: ["article-comment-reports"] }));
      }
      invalidations.push(queryClient.invalidateQueries({ queryKey: ["dashboard"] }));
      await Promise.all(invalidations);
    }
    return res;
  },
  (error) => {
    if (error.response?.status === 401) {
      useAuthStore.getState().clearAuth();
      window.location.replace("/login");
    }
    return Promise.reject(error);
  }
);
