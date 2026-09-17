import { useNavigate } from "react-router-dom";
import { useEffect, useState } from "react";
import { toast } from "sonner";
import { ChevronsUpDown, UserRound, Bell, LogOut } from "lucide-react";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "../ui/DropdownMenu";
import {
  AlertDialog,
  AlertDialogContent,
  AlertDialogHeader,
  AlertDialogTitle,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogAction,
  AlertDialogCancel,
} from "../ui/AlertDialog";
import { useAuthStore } from "../../stores/authStore";
import { authApi } from "../../lib/api/auth";
import { cn } from "../../lib/utils";
import { useUnreadNotificationCount } from "@/features/account/hooks/useUnreadNotificationCount";

interface SidebarUserMenuProps {
  collapsed?: boolean;
}

const COMPACT_BREAKPOINT = 640;

function UserAvatar({
  src,
  className,
}: {
  src?: string | null;
  className?: string;
}) {
  const [failedSrc, setFailedSrc] = useState<string | null>(null);

  return (
    <div
      className={cn(
        "flex shrink-0 items-center justify-center overflow-hidden rounded-full border border-[var(--color-border)] bg-[var(--color-primary-light)] text-xs font-semibold text-[var(--color-primary)]",
        className,
      )}
    >
      {src && failedSrc !== src ? (
        <img
          src={src}
          alt="รูปโปรไฟล์"
          className="h-full w-full object-cover"
          referrerPolicy="no-referrer"
          onError={() => setFailedSrc(src)}
        />
      ) : (
        <UserRound className="h-4 w-4 text-[var(--color-primary)]" />
      )}
    </div>
  );
}

function useIsCompactViewport() {
  const [isCompact, setIsCompact] = useState(() =>
    typeof window !== "undefined"
      ? window.innerWidth < COMPACT_BREAKPOINT
      : false,
  );

  useEffect(() => {
    const handleResize = () =>
      setIsCompact(window.innerWidth < COMPACT_BREAKPOINT);
    window.addEventListener("resize", handleResize);
    return () => window.removeEventListener("resize", handleResize);
  }, []);

  return isCompact;
}

export function SidebarUserMenu({ collapsed }: SidebarUserMenuProps) {
  const { user, clearAuth } = useAuthStore();
  const isCompact = useIsCompactViewport();
  const navigate = useNavigate();
  const unreadCount = useUnreadNotificationCount().data ?? 0;

  const [logoutConfirmOpen, setLogoutConfirmOpen] = useState(false);
  const [loggingOut, setLoggingOut] = useState(false);

  const handleLogout = async () => {
    setLoggingOut(true);
    try {
      await authApi.logout();
    } catch {
      // ignore network errors on logout
    } finally {
      clearAuth();
      setLogoutConfirmOpen(false);
      setLoggingOut(false);
      toast.success("ออกจากระบบสำเร็จ");
      navigate("/login", { replace: true });
    }
  };

  return (
    <div className="border-t border-[var(--color-border)] p-2">
      <DropdownMenu>
        <DropdownMenuTrigger asChild>
          <button
            className={cn(
              "flex w-full min-w-0 items-center gap-2 rounded-md p-1.5 text-left transition-colors",
              "hover:bg-[var(--color-surface)]",
              "focus-visible:outline-none",
              collapsed && "lg:justify-center",
            )}
          >
            <UserAvatar
              src={user?.profile_image}
              className="h-8 w-8"
            />
            <div className={cn("min-w-0 flex-1", collapsed && "lg:hidden")}>
              <p className="truncate text-sm font-medium leading-none text-[var(--color-text-primary)]">
                {user ? `${user.first_name} ${user.last_name}` : ""}
              </p>
              <p className="mt-1 truncate text-xs text-[var(--color-text-secondary)]">
                {user?.email}
              </p>
            </div>
            <ChevronsUpDown
              className={cn(
                "h-4 w-4 shrink-0 text-[var(--color-text-secondary)]",
                collapsed && "lg:hidden",
              )}
            />
          </button>
        </DropdownMenuTrigger>

        <DropdownMenuContent
          side={isCompact ? "bottom" : "right"}
          align={isCompact ? "center" : "end"}
          sideOffset={isCompact ? 8 : 12}
          className={cn(
            "rounded-lg",
            isCompact ? "w-(--radix-dropdown-menu-trigger-width)" : "w-64",
          )}
        >
          <DropdownMenuLabel className="font-normal">
            <div className="flex items-center gap-2.5 py-1">
              <UserAvatar
                src={user?.profile_image}
                className="h-8 w-8"
              />
              <div className="min-w-0">
                <p className="truncate text-sm font-medium text-[var(--color-text-primary)]">
                  {user ? `${user.first_name} ${user.last_name}` : ""}
                </p>
                <p className="truncate text-xs text-[var(--color-text-secondary)]">
                  {user?.email}
                </p>
              </div>
            </div>
          </DropdownMenuLabel>

          <DropdownMenuSeparator />

          <DropdownMenuItem onClick={() => navigate("/profile")}>
            <UserRound className="h-4 w-4 text-[var(--color-text-secondary)]" />
            โปรไฟล์ของฉัน
          </DropdownMenuItem>

          <DropdownMenuItem onClick={() => navigate("/my-notifications")}>
            <Bell className="h-4 w-4 text-[var(--color-text-secondary)]" />
            <span className="flex-1">การแจ้งเตือน</span>
            {unreadCount > 0 && <span className="flex h-5 min-w-5 items-center justify-center rounded-full bg-red-500 px-1.5 text-[10px] font-semibold text-white">{unreadCount > 99 ? "99+" : unreadCount}</span>}
          </DropdownMenuItem>

          <DropdownMenuSeparator />

          <DropdownMenuItem
            onClick={() => setLogoutConfirmOpen(true)}
            className="text-[var(--color-danger)] focus:bg-[var(--color-danger)]/10 focus:text-[var(--color-danger)] [&_svg]:text-[var(--color-danger)]"
          >
            <LogOut className="h-4 w-4" />
            ออกจากระบบ
          </DropdownMenuItem>
        </DropdownMenuContent>
      </DropdownMenu>

      <AlertDialog
        open={logoutConfirmOpen}
        onOpenChange={setLogoutConfirmOpen}
      >
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>ยืนยันการออกจากระบบ</AlertDialogTitle>
            <AlertDialogDescription>
              คุณต้องการออกจากระบบใช่หรือไม่ ต้องเข้าสู่ระบบใหม่อีกครั้งเพื่อใช้งาน
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel disabled={loggingOut}>ยกเลิก</AlertDialogCancel>
            <AlertDialogAction onClick={handleLogout} loading={loggingOut}>
              ออกจากระบบ
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </div>
  );
}
