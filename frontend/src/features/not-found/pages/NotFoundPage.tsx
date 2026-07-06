// src/features/not-found/pages/NotFoundPage.tsx
import { useNavigate } from "react-router-dom";
import { Stethoscope } from "lucide-react";

export function NotFoundPage() {
  const navigate = useNavigate();

  return (
    <div className="flex min-h-screen flex-col items-center justify-center bg-[var(--color-surface)] px-4 text-center">
      {/* Icon */}
      <div className="mb-6 flex h-20 w-20 items-center justify-center rounded-3xl bg-[var(--color-primary-light)]">
        <Stethoscope className="h-10 w-10 text-[var(--color-primary)]" />
      </div>

      {/* Number */}
      <p className="text-8xl font-bold tracking-tight text-[var(--color-primary)]">
        404
      </p>

      {/* Message */}
      <h1 className="mt-4 text-xl font-semibold text-[var(--color-text-primary)]">
        ไม่พบหน้าที่ต้องการ
      </h1>
      <p className="mt-2 text-sm text-[var(--color-text-secondary)]">
        URL ที่ระบุไม่มีอยู่ในระบบ หรืออาจถูกย้ายไปแล้ว
      </p>

      {/* Actions */}
      <div className="mt-8 flex gap-3">
        <button
          onClick={() => navigate(-1)}
          className="rounded-lg border border-[var(--color-border)] px-4 py-2 text-sm font-medium text-[var(--color-text-primary)] transition-colors hover:bg-white"
        >
          ย้อนกลับ
        </button>
        <button
          onClick={() => navigate("/dashboard", { replace: true })}
          className="rounded-lg bg-[var(--color-primary)] px-4 py-2 text-sm font-medium text-white transition-opacity hover:opacity-90"
        >
          กลับหน้าหลัก
        </button>
      </div>
    </div>
  );
}