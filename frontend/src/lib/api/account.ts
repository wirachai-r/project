import { api } from "@/lib/api";
import type { PersonalNotification } from "@/types/notification";
import type { User } from "@/types/user";
import { queryClient } from "@/lib/queryClient";
import { navigationCountsKey } from "@/features/account/hooks/useUnreadNotificationCount";

export interface ProfilePayload {
  first_name: string;
  last_name: string;
  sex: "M" | "F" | null;
  date_of_birth: string | null;
  profile_image: string | null;
}

export interface PasswordPayload {
  current_password: string;
  password: string;
  password_confirmation: string;
}

export const accountApi = {
  getProfile: () => api.get<{ data: User }>("/profile").then((response) => response.data.data),
  updateProfile: (payload: ProfilePayload) =>
    api.put<{ data: User }>("/profile", payload).then((response) => response.data.data),
  changePassword: (payload: PasswordPayload) => api.put("/profile/password", payload),
  notifications: () =>
    api.get<{ data: PersonalNotification[] }>("/notifications").then((response) => response.data.data),
  markAsRead: (id: number) => api.patch(`/notifications/${id}/read`).then((response) => {
    void queryClient.invalidateQueries({ queryKey: navigationCountsKey });
    return response;
  }),
  markAllAsRead: () => api.post("/notifications/read-all").then((response) => {
    void queryClient.invalidateQueries({ queryKey: navigationCountsKey });
    return response;
  }),
  dismiss: (id: number) => api.patch(`/notifications/${id}/dismiss`).then((response) => {
    void queryClient.invalidateQueries({ queryKey: navigationCountsKey });
    return response;
  }),
};
