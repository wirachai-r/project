import { useLocation, useNavigate } from "react-router-dom";
import { PanelLeft, Bell } from "lucide-react";
import { cn } from "../../lib/utils";
import { useSidebarContext } from "../ui/SidebarContext";
import { getActiveNavMatch } from "../../lib/constants";
import { useBreadcrumbStore } from "../../stores/breadcrumbStore";
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from "../ui/Tooltip";
import { useUnreadNotificationCount } from "@/features/account/hooks/useUnreadNotificationCount";

function getDefaultBreadcrumbs(pathname: string): string[] {
  if (pathname === "/profile") return ["บัญชีของฉัน", "โปรไฟล์ของฉัน"];
  if (pathname === "/my-notifications") return ["บัญชีของฉัน", "การแจ้งเตือน"];

  const match = getActiveNavMatch(pathname);

  if (!match) return ["หน้าแรก"];

  const crumbs = [match.section.title, match.item.label];
  if (match.sub) crumbs.push(match.sub.label);

  return crumbs;
}

export function Topbar() {
  const navigate = useNavigate();
  const { toggleSidebar } = useSidebarContext();
  const { pathname } = useLocation();
  const extra = useBreadcrumbStore((s) => s.extra);
  const unreadCount = useUnreadNotificationCount().data ?? 0;

  const crumbs = [...getDefaultBreadcrumbs(pathname), ...(extra ?? [])];
  const currentCrumb = crumbs[crumbs.length - 1];

  return (
    <header className="sticky top-0 z-10 flex h-16 items-center justify-between border-b border-[var(--color-border)] bg-white/95 px-4 backdrop-blur supports-[backdrop-filter]:bg-white/80 lg:px-6">
      <div className="flex min-w-0 items-center gap-3">
        <button
          onClick={toggleSidebar}
          className={cn(
            "flex h-9 w-9 shrink-0 items-center justify-center rounded-md text-[var(--color-text-secondary)] transition-colors",
            "hover:bg-[var(--color-surface)] hover:text-[var(--color-text-primary)]",
            "focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-primary)] focus-visible:ring-offset-2",
          )}
          aria-label="เปิด/ปิดเมนู"
        >
          <PanelLeft className="h-4 w-4" />
        </button>

        <span className="hidden h-4 w-px bg-[var(--color-border)] md:block" />

        {/* Mobile (< md): โชว์แค่ชื่อหน้าปัจจุบัน ไฮไลต์อย่างเดียว ไม่มี trail */}
        <span className="truncate text-sm font-medium text-[var(--color-text-primary)] md:hidden">
          {currentCrumb}
        </span>

        {/* Desktop (>= md): breadcrumb เต็ม */}
        <nav className="hidden min-w-0 items-center gap-1.5 text-sm md:flex">
          {crumbs.map((crumb, i) => (
            <span key={i} className="flex items-center gap-1.5">
              {i > 0 && (
                <span className="text-[var(--color-text-secondary)]">/</span>
              )}
              <span
                className={cn(
                  "truncate",
                  i === crumbs.length - 1
                    ? "font-medium text-[var(--color-text-primary)]"
                    : "text-[var(--color-text-secondary)]",
                )}
              >
                {crumb}
              </span>
            </span>
          ))}
        </nav>
      </div>

      <TooltipProvider>
        <Tooltip>
          <TooltipTrigger asChild>
            <button
              onClick={() => navigate("/my-notifications")}
              className={cn(
                "relative flex h-9 w-9 shrink-0 items-center justify-center rounded-md text-[var(--color-text-secondary)] transition-colors",
                "hover:bg-[var(--color-surface)] hover:text-[var(--color-text-primary)]",
                "focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-primary)] focus-visible:ring-offset-2",
              )}
              aria-label={`การแจ้งเตือน${unreadCount ? `ที่ยังไม่อ่าน ${unreadCount} รายการ` : ""}`}
            >
              <Bell className="h-4 w-4" />
              {unreadCount > 0 && <span className="absolute right-0 top-0 flex h-4 min-w-4 items-center justify-center rounded-full bg-red-500 px-1 text-[9px] font-semibold text-white">{unreadCount > 99 ? "99+" : unreadCount}</span>}
            </button>
          </TooltipTrigger>
          <TooltipContent>การแจ้งเตือน{unreadCount > 0 ? ` · ยังไม่อ่าน ${unreadCount} รายการ` : ""}</TooltipContent>
        </Tooltip>
      </TooltipProvider>
    </header>
  );
}
