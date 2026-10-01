import { useQuery } from "@tanstack/react-query";
import { api } from "@/lib/api";

export interface AdminNavigationCounts {
  pending_comment_reports: number;
  pending_feedback: number;
  unread_notifications: number;
}

export const navigationCountsKey = ["admin-navigation-counts"] as const;

export function useAdminNavigationCounts() {
  return useQuery({
    queryKey: navigationCountsKey,
    queryFn: () => api.get<AdminNavigationCounts>("/admin/navigation-counts").then((response) => response.data),
    staleTime: 0,
    refetchInterval: 10_000,
    refetchIntervalInBackground: true,
    refetchOnMount: "always",
    refetchOnWindowFocus: "always",
    refetchOnReconnect: "always",
  });
}

export function useUnreadNotificationCount() {
  const query = useAdminNavigationCounts();

  return {
    ...query,
    data: query.data?.unread_notifications,
  };
}
