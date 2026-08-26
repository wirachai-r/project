export type AdminNotification = {
  id: number;
  title: string;
  body: string;
  body_text: string;
  type: "S" | "U" | "W" | "E" | "I";
  is_read: boolean;
  read_at: string | null;
  user_id: string;
  user?: { first_name: string; last_name: string; email: string };
  created_at: string;
};

export type NotificationFilters = {
  search: string;
  type: string;
  read: string;
  sortDirection: "asc" | "desc";
};
export const NOTIFICATION_TYPES = [
  { label: "ระบบ", value: "S" }, { label: "ผู้ใช้", value: "U" },
  { label: "คำเตือน", value: "W" }, { label: "ฉุกเฉิน", value: "E" },
  { label: "ข้อมูล", value: "I" },
];
