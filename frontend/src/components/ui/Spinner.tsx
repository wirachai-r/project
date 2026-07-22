// components/ui/Spinner.tsx
import { cn } from "../../lib/utils";

interface SpinnerProps {
  fullscreen?: boolean;
  label?: string;
  size?: "sm" | "md" | "lg";
  className?: string;
}

export function Spinner({ fullscreen = false, label, size = "md", className }: SpinnerProps) {
  const sizeClass = {
    sm: "h-5 w-5 border-2",
    md: "h-8 w-8 border-2",
    lg: "h-12 w-12 border-[3px]",
  }[size];

  const spinner = (
    <div
      role="status"
      aria-live="polite"
      className={cn("flex flex-col items-center gap-3", className)}
    >
      <div
        className={cn(
          sizeClass,
          "animate-spin rounded-full border-[var(--color-primary)] border-t-transparent"
        )}
      />
      <span className={label ? "text-sm text-[var(--color-text-secondary)]" : "sr-only"}>
        {label ?? "กำลังโหลด"}
      </span>
    </div>
  );

  if (fullscreen) {
    // absolute + inset-0 อ้างอิงจาก ancestor ที่ใกล้ที่สุดที่มี position: relative
    // ซึ่งคือ <main> ใน AdminLayout เสมอ ไม่ว่า Spinner จะถูกเรียกจากหน้าไหนก็ตาม
    return (
      <div className="absolute inset-0 z-10 flex items-center justify-center bg-[var(--color-surface)]">
        {spinner}
      </div>
    );
  }

  return spinner;
}