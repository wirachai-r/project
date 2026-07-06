import { useNavigate } from "react-router-dom";
import { LogOut, Menu } from "lucide-react";
import { useAuthStore } from "../../stores/authStore";
import { api } from "../../lib/api";

interface TopbarProps {
  onMenuClick?: () => void;
}

export function Topbar({ onMenuClick }: TopbarProps) {
  const { user, clearAuth } = useAuthStore();
  const navigate = useNavigate();

  const handleLogout = async () => {
    try {
      await api.post("/auth/logout");
    } catch {
      // ignore network errors on logout
    } finally {
      clearAuth();
      navigate("/login", { replace: true });
    }
  };

  const initials = user
    ? `${user.first_name[0] ?? ""}${user.last_name[0] ?? ""}`
    : "";

  return (
    <header className="flex h-16 items-center justify-between border-b border-[var(--color-border)] bg-white px-4 lg:px-6">
      {/* Hamburger — แสดงเฉพาะ mobile */}
      <button
        onClick={onMenuClick}
        className="flex h-9 w-9 items-center justify-center rounded-lg text-[var(--color-text-secondary)] transition-colors hover:bg-[var(--color-surface)] hover:text-[var(--color-text-primary)] lg:hidden"
        aria-label="เปิดเมนู"
      >
        <Menu className="h-5 w-5" />
      </button>

      {/* Spacer บน desktop (ซ้ายว่าง) */}
      <div className="hidden lg:block" />

      {/* User info + logout */}
      <div className="flex items-center gap-3 lg:gap-4">
        <div className="hidden text-right sm:block">
          <p className="text-sm font-medium leading-none text-[var(--color-text-primary)]">
            {user ? `${user.first_name} ${user.last_name}` : ""}
          </p>
          <p className="mt-1 text-xs text-[var(--color-text-secondary)]">{user?.role}</p>
        </div>
        <div className="flex h-9 w-9 items-center justify-center rounded-full bg-[var(--color-primary-light)] text-xs font-semibold text-[var(--color-primary)]">
          {initials}
        </div>
        <button
          onClick={handleLogout}
          className="flex h-9 w-9 items-center justify-center rounded-lg text-[var(--color-text-secondary)] transition-colors hover:bg-[var(--color-surface)] hover:text-[var(--color-danger)]"
          aria-label="ออกจากระบบ"
          title="ออกจากระบบ"
        >
          <LogOut className="h-4 w-4" />
        </button>
      </div>
    </header>
  );
}