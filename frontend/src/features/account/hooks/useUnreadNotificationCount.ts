import { useQuery } from "@tanstack/react-query";
import { api } from "@/lib/api";

export const unreadNotificationCountKey = ["personal-notifications", "unread-count"] as const;

export function useUnreadNotificationCount() {
  return useQuery({
    queryKey: unreadNotificationCountKey,
    queryFn: () => api.get<{ unread_count: number }>("/notifications/unread-count").then((response) => response.data.unread_count),
    staleTime: 15_000,
    refetchInterval: 30_000,
  });
}
