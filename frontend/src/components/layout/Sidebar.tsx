import { NavLink } from "react-router-dom";
import { Stethoscope, X } from "lucide-react";
import { NAV_SECTIONS } from "../../lib/constants";

interface SidebarProps {
  onClose?: () => void;
}

export function Sidebar({ onClose }: SidebarProps) {
  return (
    <aside className="relative flex h-full w-64 shrink-0 flex-col border-r border-[var(--color-border)] bg-white">
      {/* ปุ่มปิด — แสดงเฉพาะ mobile */}
      <button
        onClick={onClose}
        className="absolute right-3 top-4 flex h-8 w-8 items-center justify-center rounded-lg text-[var(--color-text-secondary)] transition-colors hover:bg-[var(--color-surface)] lg:hidden"
        aria-label="ปิดเมนู"
      >
        <X className="h-4 w-4" />
      </button>

      {/* Logo */}
      <div className="flex items-center gap-2 px-5 py-5">
        <div className="flex h-9 w-9 items-center justify-center rounded-xl bg-[var(--color-primary)]">
          <Stethoscope className="h-5 w-5 text-white" />
        </div>
        <div>
          <p className="text-sm font-semibold leading-none text-[var(--color-text-primary)]">
            Checkup Admin
          </p>
          <p className="mt-1 text-xs text-[var(--color-text-secondary)]">ระบบจัดการ</p>
        </div>
      </div>

      {/* Nav */}
      <nav className="flex-1 space-y-6 overflow-y-auto px-3 pb-6">
        {NAV_SECTIONS.map((section) => (
          <div key={section.title}>
            <p className="px-3 pb-2 text-xs font-medium uppercase tracking-wide text-[var(--color-text-secondary)]">
              {section.title}
            </p>
            <div className="space-y-1">
              {section.items.map((item) => (
                <NavLink
                  key={item.to}
                  to={item.to}
                  onClick={onClose}
                  className={({ isActive }) =>
                    [
                      "relative flex items-center rounded-lg px-3 py-2 text-sm transition-colors",
                      isActive
                        ? "bg-[var(--color-primary-light)] font-medium text-[var(--color-primary)]"
                        : "text-[var(--color-text-secondary)] hover:bg-[var(--color-surface)] hover:text-[var(--color-text-primary)]",
                    ].join(" ")
                  }
                >
                  {({ isActive }) => (
                    <>
                      {isActive && (
                        <span className="absolute left-0 h-4 w-1 rounded-r-full bg-[var(--color-primary)]" />
                      )}
                      {item.label}
                    </>
                  )}
                </NavLink>
              ))}
            </div>
          </div>
        ))}
      </nav>
    </aside>
  );
}