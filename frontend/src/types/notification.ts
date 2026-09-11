export type CampaignStatus = "draft"|"scheduled"|"queued"|"processing"|"sent"|"partially_failed"|"cancelled";
export type AdminNotification = { id:number; title:string; body:string; body_text:string; type:"S"|"U"|"W"|"E"|"I"; audience:"all"|"group"|"individual"; audience_filter:Record<string,string|string[]>|null; channels:("in_app"|"push")[]; target_url:string|null; status:CampaignStatus; is_persistent:boolean; starts_at:string|null; expires_at:string|null; scheduled_at:string|null; sent_at:string|null; recipient_count:number; sent_count:number; failed_count:number; read_count:number; created_at:string };
export type NotificationFilters = { search:string; types:string[]; status:string; sortDirection:"asc"|"desc" };
export interface PersonalNotification {
  id: number;
  title: string;
  body: string;
  body_text: string;
  type: "S" | "U" | "W" | "E" | "I";
  target_url: string | null;
  is_read: "Y" | "N";
  read_at: string | null;
  dismissed_at: string | null;
  created_at: string;
}
export const NOTIFICATION_TYPES=[{label:"ระบบ",value:"S"},{label:"ผู้ใช้",value:"U"},{label:"คำเตือน",value:"W"},{label:"เร่งด่วน",value:"E"},{label:"ข้อมูล",value:"I"}];
