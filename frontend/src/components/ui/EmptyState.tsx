import { Inbox } from "lucide-react";

interface EmptyStateProps {
  title?: string;
  description?: string;
  action?: React.ReactNode;
}

export function EmptyState({
  title = "ไม่พบข้อมูล",
  description,
  action,
}: EmptyStateProps) {
  return (
    <div className="flex flex-col items-center justify-center gap-3 py-16">
      <div className="flex h-12 w-12 items-center justify-center rounded-full bg-[var(--color-surface)]">
        <Inbox className="h-6 w-6 text-[var(--color-text-secondary)]" />
      </div>
      <div className="text-center">
        <p className="font-medium text-[var(--color-text-primary)]">{title}</p>
        {description && (
          <p className="mt-1 text-sm text-[var(--color-text-secondary)]">{description}</p>
        )}
      </div>
      {action && <div className="mt-2">{action}</div>}
    </div>
  );
}