import { Button } from "@/components/ui/Button";
import { Badge } from "@/components/ui/Badge";
import {
  Dialog,
  DialogContent,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/Dialog";
import type { AdminNotification } from "@/types/notification";

const statusLabels: Record<AdminNotification["status"], string> = {
  draft: "ฉบับร่าง",
  scheduled: "รอส่ง",
  queued: "เข้าคิว",
  processing: "กำลังส่ง",
  sent: "ส่งแล้ว",
  partially_failed: "ส่งไม่ครบ",
  cancelled: "ยกเลิก",
};

export function NotificationDetailDialog({
  item,
  onClose,
}: {
  item: AdminNotification | null;
  onClose: () => void;
}) {
  return (
    <Dialog
      open={item !== null}
      onOpenChange={(open) => {
        if (!open) onClose();
      }}
    >
      <DialogContent maxWidth="xl" className="max-h-[90vh] min-w-0 overflow-y-auto">
        <DialogHeader>
          <DialogTitle>รายละเอียดการแจ้งเตือน</DialogTitle>
        </DialogHeader>
        {item && (
          <div className="space-y-5">
            <section className="min-w-0 overflow-hidden rounded-2xl border border-[var(--color-border)] bg-white">
              <div className="flex flex-wrap items-center justify-between gap-2 border-b border-[var(--color-border)] bg-[var(--color-primary-light)]/35 px-5 py-4">
                <h2 className="break-words text-lg font-semibold text-[var(--color-text-primary)] [overflow-wrap:anywhere]">{item.title}</h2>
                <Badge variant={item.status === "sent" ? "success" : item.status === "partially_failed" ? "danger" : "warning"}>{statusLabels[item.status]}</Badge>
              </div>
              <div
                className="prose prose-sm min-h-24 max-w-none break-words p-5 [overflow-wrap:anywhere] [&_img]:h-auto [&_img]:max-w-full [&_pre]:max-w-full [&_pre]:overflow-x-auto [&_pre]:whitespace-pre-wrap [&_table]:block [&_table]:max-w-full [&_table]:overflow-x-auto"
                dangerouslySetInnerHTML={{ __html: item.body }}
              />
            </section>
            <dl className="grid gap-3 text-sm sm:grid-cols-2 lg:grid-cols-4">
              <div className="rounded-xl border border-[var(--color-border)] p-4">
                <dt className="text-[var(--color-text-secondary)]">ผู้รับ</dt>
                <dd className="mt-2 font-semibold">
                  {item.audience === "all"
                    ? "ผู้ใช้ทุกคน"
                    : item.audience === "group"
                      ? "กลุ่มผู้ใช้"
                      : "รายบุคคล"}
                </dd>
              </div>
              <div className="rounded-xl border border-[var(--color-border)] p-4">
                <dt className="text-[var(--color-text-secondary)]">ส่งสำเร็จ</dt>
                <dd className="mt-2 text-lg font-semibold text-green-700">{item.sent_count} <span className="text-sm font-medium text-[var(--color-text-secondary)]">จาก {item.recipient_count} คน</span></dd>
              </div>
              <div className="rounded-xl border border-[var(--color-border)] p-4">
                <dt className="text-[var(--color-text-secondary)]">เปิดอ่าน</dt>
                <dd className="mt-2 text-lg font-semibold">{item.read_count} <span className="text-sm font-medium text-[var(--color-text-secondary)]">คน</span></dd>
              </div>
              <div className="rounded-xl border border-[var(--color-border)] p-4">
                <dt className="text-[var(--color-text-secondary)]">ส่งล้มเหลว</dt>
                <dd className="mt-2 text-lg font-semibold text-red-600">{item.failed_count} <span className="text-sm font-medium text-[var(--color-text-secondary)]">คน</span></dd>
              </div>
            </dl>
            {["sent", "partially_failed", "cancelled"].includes(
              item.status,
            ) && (
              <p className="rounded-lg bg-[var(--color-surface)] px-3 py-2 text-xs text-[var(--color-text-secondary)]">
                รายการที่ส่งหรือยกเลิกแล้วดูรายละเอียดได้
                แต่ไม่สามารถแก้ไขหรือลบได้
              </p>
            )}
          </div>
        )}
        <DialogFooter>
          <Button variant="outline" onClick={onClose}>ปิด</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
