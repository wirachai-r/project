import { ExternalLink } from "lucide-react";
import { Link } from "react-router-dom";
import { Badge } from "@/components/ui/Badge";
import { Button } from "@/components/ui/Button";
import { DataTable, type Column } from "@/components/ui/DataTable";
import { SimpleSelect } from "@/components/ui/SimpleSelect";
import { formatAdminDateTime } from "@/lib/formatDate";
import { encodeId } from "@/lib/idCodec";
import { FEEDBACK_TYPE_LABELS, type UserFeedback } from "@/types/userFeedback";

type MutableStatus = "in_review" | "resolved" | "dismissed";

const statusLabels: Record<UserFeedback["status"], string> = {
  pending: "รอตรวจสอบ",
  in_review: "กำลังตรวจสอบ",
  resolved: "ดำเนินการแล้ว",
  dismissed: "ปิดรายงาน",
};

function StatusBadge({ status }: { status: UserFeedback["status"] }) {
  const variant = status === "pending" ? "warning" : status === "in_review" ? "primary" : status === "resolved" ? "success" : "outline";
  return <Badge variant={variant}>{statusLabels[status]}</Badge>;
}

function FeedbackTypeBadge({ type }: { type: UserFeedback["feedback_type"] }) {
  const variant = type === "content_error"
    ? "danger"
    : type === "assessment"
      ? "warning"
      : "primary";
  return <Badge variant={variant}>{FEEDBACK_TYPE_LABELS[type]}</Badge>;
}

const statusOptions = [
  { label: "รอตรวจสอบ", value: "pending" },
  { label: "กำลังตรวจสอบ", value: "in_review" },
  { label: "ดำเนินการแล้ว", value: "resolved" },
  { label: "ปิดรายงาน", value: "dismissed" },
];

const targetTypeLabels: Record<string, string> = {
  article: "บทความ",
  disease: "ข้อมูลโรค",
  symptom: "อาการ",
  first_aid: "ปฐมพยาบาล",
  assessment: "ผลประเมิน",
};

const categoryLabels: Record<string, string> = {
  inaccurate: "ข้อมูลไม่ถูกต้อง",
  outdated: "ข้อมูลล้าสมัย",
  unclear: "ข้อมูลไม่ชัดเจน",
  unsafe: "ข้อมูลอาจไม่ปลอดภัย",
  suggestion: "ข้อเสนอแนะ",
  bug: "ปัญหาการใช้งาน",
  content_error: "ข้อมูลไม่ถูกต้อง",
  other: "อื่น ๆ",
};

function targetEditPath(item: UserFeedback) {
  if (!item.target_id) return null;
  const id = encodeId(item.target_id);
  if (item.target_type === "article") return `/articles/edit/${id}`;
  if (item.target_type === "disease") return `/diseases/edit/${id}`;
  if (item.target_type === "first_aid") return `/first-aids/edit/${id}`;
  return null;
}

export function UserFeedbackTable({ data, busyId, onStatusChange }: { data: UserFeedback[]; busyId: number | null; onStatusChange: (id: number, status: MutableStatus) => void }) {
  const columns: Column<UserFeedback>[] = [
    {
      key: "sequence",
      label: "ลำดับ",
      render: (item) => String((item as UserFeedback & { __rowNumber?: number }).__rowNumber ?? "-"),
    },
    {
      key: "sender",
      label: "ผู้ส่ง",
      className: "min-w-52",
      render: (item) => (
        <div>
          <div className="font-medium">{`${item.user?.first_name ?? ""} ${item.user?.last_name ?? ""}`.trim() || "ผู้ใช้งาน"}</div>
          <div className="text-xs text-[var(--color-text-secondary)]">{item.user?.email}</div>
        </div>
      ),
    },
    {
      key: "feedback_type",
      label: "ประเภท",
      className: "min-w-40",
      render: (item) => <FeedbackTypeBadge type={item.feedback_type} />,
    },
    {
      key: "category",
      label: "หัวข้อรายงาน",
      className: "min-w-44",
      render: (item) => item.category ? (
        <Badge variant="outline">{categoryLabels[item.category] ?? item.category}</Badge>
      ) : <span className="text-[var(--color-text-secondary)]">ไม่ระบุ</span>,
    },
    {
      key: "message",
      label: "รายละเอียด",
      className: "min-w-72 max-w-xl",
      render: (item) => <div><p className="whitespace-pre-wrap">{item.message}</p>{item.rating && <p className="mt-2 text-xs text-[var(--color-text-secondary)]">{item.rating} ดาว</p>}</div>,
    },
    {
      key: "target",
      label: "รายงานเรื่อง",
      className: "w-80 min-w-64 max-w-80",
      render: (item) => {
        const editPath = targetEditPath(item);
        return item.target_type ? (
          <div className="min-w-0">
            <div className="font-medium">{targetTypeLabels[item.target_type] ?? item.target_type}</div>
            <div
              className="mt-1 truncate text-sm text-[var(--color-text-secondary)]"
              title={item.target_name ?? undefined}
            >
              {item.target_name ?? (item.target_type === "assessment" ? `ผลประเมิน #${item.target_id}` : `รหัส ${item.target_id}`)}
            </div>
            {editPath && (
              <Button asChild variant="link" size="sm" className="mt-1 h-auto px-0 py-1">
                <Link to={editPath}>
                  <ExternalLink /> ดูและแก้ไข
                </Link>
              </Button>
            )}
          </div>
        ) : <span className="text-[var(--color-text-secondary)]">ไม่ระบุรายการ</span>;
      },
    },
    {
      key: "created_at",
      label: "เวลารายงาน",
      className: "min-w-44",
      render: (item) => formatAdminDateTime(item.created_at),
    },
    {
      key: "updated_at",
      label: "แก้ไขล่าสุด",
      className: "min-w-44",
      render: (item) => formatAdminDateTime(item.updated_at),
    },
    {
      key: "status",
      label: "สถานะปัจจุบัน",
      render: (item) => <StatusBadge status={item.status} />,
    },
    {
      key: "actions",
      label: "เปลี่ยนสถานะ",
      className: "w-56 min-w-56",
      render: (item) => (
        <SimpleSelect
          value={item.status}
          disabled={busyId === item.id}
          options={statusOptions}
          onChange={(status) => {
            if (status !== item.status && status !== "pending") {
              onStatusChange(item.id, status as MutableStatus);
            }
          }}
          className="w-full"
        />
      ),
    },
  ];

  return <DataTable columns={columns} data={data} keyExtractor={(item) => item.id} emptyMessage="ไม่พบข้อเสนอแนะ" />;
}
