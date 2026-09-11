import { useState, type FormEvent } from "react";
import { Navigate, useNavigate } from "react-router-dom";
import { Eye, EyeOff, ArrowRight } from "lucide-react";
import { isAxiosError } from "axios";
import { toast } from "sonner";
import { useGoogleLogin } from "@react-oauth/google";
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

  // ✅ ย้าย useGoogleLogin มาไว้ตรงนี้ (ก่อน if และก่อน return)
  const handleGoogleLogin = useGoogleLogin({
    onSuccess: async (tokenResponse) => {
      setError(null);
      setLoading(true);
      try {
        const { data } = await api.post("/auth/google", {
          token: tokenResponse.access_token,
        });

        if (data.user?.role !== "Admin") {
          const message = "บัญชีนี้ไม่มีสิทธิ์เข้าถึงระบบจัดการ";
          setError(message);
          toast.error(message);
          return;
        }

        setAuth(data.token, data.user);
        toast.success("เข้าสู่ระบบด้วย Google สำเร็จ");
        navigate("/dashboard", { replace: true });
      } catch (err: unknown) {
        const message = isAxiosError<{ message?: string }>(err)
          ? err.response?.data?.message
          : undefined;
        const errorMessage = message ?? "เข้าสู่ระบบด้วย Google ไม่สำเร็จ";
        setError(errorMessage);
        toast.error(errorMessage);
      } finally {
        setLoading(false);
      }
    },
    onError: () => {
      toast.error("การเชื่อมต่อกับ Google ล้มเหลว");
    },
  });

  // ✅ เงื่อนไขการ Redirect อยู่หลัง Hooks ทั้งหมด
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
                      <Eye className="h-4 w-4" />
                    ) : (
                      <EyeOff className="h-4 w-4" />
                    )}
                  </button>
                }
              />
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

          {/* ปุ่ม Google Login */}
          <div className="relative my-6 text-center">
            <div className="absolute inset-0 flex items-center">
              <div className="w-full border-t border-gray-200" />
            </div>
            <span className="relative bg-white px-3 text-xs text-[var(--color-text-secondary)]">
              หรือ
            </span>
          </div>

          <Button
            type="button"
            variant="outline"
            size="lg"
            className="w-full flex items-center justify-center gap-2"
            onClick={() => handleGoogleLogin()}
            disabled={loading}
          >
            <svg className="h-5 w-5" viewBox="0 0 24 24">
              <path
                fill="#4285F4"
                d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"
              />
              <path
                fill="#34A853"
                d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"
              />
              <path
                fill="#FBBC05"
                d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"
              />
              <path
                fill="#EA4335"
                d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"
              />
            </svg>
            เข้าสู่ระบบด้วย Google
          </Button>
        </div>
      </Card>

      <div className="w-full text-center text-xs text-[var(--color-text-secondary)]">
        © 2026 CHECKUP : แอปพลิเคชันประเมินอาการเจ็บป่วยเบื้องต้น
      </div>
    </div>
  );
}
