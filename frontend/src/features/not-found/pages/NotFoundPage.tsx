// src/features/not-found/pages/NotFoundPage.tsx
import { useNavigate } from "react-router-dom";
import { Button } from "../../../components/ui/Button";

export function NotFoundPage() {
  const navigate = useNavigate();

  return (
    <div className="flex min-h-[calc(100vh-4rem-3rem)] flex-col items-center justify-center px-4 text-center lg:min-h-[calc(100vh-4rem-4rem)]">

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
        <Button variant="outline" onClick={() => navigate(-1)} className="hover:bg-[var(--color-border)]/40">
          ย้อนกลับ
        </Button>
        <Button variant="primary" onClick={() => navigate("/dashboard", { replace: true })}>
          กลับหน้าหลัก
        </Button>
      </div>
    </div>
  );
}