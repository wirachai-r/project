// components/ui/PlaceholderPanel.tsx
import { cn } from "../../lib/utils";

interface PlaceholderPanelProps {
  icon: React.ReactNode;
  title: string;
  description?: string;
  action?: React.ReactNode;
  className?: string;
  bordered?: boolean;
}

export function PlaceholderPanel({
  icon,
  title,
  description,
  action,
  className,
  bordered = false,
}: PlaceholderPanelProps) {
  return (
    <div
      data-slot="placeholder-panel"
      className={cn(
        "flex flex-col items-center justify-center gap-4 py-16 text-center",
        bordered && "rounded-xl border border-dashed border-[var(--color-border)]",
        className
      )}
    >
      <div className="flex h-14 w-14 items-center justify-center rounded-full bg-[var(--color-surface)] [&_svg]:size-7 [&_svg]:text-[var(--color-text-secondary)]">
        {icon}
      </div>
      <div>
        <p className="font-medium text-[var(--color-text-primary)]">{title}</p>
        {description && (
          <p className="mt-1 text-sm text-[var(--color-text-secondary)]">
            {description}
          </p>
        )}
      </div>
      {action && <div className="mt-1">{action}</div>}
    </div>
  );
}