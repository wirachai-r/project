import { useState, type FormEvent } from "react";
import { Navigate, useNavigate } from "react-router-dom";
import { Eye, EyeOff, ArrowRight } from "lucide-react";
import { isAxiosError } from "axios";
import { toast } from "sonner";
import { api } from "../../../lib/api";
import { useAuthStore } from "../../../stores/authStore";
import { Card } from "../../../components/ui/Card";
import { Input } from "../../../components/ui/Input";
import { Button } from "../../../components/ui/Button";

export function LoginPage() {
  const { isAuthenticated, setAuth } = useAuthStore();
  const navigate = useNavigate();

  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [showPassword, setShowPassword] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);

  if (isAuthenticated) {
    return <Navigate to="/dashboard" replace />;
  }

  const handleSubmit = async (e: FormEvent) => {
    e.preventDefault();
    setError(null);
    setLoading(true);

    try {
      const { data } = await api.post("/auth/login", { email, password });

      if (data.user?.role !== "Admin") {
        const message = "บัญชีนี้ไม่มีสิทธิ์เข้าถึงระบบจัดการ";
        setError(message);
        toast.error(message);
        return;
      }

      setAuth(data.token, data.user);
      toast.success("เข้าสู่ระบบสำเร็จ");
      navigate("/dashboard", { replace: true });
    } catch (err: unknown) {
      const message = isAxiosError<{ message?: string }>(err)
        ? err.response?.data?.message
        : undefined;
      const errorMessage =
        message ?? "เข้าสู่ระบบไม่สำเร็จ กรุณาลองใหม่อีกครั้ง";
      setError(errorMessage);
      toast.error(errorMessage);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="flex min-h-screen flex-col items-center justify-center gap-6 bg-[var(--color-surface)] px-4 py-10">
      <Card className="grid w-full max-w-4xl overflow-hidden p-0 shadow-xl md:grid-cols-2">
        {/* ฝั่งซ้าย: แบรนด์ */}
        <div className="flex flex-col justify-between bg-[var(--color-primary)] p-10 text-white">
          <div>
            <div className="mb-10 flex items-center gap-3">
              <div className="flex h-14 w-14 items-center justify-center rounded-2xl bg-white">
                <img
                  src="/logo_white.png"
                  alt="Checkup Logo"
                  className="h-10 w-10 object-contain"
                />
              </div>
              <span className="text-2xl font-bold">CHECKUP</span>
            </div>

            <h2 className="text-3xl font-bold leading-tight">
              แอปพลิเคชันประเมินอาการ
              <br />
              เจ็บป่วยเบื้องต้น
            </h2>
          </div>

          <p className="text-sm text-white/80">
            ยินดีต้อนรับสู่ระบบบริหารจัดการ
          </p>
        </div>

        {/* ฝั่งขวา: ฟอร์ม */}
        <div className="flex flex-col justify-center p-10">
          <h1 className="text-xl font-bold text-[var(--color-text-primary)]">
            เข้าสู่ระบบผู้ดูแล
          </h1>
          <p className="mt-1 text-sm text-[var(--color-text-secondary)]">
            กรุณากรอกข้อมูลส่วนตัวเพื่อเข้าสู่แผงควบคุม
          </p>

          <form onSubmit={handleSubmit} className="mt-8 flex flex-col gap-5">
            {error && (
              <div className="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-600">
                {error}
              </div>
            )}

            <Input
              label="ชื่อผู้ใช้งาน"
              type="email"
              required
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              placeholder="ระบุชื่อผู้ใช้งานของคุณ"
              className="placeholder:text-[var(--color-text-secondary)]"
            />

            <div>
              <Input
                label="รหัสผ่าน"
                type={showPassword ? "text" : "password"}
                required
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                placeholder="ระบุรหัสผ่านของคุณ"
                className="placeholder:text-[var(--color-text-secondary)]"
                rightIcon={
                  <button
                    type="button"
                    onClick={() => setShowPassword((v) => !v)}
                    aria-label={showPassword ? "ซ่อนรหัสผ่าน" : "แสดงรหัสผ่าน"}
                    tabIndex={-1}
                  >
                    {showPassword ? (
                      <EyeOff className="h-4 w-4" />
                    ) : (
                      <Eye className="h-4 w-4" />
                    )}
                  </button>
                }
              />
              {/* 
              <div className="mt-2 flex justify-end">
                
                  href="#"
                  className="text-sm font-medium text-[var(--color-primary)] hover:underline"
                >
                  ลืมรหัสผ่าน?
                </a>
              </div> */}
            </div>
            <Button
              type="submit"
              loading={loading}
              size="lg"
              className="w-full"
            >
              {!loading && (
                <>
                  เข้าสู่ระบบ
                  <ArrowRight className="ml-2 h-4 w-4" />
                </>
              )}
              {loading && "กำลังเข้าสู่ระบบ..."}
            </Button>
          </form>
        </div>
      </Card>

      <div className=" w-full text-center text-xs text-[var(--color-text-secondary)]">
        © 2026 CHECKUP : แอปพลิเคชันประเมินอาการเจ็บป่วยเบื้องต้น
      </div>
    </div>
  );
}
