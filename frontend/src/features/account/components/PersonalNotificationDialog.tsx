import { ExternalLink } from "lucide-react";
import { Button } from "@/components/ui/Button";
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from "@/components/ui/Dialog";
import { formatAdminDateTime } from "@/lib/formatDate";
import type { PersonalNotification } from "@/types/notification";

export function PersonalNotificationDialog({ item, onClose }: { item: PersonalNotification | null; onClose: () => void }) {
  return <Dialog open={item !== null} onOpenChange={(open) => { if (!open) onClose(); }}><DialogContent maxWidth="lg" className="max-h-[90vh] min-w-0 overflow-y-auto"><DialogHeader><DialogTitle>รายละเอียดการแจ้งเตือน</DialogTitle></DialogHeader>{item && <article className="min-w-0 overflow-hidden rounded-2xl border border-[var(--color-border)]"><div className="border-b border-[var(--color-border)] bg-[var(--color-primary-light)]/35 px-5 py-4"><h2 className="break-words text-lg font-semibold [overflow-wrap:anywhere]">{item.title}</h2><time className="mt-1 block text-xs text-[var(--color-text-secondary)]">{formatAdminDateTime(item.created_at)}</time></div><div className="prose prose-sm max-w-none break-words p-5 [overflow-wrap:anywhere] [&_img]:h-auto [&_img]:max-w-full [&_pre]:max-w-full [&_pre]:overflow-x-auto [&_pre]:whitespace-pre-wrap [&_table]:block [&_table]:max-w-full [&_table]:overflow-x-auto" dangerouslySetInnerHTML={{ __html: item.body }} /></article>}<DialogFooter><Button variant="outline" onClick={onClose}>ปิด</Button>{item?.target_url && <Button onClick={() => window.location.assign(item.target_url!)}><ExternalLink />เปิดหน้าที่เกี่ยวข้อง</Button>}</DialogFooter></DialogContent></Dialog>;
}
