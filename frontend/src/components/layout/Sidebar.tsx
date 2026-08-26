import { useEffect, useState } from "react";
import { NavLink, useLocation } from "react-router-dom";
import { X, ChevronDown } from "lucide-react";
import { NAV_SECTIONS, getActiveNavMatch } from "../../lib/constants";
import { cn } from "../../lib/utils";
import { useSidebarContext } from "../ui/SidebarContext";
import { SidebarUserMenu } from "./SidebarUserMenu";
import { api } from "../../lib/api";

function ReportCountBadge({ count, collapsed = false }: { count: number; collapsed?: boolean }) {
  if (count <= 0) return null;
  return (
    <span
      className={cn(
        "inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-red-500 px-1.5 text-[10px] font-semibold leading-none text-white",
        collapsed && "lg:absolute lg:right-1 lg:top-1 lg:h-4 lg:min-w-4 lg:px-1",
      )}
      aria-label={`${count} รายงานรอตรวจสอบ`}
    >
      {count > 99 ? "99+" : count}
    </span>
  );
}

export function Sidebar() {
  const { collapsed, mobileOpen, setMobileOpen } = useSidebarContext();
  const { pathname } = useLocation();
  const [reportCounts, setReportCounts] = useState({ comments: 0, feedback: 0 });

  useEffect(() => {
    let active = true;
    const loadCounts = async () => {
      try {
        const [comments, feedback] = await Promise.all([
          api.get("/admin/article-comment-reports", {
            params: { status: "pending", per_page: 1 },
          }),
          api.get("/admin/feedback", {
            params: { status: "pending", per_page: 1 },
          }),
        ]);
        if (active) {
          setReportCounts({
            comments: Number(comments.data.total ?? 0),
            feedback: Number(feedback.data.total ?? 0),
          });
        }
      } catch {
        // เมนูยังใช้งานได้ตามปกติหากโหลดตัวเลขไม่สำเร็จ
      }
    };
    void loadCounts();
    const timer = window.setInterval(() => void loadCounts(), 30_000);
    return () => {
      active = false;
      window.clearInterval(timer);
    };
  }, [pathname]);

  const activeMatch = getActiveNavMatch(pathname);
  const activeItemTo = activeMatch?.item.to ?? null;
  const activeSubTo = activeMatch?.sub?.to ?? null;
  const activeGroupTo =
    activeItemTo && activeMatch?.item.items?.length ? activeItemTo : null;

  const [openGroups, setOpenGroups] = useState<Set<string>>(() => {
    const initial = new Set<string>();
    if (activeGroupTo) initial.add(activeGroupTo);
    return initial;
  });

  // เก็บ path ที่เคย auto-open ไปแล้วจาก render ก่อนหน้า เพื่อเทียบตอน render
  // (ไม่ใช้ useEffect เพราะเป็นการ derive state จาก props ของ render เดียวกัน)
  const [lastAutoOpened, setLastAutoOpened] = useState<string | null>(
    activeGroupTo
  );

  if (activeGroupTo && activeGroupTo !== lastAutoOpened) {
    setLastAutoOpened(activeGroupTo);
    setOpenGroups((prev) => {
      if (prev.has(activeGroupTo)) return prev;
      const next = new Set(prev);
      next.add(activeGroupTo);
      return next;
    });
  }

  const toggleGroup = (key: string) => {
    setOpenGroups((prev) => {
      const next = new Set(prev);
      if (next.has(key)) {
        next.delete(key);
      } else {
        next.add(key);
      }
      return next;
    });
  };

  return (
    <>
      {mobileOpen && (
        <div
          className="fixed inset-0 z-20 bg-black/40 lg:hidden"
          onClick={() => setMobileOpen(false)}
        />
      )}

      <aside
        className={cn(
          "fixed inset-y-0 left-0 z-30 flex h-full shrink-0 flex-col border-r border-[var(--color-border)] bg-white transition-all duration-300 ease-in-out",
          "lg:static lg:z-auto lg:translate-x-0",
          mobileOpen ? "translate-x-0" : "-translate-x-full",
          collapsed ? "w-64 lg:w-[72px]" : "w-64"
        )}
      >
        <button
          onClick={() => setMobileOpen(false)}
          className={cn(
            "absolute right-3 top-4 flex h-8 w-8 items-center justify-center rounded-md",
            "text-[var(--color-text-secondary)] transition-colors hover:bg-[var(--color-surface)]",
            "focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-primary)] focus-visible:ring-offset-2",
            "lg:hidden"
          )}
          aria-label="ปิดเมนู"
        >
          <X className="h-4 w-4" />
        </button>

        {/* Logo */}
        <div
          className={cn(
            "flex h-16 shrink-0 items-center gap-2 border-b border-[var(--color-border)] px-5",
            collapsed && "lg:justify-center lg:px-0"
          )}
        >
          <div className="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-md bg-[var(--color-primary)]">
            <img src="/logo.png" alt="CHECKUP Logo" className="h-6 w-6 object-contain" />
          </div>
          <div className={cn("min-w-0", collapsed && "lg:hidden")}>
            <p className="truncate text-sm font-semibold leading-none text-[var(--color-text-primary)]">
              CHECKUP Admin
            </p>
            <p className="mt-1 text-xs text-[var(--color-text-secondary)]">ระบบจัดการ</p>
          </div>
        </div>

        {/* Nav */}
        <nav className="flex-1 space-y-6 overflow-y-auto overflow-x-hidden px-3 py-4">
          {NAV_SECTIONS.map((section) => (
            <div key={section.title}>
              <p
                className={cn(
                  "px-3 pb-2 text-xs font-medium uppercase tracking-wide text-[var(--color-text-secondary)]",
                  collapsed && "lg:hidden"
                )}
              >
                {section.title}
              </p>
              <div className="space-y-0.5">
                {section.items.map((item) => {
                  const hasSubItems = !!item.items?.length;

                  if (!hasSubItems) {
                    const isActive = item.to === activeItemTo;

                    return (
                      <NavLink
                        key={item.to}
                        to={item.to}
                        onClick={() => setMobileOpen(false)}
                        title={collapsed ? item.label : undefined}
                        className={cn(
                          "relative flex items-center gap-2.5 rounded-md px-3 py-2 text-sm transition-colors",
                          "focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-primary)] focus-visible:ring-offset-1",
                          collapsed && "lg:justify-center lg:px-0",
                          isActive
                            ? "bg-[var(--color-primary-light)] font-medium text-[var(--color-primary)]"
                            : "text-[var(--color-text-secondary)] hover:bg-[var(--color-surface)] hover:text-[var(--color-text-primary)]"
                        )}
                      >
                        {isActive && (
                          <span
                            className={cn(
                              "absolute left-0 h-4 w-1 rounded-r-full bg-[var(--color-primary)]",
                              collapsed && "lg:hidden"
                            )}
                          />
                        )}
                        <item.icon className="h-4 w-4 shrink-0" />
                        <span className={cn("truncate", collapsed && "lg:hidden")}>
                          {item.label}
                        </span>
                        {item.to === "/feedback" && (
                          <ReportCountBadge
                            count={reportCounts.feedback}
                            collapsed={collapsed}
                          />
                        )}
                      </NavLink>
                    );
                  }

                  // รายการที่มีเมนูย่อย
                  const isGroupOpen = openGroups.has(item.to);
                  const isParentActive = item.to === activeItemTo;

                  return (
                    <div key={item.to}>
                      <button
                        onClick={() => toggleGroup(item.to)}
                        title={collapsed ? item.label : undefined}
                        className={cn(
                          "relative flex w-full items-center gap-2.5 rounded-md px-3 py-2 text-sm transition-colors",
                          "focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-primary)] focus-visible:ring-offset-1",
                          collapsed && "lg:justify-center lg:px-0",
                          isParentActive
                            ? "font-medium text-[var(--color-primary)]"
                            : "text-[var(--color-text-secondary)] hover:bg-[var(--color-surface)] hover:text-[var(--color-text-primary)]"
                        )}
                      >
                        {isParentActive && (
                          <span
                            className={cn(
                              "absolute left-0 h-4 w-1 rounded-r-full bg-[var(--color-primary)]",
                              collapsed && "lg:hidden"
                            )}
                          />
                        )}
                        <item.icon className="h-4 w-4 shrink-0" />
                        <span className={cn("flex-1 truncate text-left", collapsed && "lg:hidden")}>
                          {item.label}
                        </span>
                        {item.to === "/articles" && (
                          <ReportCountBadge
                            count={reportCounts.comments}
                            collapsed={collapsed}
                          />
                        )}
                        <ChevronDown
                          className={cn(
                            "h-3.5 w-3.5 shrink-0 transition-transform",
                            isGroupOpen && "rotate-180",
                            collapsed && "lg:hidden"
                          )}
                        />
                      </button>

                      {/* Sub-items — ซ่อนตอน sidebar พับเป็นไอคอน (desktop) */}
                      <div
                        className={cn(
                          "grid overflow-hidden transition-all duration-200",
                          isGroupOpen ? "grid-rows-[1fr] opacity-100" : "grid-rows-[0fr] opacity-0",
                          collapsed && "lg:hidden"
                        )}
                      >
                        <div className="min-h-0">
                          <div className="ml-4 space-y-0.5 border-l border-[var(--color-border)] py-1 pl-3">
                            {item.items!.map((sub) => {
                              const isSubActive = sub.to === activeSubTo;

                              return (
                                <NavLink
                                  key={sub.to}
                                  to={sub.to}
                                  onClick={() => setMobileOpen(false)}
                                  className={cn(
                                    "block rounded-md px-3 py-1.5 text-sm transition-colors",
                                    "focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-primary)] focus-visible:ring-offset-1",
                                    isSubActive
                                      ? "bg-[var(--color-primary-light)] font-medium text-[var(--color-primary)]"
                                      : "text-[var(--color-text-secondary)] hover:bg-[var(--color-surface)] hover:text-[var(--color-text-primary)]"
                                  )}
                                >
                                  <span className="flex items-center justify-between gap-2">
                                    <span>{sub.label}</span>
                                    {sub.to === "/articles/comment-reports" && (
                                      <ReportCountBadge count={reportCounts.comments} />
                                    )}
                                  </span>
                                </NavLink>
                              );
                            })}
                          </div>
                        </div>
                      </div>
                    </div>
                  );
                })}
              </div>
            </div>
          ))}
        </nav>

        <SidebarUserMenu collapsed={collapsed} />
      </aside>
    </>
  );
}
