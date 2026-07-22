// components/ui/EmptyState.tsx
import { Inbox } from "lucide-react";
import { PlaceholderPanel } from "./PlaceholderPanel";

interface EmptyStateProps {
  title?: string;
  description?: string;
  action?: React.ReactNode;
  className?: string;
}

export function EmptyState({
  title = "ไม่พบข้อมูล",
  description,
  action,
  className,
}: EmptyStateProps) {
  return (
    <PlaceholderPanel
      icon={<Inbox />}
      title={title}
      description={description}
      action={action}
      className={className}
    />
  );
}