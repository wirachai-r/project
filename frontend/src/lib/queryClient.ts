import { QueryClient } from "@tanstack/react-query";

export const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      staleTime: 60_000,
      gcTime: 10 * 60_000,
      refetchOnWindowFocus: false,
      refetchOnReconnect: true,
      retry: 1,
    },
    mutations: {
      retry: false,
    },
  },
});

export const queryKeys = {
  users: {
    all: ["users"] as const,
    lists: () => ["users", "list"] as const,
    list: (params: object) => ["users", "list", params] as const,
    stats: () => ["users", "stats"] as const,
  },
  articles: {
    all: ["articles"] as const,
    lists: () => ["articles", "list"] as const,
    list: (params: object) => ["articles", "list", params] as const,
    detail: (id: string) => ["articles", "detail", id] as const,
  },
  articleCategories: {
    all: ["article-categories"] as const,
    lists: () => ["article-categories", "list"] as const,
    list: (params: object) => ["article-categories", "list", params] as const,
  },
  lookups: {
    activeDiseases: ["lookups", "active-diseases"] as const,
    activeDiagrams: ["lookups", "active-diagrams"] as const,
  },
};

export const resourceKeys = (resource: string) => ({
  all: [resource] as const,
  lists: () => [resource, "list"] as const,
  list: (params: object) => [resource, "list", params] as const,
  details: () => [resource, "detail"] as const,
  detail: (id: string | number) => [resource, "detail", id] as const,
});
