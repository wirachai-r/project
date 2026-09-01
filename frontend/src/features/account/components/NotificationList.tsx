import { Bell, Check, Info, ShieldAlert, Trash2, TriangleAlert, UserRound } from "lucide-react";
import { Button } from "@/components/ui/Button";
import { EmptyState } from "@/components/ui/EmptyState";
import { formatAdminDateTime } from "@/lib/formatDate";
import { cn } from "@/lib/utils";
import type { PersonalNotification } from "@/types/notification";
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from "@/components/ui/Tooltip";

interface NotificationListProps {
  items: PersonalNotification[];
  busyId: number | null;
  onRead: (item: PersonalNotification) => void;
  onDismiss: (id: number) => void;
}

const typeIcon = { S: Bell, U: UserRound, W: TriangleAlert, E: ShieldAlert, I: Info };

export function NotificationList({ items, busyId, onRead, onDismiss }: NotificationListProps) {
  if (items.length === 0) return <EmptyState title="ยังไม่มีการแจ้งเตือน" description="การแจ้งเตือนใหม่ของคุณจะแสดงที่นี่" className="min-h-64" />;

  return (
    <TooltipProvider>
    <div className="divide-y divide-[var(--color-border)]">
      {items.map((item) => {
        const Icon = typeIcon[item.type];
        const unread = item.is_read === "N";
        return (
          <article key={item.id} className={cn("flex gap-3 p-4 sm:p-5", unread && "bg-[var(--color-primary-light)]/25")}>
            <div className="mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[var(--color-primary-light)] text-[var(--color-primary)]"><Icon className="h-5 w-5" /></div>
            <button type="button" className="min-w-0 flex-1 text-left" onClick={() => onRead(item)}>
              <div className="flex items-start gap-2">
                <h2 className={cn("text-sm text-[var(--color-text-primary)]", unread ? "font-semibold" : "font-medium")}>{item.title}</h2>
                {unread && <span className="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-[var(--color-primary)]" aria-label="ยังไม่ได้อ่าน" />}
              </div>
              <p className="mt-1 line-clamp-3 break-words text-sm leading-6 text-[var(--color-text-secondary)] [overflow-wrap:anywhere]">{item.body_text}</p>
              <time className="mt-2 block text-xs text-[var(--color-text-secondary)]">{formatAdminDateTime(item.created_at)}</time>
            </button>
            <div className="flex shrink-0 items-start gap-1">
              {unread && <Tooltip><TooltipTrigger asChild><Button size="icon" variant="ghost" disabled={busyId === item.id} aria-label="ทำเครื่องหมายว่าอ่านแล้ว" onClick={() => onRead(item)}><Check /></Button></TooltipTrigger><TooltipContent>ทำเครื่องหมายว่าอ่านแล้ว</TooltipContent></Tooltip>}
              <Tooltip><TooltipTrigger asChild><Button size="icon" variant="ghost" disabled={busyId === item.id} aria-label="ลบการแจ้งเตือน" onClick={() => onDismiss(item.id)}><Trash2 /></Button></TooltipTrigger><TooltipContent>ลบการแจ้งเตือน</TooltipContent></Tooltip>
            </div>
          </article>
        );
      })}
    </div>
    </TooltipProvider>
  );
}
