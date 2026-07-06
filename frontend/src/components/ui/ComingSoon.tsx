import { Construction } from "lucide-react";

interface ComingSoonProps {
  title?: string;
  description?: string;
}

export function ComingSoon({
  title = "กำลังพัฒนา",
  description = "หน้านี้อยู่ระหว่างการพัฒนา",
}: ComingSoonProps) {
  return (
    <div className="flex h-96 flex-col items-center justify-center gap-4 rounded-xl border border-dashed border-[var(--color-border)]">
      <div className="flex h-14 w-14 items-center justify-center rounded-full bg-[var(--color-surface)]">
        <Construction className="h-7 w-7 text-[var(--color-text-secondary)]" />
      </div>
      <div className="text-center">
        <p className="font-medium text-[var(--color-text-primary)]">{title}</p>
        <p className="mt-1 text-sm text-[var(--color-text-secondary)]">{description}</p>
      </div>
    </div>
  );
}