import { Badge } from "@/components/ui/Badge";
import { Button } from "@/components/ui/Button";
import { DataTable, type Column } from "@/components/ui/DataTable";
import {
  Tooltip,
  TooltipContent,
  TooltipProvider,
  TooltipTrigger,
} from "@/components/ui/Tooltip";
import { Eye, MoreHorizontal, Pencil, RefreshCw, Trash2, XCircle } from "lucide-react";
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from "@/components/ui/DropdownMenu";
import {
  NOTIFICATION_TYPES,
  type AdminNotification,
} from "@/types/notification";
const statuses: Record<string, string> = {
  draft: "ฉบับร่าง",
  scheduled: "รอส่ง",
  queued: "เข้าคิว",
  processing: "กำลังส่ง",
  sent: "ส่งแล้ว",
  partially_failed: "ส่งไม่ครบ",
  cancelled: "ยกเลิก",
};
const typeVariants: Record<
  AdminNotification["type"],
  "default" | "primary" | "warning" | "danger"
> = { S: "default", U: "primary", W: "warning", E: "danger", I: "primary" };
const typeDescriptions: Record<AdminNotification["type"], string> = {
  S: "ข้อความจากระบบ",
  U: "ข้อความส่วนตัวสำหรับผู้ใช้",
  W: "คำเตือนที่ควรทราบ",
  E: "ข้อความเร่งด่วน",
  I: "ข้อมูลทั่วไป",
};
export function NotificationTable({
  data,
  busyId,
  onView,
  onEdit,
  onDelete,
  onCancel,
  onRetry,
}: {
  data: AdminNotification[];
  busyId: number | null;
  onView: (item: AdminNotification) => void;
  onEdit: (item: AdminNotification) => void;
  onDelete: (id: number) => void;
  onCancel: (id: number) => void;
  onRetry: (id: number) => void;
}) {
  const columns: Column<AdminNotification>[] = [
    {
      key: "sequence",
      label: "ลำดับ",
      className: "w-16",
      render: (i) =>
        String(
          (i as AdminNotification & { __rowNumber?: number }).__rowNumber ??
            "-",
        ),
    },
    {
      key: "title",
      label: "การแจ้งเตือน",
      className: "w-72 max-w-72 whitespace-normal",
      render: (i) => (
        <div className="min-w-0 max-w-72">
          <div className="truncate font-medium" title={i.title}>{i.title}</div>
          <p className="mt-1 line-clamp-2 break-words text-[var(--color-text-secondary)] [overflow-wrap:anywhere]">
            {i.body_text}
          </p>
          {i.is_persistent && (
            <div className="mt-2">
              <Badge variant="success">ข้อความคงอยู่</Badge>
            </div>
          )}
        </div>
      ),
    },
    {
      key: "audience",
      label: "ผู้รับ",
      className: "w-28",
      render: (i) =>
        i.audience === "all"
          ? "ทุกคน"
          : i.audience === "group"
            ? "กลุ่มผู้ใช้"
            : "รายบุคคล",
    },
    {
      key: "type",
      label: "ประเภท",
      className: "w-24",
      render: (i) => (
        <Tooltip>
          <TooltipTrigger asChild>
            <span className="inline-flex">
              <Badge variant={typeVariants[i.type]}>
                {NOTIFICATION_TYPES.find((t) => t.value === i.type)?.label}
              </Badge>
            </span>
          </TooltipTrigger>
          <TooltipContent>{typeDescriptions[i.type]}</TooltipContent>
        </Tooltip>
      ),
    },
    {
      key: "stats",
      label: "ผลการส่ง",
      className: "w-48 min-w-48",
      render: (i) => (
        <div className="text-sm">
          <div>
            ผู้รับ {i.recipient_count} · ส่งสำเร็จ {i.sent_count}
          </div>
          <div className="text-[var(--color-text-secondary)]">
            ล้มเหลว {i.failed_count} · เปิดอ่าน {i.read_count}
          </div>
        </div>
      ),
    },
    {
      key: "status",
      label: "สถานะ",
      className: "w-24",
      render: (i) => (
        <Badge
          variant={
            i.status === "sent"
              ? "success"
              : i.status === "partially_failed"
                ? "danger"
                : "warning"
          }
        >
          {statuses[i.status] ?? i.status}
        </Badge>
      ),
    },
    {
      key: "created_at",
      label: "วันที่",
      className: "w-40 min-w-40",
      render: (i) =>
        new Date(i.scheduled_at ?? i.created_at).toLocaleString("th-TH", {
          dateStyle: "medium",
          timeStyle: "short",
        }),
    },
    {
      key: "actions",
      label: "",
      className: "w-12 text-right",
      render: (i) => (
        <DropdownMenu>
          <DropdownMenuTrigger asChild>
            <Button size="icon" variant="ghost" disabled={busyId === i.id} aria-label={`จัดการการแจ้งเตือน ${i.title}`}>
              <MoreHorizontal />
            </Button>
          </DropdownMenuTrigger>
          <DropdownMenuContent align="end">
            <DropdownMenuItem onClick={() => onView(i)}><Eye />ดูรายละเอียด</DropdownMenuItem>
          {["draft", "scheduled"].includes(i.status) && (
            <>
              <DropdownMenuItem onClick={() => onEdit(i)}><Pencil />แก้ไขการแจ้งเตือน</DropdownMenuItem>
              <DropdownMenuSeparator />
              <DropdownMenuItem variant="danger" onClick={() => onDelete(i.id)}><Trash2 />ลบการแจ้งเตือน</DropdownMenuItem>
            </>
          )}
          {i.status === "partially_failed" && (
            <><DropdownMenuSeparator /><DropdownMenuItem onClick={() => onRetry(i.id)}><RefreshCw />ส่งซ้ำรายการที่ล้มเหลว</DropdownMenuItem></>
          )}
          {["queued", "processing"].includes(i.status) && (
            <><DropdownMenuSeparator /><DropdownMenuItem variant="danger" onClick={() => onCancel(i.id)}><XCircle />ยกเลิกการส่ง</DropdownMenuItem></>
          )}
          </DropdownMenuContent>
        </DropdownMenu>
      ),
    },
  ];
  return (
    <TooltipProvider>
      <DataTable
        columns={columns}
        data={data}
        keyExtractor={(i) => i.id}
        emptyMessage="ไม่พบการแจ้งเตือน"
      />
    </TooltipProvider>
  );
}
