import { useState } from "react";
import { Eye } from "lucide-react";
import { Badge } from "@/components/ui/Badge";
import { Button } from "@/components/ui/Button";
import { DataTable, type Column } from "@/components/ui/DataTable";
import { formatAdminDateTime } from "@/lib/formatDate";
import { FEEDBACK_TYPE_LABELS, type UserFeedback } from "@/types/userFeedback";
import { UserFeedbackDetailsDialog, type FeedbackMutableStatus } from "./UserFeedbackDetailsDialog";

const statusLabels: Record<UserFeedback["status"], string> = {
  pending: "รอตรวจสอบ", in_review: "กำลังตรวจสอบ", resolved: "ดำเนินการแล้ว", dismissed: "ปิดรายงาน",
};

function StatusBadge({ status }: { status: UserFeedback["status"] }) {
  const variant = status === "pending" ? "warning" : status === "in_review" ? "primary" : status === "resolved" ? "success" : "outline";
  return <Badge variant={variant}>{statusLabels[status]}</Badge>;
}

export function UserFeedbackTable({ data, busyId, onStatusChange }: {
  data: UserFeedback[];
  busyId: number | null;
  onStatusChange: (id: number, status: FeedbackMutableStatus, adminNote?: string) => Promise<void>;
}) {
  const [selected, setSelected] = useState<UserFeedback | null>(null);
  const columns: Column<UserFeedback>[] = [
    { key: "sequence", label: "ลำดับ", render: (item) => String((item as UserFeedback & { __rowNumber?: number }).__rowNumber ?? "-") },
    { key: "sender", label: "ผู้ส่ง", className: "min-w-52", render: (item) => <div><div className="font-medium">{`${item.user?.first_name ?? ""} ${item.user?.last_name ?? ""}`.trim() || "ผู้ใช้งาน"}</div><div className="text-xs text-[var(--color-text-secondary)]">{item.user?.email}</div></div> },
    { key: "feedback_type", label: "ประเภท", className: "min-w-40", render: (item) => <Badge variant={item.feedback_type === "content_error" ? "danger" : item.feedback_type === "assessment" ? "warning" : "primary"}>{FEEDBACK_TYPE_LABELS[item.feedback_type]}</Badge> },
    { key: "message", label: "รายละเอียด", className: "min-w-64 max-w-lg", render: (item) => <div><p className="line-clamp-2 break-words">{item.message}</p>{!!item.attachments?.length && <p className="mt-1 text-xs text-[var(--color-text-secondary)]">แนบรูป {item.attachments.length} รูป</p>}</div> },
    { key: "created_at", label: "เวลารายงาน", className: "min-w-40", render: (item) => formatAdminDateTime(item.created_at) },
    { key: "status", label: "สถานะ", render: (item) => <StatusBadge status={item.status} /> },
    { key: "actions", label: "จัดการ", className: "min-w-36", render: (item) => <Button variant="outline" size="sm" onClick={() => setSelected(item)}><Eye />ดูรายละเอียด</Button> },
  ];

  return <>
    <DataTable columns={columns} data={data} keyExtractor={(item) => item.id} emptyMessage="ไม่พบข้อเสนอแนะ" />
    <UserFeedbackDetailsDialog item={selected} busy={busyId === selected?.id} onClose={() => setSelected(null)} onSave={async (id, status, note) => {
      try {
        await onStatusChange(id, status, note);
        setSelected(null);
      } catch {
        // The page shows the error toast; keep the dialog open for retrying.
      }
    }} />
  </>;
}
