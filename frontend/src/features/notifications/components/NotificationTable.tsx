import { Badge } from "@/components/ui/Badge";
import { Button } from "@/components/ui/Button";
import { DataTable, type Column } from "@/components/ui/DataTable";
import { NOTIFICATION_TYPES, type AdminNotification } from "@/types/notification";

export function NotificationTable({ data, busyId, onRead, onDelete }: { data: AdminNotification[]; busyId: number | null; onRead: (id: number) => void; onDelete: (id: number) => void }) {
  const columns: Column<AdminNotification>[] = [
    { key: "sequence", label: "ลำดับ", render: (item) => String((item as AdminNotification & { __rowNumber?: number }).__rowNumber ?? "-") },
    { key: "title", label: "การแจ้งเตือน", className: "min-w-72", render: (item) => <div><div className="font-medium">{item.title}</div><p className="mt-1 line-clamp-2 text-[var(--color-text-secondary)]">{item.body_text}</p></div> },
    { key: "recipient", label: "ผู้รับ", className: "min-w-52", render: (item) => <div><div>{`${item.user?.first_name ?? ""} ${item.user?.last_name ?? ""}`.trim() || item.user_id}</div><div className="text-xs text-[var(--color-text-secondary)]">{item.user?.email}</div></div> },
    { key: "type", label: "ประเภท", render: (item) => <Badge>{NOTIFICATION_TYPES.find((type) => type.value === item.type)?.label ?? item.type}</Badge> },
    { key: "is_read", label: "สถานะ", render: (item) => <Badge variant={item.is_read ? "success" : "warning"}>{item.is_read ? "อ่านแล้ว" : "ยังไม่อ่าน"}</Badge> },
    { key: "created_at", label: "วันที่ส่ง", className: "min-w-40", render: (item) => new Date(item.created_at).toLocaleString("th-TH", { dateStyle: "medium", timeStyle: "short" }) },
    { key: "actions", label: "การจัดการ", className: "min-w-48 text-right", render: (item) => <div className="flex justify-end gap-2">{!item.is_read && <Button size="sm" variant="outline" disabled={busyId === item.id} onClick={() => onRead(item.id)}>ทำเครื่องหมายว่าอ่าน</Button>}<Button size="sm" variant="danger" disabled={busyId === item.id} onClick={() => onDelete(item.id)}>ลบ</Button></div> },
  ];
  return <DataTable columns={columns} data={data} keyExtractor={(item) => item.id} emptyMessage="ไม่พบการแจ้งเตือน" />;
}
