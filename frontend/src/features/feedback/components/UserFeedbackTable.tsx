import { Badge } from "@/components/ui/Badge";
import { DataTable, type Column } from "@/components/ui/DataTable";
import { SimpleSelect } from "@/components/ui/SimpleSelect";
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

const statusOptions = [
  { label: "รอตรวจสอบ", value: "pending" },
  { label: "กำลังตรวจสอบ", value: "in_review" },
  { label: "ดำเนินการแล้ว", value: "resolved" },
  { label: "ปิดรายงาน", value: "dismissed" },
];

export function UserFeedbackTable({ data, busyId, onStatusChange }: { data: UserFeedback[]; busyId: number | null; onStatusChange: (id: number, status: MutableStatus) => void }) {
  const columns: Column<UserFeedback>[] = [
    {
      key: "sequence",
      label: "ลำดับ",
      render: (item) => String((item as UserFeedback & { __rowNumber?: number }).__rowNumber ?? "-"),
    },
    {
      key: "sender",
      label: "ผู้ส่ง/ประเภท",
      className: "min-w-52",
      render: (item) => (
        <div>
          <div className="font-medium">{`${item.user?.first_name ?? ""} ${item.user?.last_name ?? ""}`.trim() || "ผู้ใช้งาน"}</div>
          <div className="text-xs text-[var(--color-text-secondary)]">{item.user?.email}</div>
          <Badge className="mt-2">{FEEDBACK_TYPE_LABELS[item.feedback_type]}</Badge>
        </div>
      ),
    },
    {
      key: "message",
      label: "รายละเอียด",
      className: "min-w-72 max-w-xl",
      render: (item) => <div><p className="whitespace-pre-wrap">{item.message}</p>{item.rating && <p className="mt-2 text-xs text-[var(--color-text-secondary)]">{item.rating} ดาว</p>}</div>,
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
