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
    staleTime: 15_000,
    refetchInterval: 30_000,
    refetchIntervalInBackground: false,
  });
}

export function useUnreadNotificationCount() {
  const query = useAdminNavigationCounts();

  return {
    ...query,
    data: query.data?.unread_notifications,
  };
}
