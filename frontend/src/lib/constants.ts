import {
  LayoutDashboard,
  Users,
  Activity,
  Stethoscope,
  // BookOpen,
  Newspaper,
  Heart,
  // MapPin,
  // ClipboardList,
  GitBranch,
  Bell,
  MessageSquareWarning,
} from "lucide-react";

export interface NavSubItem {
  label: string;
  to: string;
}

export interface NavItem {
  label: string;
  to: string;
  icon: React.ComponentType<{ className?: string }>;
  items?: NavSubItem[]; // ถ้ามี = แสดงเป็นเมนูย่อยแบบพับ/กาง
}

export interface NavSection {
  title: string;
  items: NavItem[];
}

export const NAV_SECTIONS: NavSection[] = [
  {
    title: "ภาพรวม",
    items: [
      { label: "ภาพรวมระบบ", to: "/dashboard", icon: LayoutDashboard },
      { label: "ผู้ใช้งาน", to: "/users", icon: Users },
      // { label: "การประเมิน", to: "/assessments", icon: ClipboardList },
    ],
  },
  {
    title: "ข้อมูลการวินิจฉัย",
    items: [
      {
        label: "อาการ",
        to: "/symptoms",
        icon: Activity,
        items: [
          { label: "หมวดหมู่อาการ", to: "/symptoms/categories" },
          { label: "รายการอาการ", to: "/symptoms" },
          { label: "กลุ่มบริเวณร่างกาย", to: "/symptoms/body-areas" },
          { label: "คำถามติดตามอาการ", to: "/symptoms/follow-up-questions" },
          { label: "คำถามประเมินแบบตามคำตอบ", to: "/symptoms/adaptive-questions" },
        ],
      },
      {
        label: "โรค",
        to: "/diseases",
        icon: Stethoscope,
        items: [
          { label: "หมวดหมู่โรค", to: "/diseases/categories" },
          { label: "รายการโรค", to: "/diseases" },
        ],
      },
      { label: "แผนภูมิ", to: "/diagrams", icon: GitBranch },
      // { label: "กฎการวินิจฉัย", to: "/diagnosis-rules", icon: ClipboardList },
    ],
  },
  {
    title: "เนื้อหา",
    items: [
      {
        label: "บทความ",
        to: "/articles",
        // icon: BookOpen,
        icon: Newspaper,
        items: [
          { label: "หมวดหมู่บทความ", to: "/articles/categories" },
          { label: "รายการบทความ", to: "/articles" },
          { label: "ความคิดเห็น", to: "/articles/comments" },
          { label: "รายงาน", to: "/articles/comment-reports" },
        ],
      },
      {
        label: "ปฐมพยาบาล",
        to: "/first-aids",
        icon: Heart,
        items: [
          { label: "หมวดหมู่ปฐมพยาบาล", to: "/first-aids/categories" },
          { label: "รายการปฐมพยาบาล", to: "/first-aids" },
        ],
      },
      // { label: "สถานพยาบาล", to: "/facilities", icon: MapPin },
    ],
  },
  {
    title: "ระบบ",
    items: [
      { label: "จัดการการแจ้งเตือน", to: "/notifications", icon: Bell },
      { label: "ข้อเสนอแนะจากผู้ใช้", to: "/feedback", icon: MessageSquareWarning },
    ],
  },
];

// แต่ละ resource หลักที่มีหมวดหมู่ (category) แนบอยู่ ใช้ tab ภายในหน้าเดียวกัน
// ไม่ต้องมีเมนูแยกในไซด์บาร์
export const CATEGORY_TABS: Record<
  string,
  { key: string; label: string; apiPath: string }
> = {
  "/symptoms": {
    key: "symptom-categories",
    label: "หมวดหมู่อาการ",
    apiPath: "/admin/symptom-categories",
  },
  "/diseases": {
    key: "disease-categories",
    label: "หมวดหมู่โรค",
    apiPath: "/admin/disease-categories",
  },
  "/articles": {
    key: "article-categories",
    label: "หมวดหมู่บทความ",
    apiPath: "/admin/article-categories",
  },
  "/first-aids": {
    key: "first-aid-categories",
    label: "หมวดหมู่ปฐมพยาบาล",
    apiPath: "/admin/first-aid-categories",
  },
};

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

export const URGENCY_LEVELS: Record<
  UrgencyCode,
  { label: string; short: string; hex: string }
> = {
  R: { label: "ฉุกเฉินมาก", short: "แดง", hex: "#EF4444" },
  P: { label: "เร่งด่วน", short: "ชมพู", hex: "#EC4899" },
  Y: { label: "ควรพบแพทย์", short: "เหลือง", hex: "#EAB308" },
  G: { label: "ดูแลตัวเองได้", short: "เขียว", hex: "#22C55E" },
  W: { label: "ปกติ", short: "ขาว", hex: "#9CA3AF" },
};

// ===== เพิ่มต่อท้ายไฟล์ constants.ts (หลัง URGENCY_LEVELS) =====

export interface ActiveNavMatch {
  section: NavSection;
  item: NavItem;
  sub?: NavSubItem;
  to: string; // path ที่ match (item.to หรือ sub.to)
}

/**
 * หา nav entry ที่ active ที่สุดจาก pathname ปัจจุบัน
 * รองรับ path ย่อย เช่น /diseases/edit/xxx จะ match กับ item.to = "/diseases"
 * ถ้ามีหลาย match (เช่น /diseases และ /diseases/categories) จะเลือกอันที่ specific ที่สุด (to ยาวสุด)
 */
export function getActiveNavMatch(pathname: string): ActiveNavMatch | null {
  const isMatch = (to: string) =>
    pathname === to || pathname.startsWith(`${to}/`);

  let best: ActiveNavMatch | null = null;

  for (const section of NAV_SECTIONS) {
    for (const item of section.items) {
      // เช็ค sub-items ก่อน เพราะ specific กว่า
      if (item.items?.length) {
        for (const sub of item.items) {
          if (isMatch(sub.to)) {
            if (!best || sub.to.length > best.to.length) {
              best = { section, item, sub, to: sub.to };
            }
          }
        }
      }

      if (isMatch(item.to)) {
        if (!best || item.to.length > best.to.length) {
          best = { section, item, to: item.to };
        }
      }
    }
  }

  return best;
}
