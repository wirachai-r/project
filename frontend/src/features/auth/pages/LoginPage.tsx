import { useState, type FormEvent } from "react";
import { Navigate, useNavigate } from "react-router-dom";
import { Stethoscope, Loader2 } from "lucide-react";
import { isAxiosError } from "axios";
import { api } from "../../../lib/api";
import { useAuthStore } from "../../../stores/authStore";

export function LoginPage() {
  const { isAuthenticated, setAuth } = useAuthStore();
  const navigate = useNavigate();

  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
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
        setError("บัญชีนี้ไม่มีสิทธิ์เข้าถึงระบบจัดการ");
        return;
      }

      setAuth(data.token, data.user);
      navigate("/dashboard", { replace: true });
    } catch (err: unknown) {
      const message = isAxiosError<{ message?: string }>(err)
        ? err.response?.data?.message
        : undefined;
      setError(message ?? "เข้าสู่ระบบไม่สำเร็จ กรุณาลองใหม่อีกครั้ง");
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="flex min-h-screen items-center justify-center bg-[var(--color-surface)] px-4">
      <div className="w-full max-w-sm">
        <div className="mb-8 flex flex-col items-center text-center">
          <div className="mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-[var(--color-primary)]">
            <Stethoscope className="h-6 w-6 text-white" />
          </div>
          <h1 className="text-xl font-semibold text-[var(--color-text-primary)]">
            เข้าสู่ระบบจัดการ
          </h1>
          <p className="mt-1 text-sm text-[var(--color-text-secondary)]">
            Checkup Admin Panel
          </p>
        </div>

        <form
          onSubmit={handleSubmit}
          className="rounded-2xl border border-[var(--color-border)] bg-white p-6 shadow-sm"
        >
          {error && (
            <div className="mb-4 rounded-lg bg-[#FFF1F1] px-3 py-2 text-sm text-[var(--color-danger)]">
              {error}
            </div>
          )}

          <label className="mb-1.5 block text-sm font-medium text-[var(--color-text-primary)]">
            อีเมล
          </label>
          <input
            type="email"
            required
            value={email}
            onChange={(e) => setEmail(e.target.value)}
            placeholder="admin@example.com"
            className="mb-4 w-full rounded-lg border border-[var(--color-border)] px-3 py-2 text-sm text-[var(--color-text-primary)] outline-none transition-colors focus:border-[var(--color-primary)]"
          />

          <label className="mb-1.5 block text-sm font-medium text-[var(--color-text-primary)]">
            รหัสผ่าน
          </label>
          <input
            type="password"
            required
            value={password}
            onChange={(e) => setPassword(e.target.value)}
            placeholder="••••••••"
            className="mb-6 w-full rounded-lg border border-[var(--color-border)] px-3 py-2 text-sm text-[var(--color-text-primary)] outline-none transition-colors focus:border-[var(--color-primary)]"
          />

          <button
            type="submit"
            disabled={loading}
            className="flex w-full items-center justify-center gap-2 rounded-lg bg-[var(--color-primary)] py-2.5 text-sm font-medium text-white transition-opacity hover:opacity-90 disabled:opacity-60"
          >
            {loading && <Loader2 className="h-4 w-4 animate-spin" />}
            เข้าสู่ระบบ
          </button>
        </form>
      </div>
    </div>
  );
}