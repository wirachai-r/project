import type { FollowUpAnswerType } from "@/types/followUpQuestion";

export const FOLLOW_UP_ANSWER_TYPES: Array<{
  value: FollowUpAnswerType;
  label: string;
}> = [
  { value: "boolean", label: "ใช่ / ไม่ใช่" },
  { value: "single_choice", label: "เลือกได้หนึ่งข้อ" },
  { value: "multiple_choice", label: "เลือกได้หลายข้อ" },
  { value: "scale", label: "เลือกระดับ" },
  { value: "number", label: "ตัวเลข" },
  { value: "text", label: "ข้อความ" },
  { value: "date", label: "วันที่" },
  { value: "time", label: "เวลา" },
];
