import {
  LayoutDashboard,
  Users,
  Activity,
  Stethoscope,
  BookOpen,
  Heart,
  MapPin,
  ClipboardList,
  GitBranch,
} from "lucide-react";

export interface NavItem {
  label: string;
  to: string;
  icon: React.ComponentType<{ className?: string }>;
}

export interface NavSection {
  title: string;
  items: NavItem[];
}

export const NAV_SECTIONS: NavSection[] = [
  {
    title: "ภาพรวม",
    items: [
      { label: "แดชบอร์ด",   to: "/dashboard",  icon: LayoutDashboard },
      { label: "ผู้ใช้งาน",   to: "/users",      icon: Users },
      { label: "การประเมิน", to: "/assessments", icon: ClipboardList },
    ],
  },
  {
    title: "ข้อมูลการวินิจฉัย",
    items: [
      { label: "อาการ",          to: "/symptoms",        icon: Activity },
      { label: "โรค",            to: "/diseases",         icon: Stethoscope },
      { label: "แผนภาพ",        to: "/diagrams",         icon: GitBranch },
      { label: "กฎการวินิจฉัย", to: "/diagnosis-rules",  icon: ClipboardList },
    ],
  },
  {
    title: "เนื้อหา",
    items: [
      { label: "บทความ",    to: "/articles",   icon: BookOpen },
      { label: "ปฐมพยาบาล", to: "/first-aids", icon: Heart },
      { label: "สถานพยาบาล", to: "/facilities", icon: MapPin },
    ],
  },
];

export const URGENCY_LABELS: Record<string, string> = {
  R: "ฉุกเฉินมาก",
  P: "เร่งด่วน",
  Y: "ควรพบแพทย์",
  G: "ดูแลตัวเองได้",
  W: "ปกติ",
};

export const URGENCY_COLORS: Record<string, string> = {
  R: "bg-red-100 text-red-600",
  P: "bg-pink-100 text-pink-600",
  Y: "bg-yellow-100 text-yellow-700",
  G: "bg-green-100 text-green-700",
  W: "bg-gray-100 text-gray-600",
};

export type UrgencyCode = "R" | "P" | "Y" | "G" | "W";

export const URGENCY_LEVELS: Record<UrgencyCode, { label: string; short: string; hex: string }> = {
  R: { label: "ฉุกเฉินมาก",     short: "แดง",   hex: "#EF4444" },
  P: { label: "เร่งด่วน",       short: "ชมพู",  hex: "#EC4899" },
  Y: { label: "ควรพบแพทย์",    short: "เหลือง", hex: "#EAB308" },
  G: { label: "ดูแลตัวเองได้",  short: "เขียว", hex: "#22C55E" },
  W: { label: "ปกติ",           short: "ขาว",   hex: "#9CA3AF" },
};