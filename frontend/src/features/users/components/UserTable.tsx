import { MoreHorizontal, Pencil, Ban, CheckCircle, UserRound } from "lucide-react";
import type { User } from "@/types/user";
import { DataTable, type Column } from "../../../components/ui/DataTable";
import { Badge } from "../../../components/ui/Badge";
import { Button } from "../../../components/ui/Button";
import { Checkbox } from "../../../components/ui/Checkbox";
import { StatusToggle } from "../../../components/ui/StatusToggle";
import { TableSkeleton } from "../../../components/ui/TableSkeleton";
import {
  Tooltip,
  TooltipTrigger,
  TooltipContent,
} from "../../../components/ui/Tooltip";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "../../../components/ui/DropdownMenu";

interface UserTableProps {
  data: User[];
  loading: boolean;
  selectedIds: string[];
  onSelectedIdsChange: (ids: string[]) => void;
  onEdit: (user: User) => void;
  onToggleStatus: (user: User) => void;
  sortKey: string | null;
  sortDirection: "asc" | "desc" | null;
  onSortChange: (key: string, direction: "asc" | "desc" | null) => void;
}

function formatDate(dateStr: string | null) {
  if (!dateStr) return "-";
  return new Date(dateStr).toLocaleDateString("th-TH", {
    day: "2-digit",
    month: "short",
    year: "numeric",
  });
}

export function UserTable({
  data,
  loading,
  selectedIds,
  onSelectedIdsChange,
  onEdit,
  onToggleStatus,
  sortKey,
  sortDirection,
  onSortChange,
}: UserTableProps) {
  const allSelected =
    data.length > 0 &&
    data
      .filter((u) => u.role !== "Admin")
      .every((u) => selectedIds.includes(u.user_id)) &&
    data.some((u) => u.role !== "Admin");

  const toggleAll = () => {
    const selectableIds = data
      .filter((u) => u.role !== "Admin")
      .map((u) => u.user_id);
    onSelectedIdsChange(allSelected ? [] : selectableIds);
  };

  const toggleOne = (id: string) => {
    onSelectedIdsChange(
      selectedIds.includes(id)
        ? selectedIds.filter((i) => i !== id)
        : [...selectedIds, id],
    );
  };

  if (loading) {
    return (
      <TableSkeleton
        columns={6}
        rows={5}
        columnWidths={["w-5", "w-40", "w-24", "w-20", "w-24", "w-16"]}
      />
    );
  }

  const columns: Column<User>[] = [
    {
      key: "select",
      label: <Checkbox checked={allSelected} onCheckedChange={toggleAll} />,
      className: "w-10",
      render: (user) => (
        <Checkbox
          checked={selectedIds.includes(user.user_id)}
          onCheckedChange={() => toggleOne(user.user_id)}
          disabled={user.role === "Admin"}
        />
      ),
    },
    {
      key: "name",
      label: "ชื่อ-นามสกุล",
      sortable: true,
      render: (user) => (
        <div className="flex items-center gap-3">
          <div className="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-full border border-[var(--color-border)] bg-[var(--color-primary-light)]">
            {user.profile_image ? (
              <img
                src={user.profile_image}
                alt={`${user.first_name} ${user.last_name}`}
                className="h-full w-full object-cover"
              />
            ) : (
              <UserRound className="h-5 w-5 text-[var(--color-primary)]" />
            )}
          </div>
          <div className="min-w-0">
            <p className="truncate font-medium text-[var(--color-text-primary)]">
              {user.first_name} {user.last_name}
            </p>
            <p className="truncate text-xs text-[var(--color-text-secondary)]">
              {user.email}
            </p>
          </div>
        </div>
      ),
    },
    {
      key: "role",
      label: "บทบาท",
      sortable: true,
      render: (user) => (
        <Badge variant={user.role === "Admin" ? "primary" : "default"}>
          {user.role === "Admin" ? "ผู้ดูแลระบบ" : "ผู้ใช้ทั่วไป"}
        </Badge>
      ),
    },
    {
      key: "last_login",
      label: "เข้าใช้ล่าสุด",
      sortable: true,
      render: (user) => (
        <span className="text-[var(--color-text-secondary)]">
          {formatDate(user.last_login_at)}
        </span>
      ),
    },
    {
      key: "status",
      label: "สถานะ",
      render: (user) => (
        <Tooltip>
          <TooltipTrigger asChild>
            <span>
              <StatusToggle
                active={user.status === "1"}
                onChange={() => onToggleStatus(user)}
                disabled={user.role === "Admin"}
              />
            </span>
          </TooltipTrigger>
          <TooltipContent>
            {user.status === "1" ? "คลิกเพื่อปิดใช้งาน" : "คลิกเพื่อเปิดใช้งาน"}
          </TooltipContent>
        </Tooltip>
      ),
    },
    {
      key: "actions",
      label: "",
      className: "w-10 text-right",
      render: (user) => (
        <DropdownMenu>
          <DropdownMenuTrigger asChild>
            <Button variant="ghost" size="icon">
              <MoreHorizontal className="h-4 w-4" />
            </Button>
          </DropdownMenuTrigger>
          <DropdownMenuContent align="end">
            <DropdownMenuItem onClick={() => onEdit(user)}>
              <Pencil className="h-4 w-4 text-[var(--color-text-secondary)]" />
              แก้ไขข้อมูล
            </DropdownMenuItem>
            {user.role !== "Admin" && (
              <>
                <DropdownMenuSeparator />
                <DropdownMenuItem
                  onClick={() => onToggleStatus(user)}
                  className={
                    user.status === "1"
                      ? "text-[var(--color-danger)] focus:bg-[var(--color-danger)]/10 focus:text-[var(--color-danger)]"
                      : ""
                  }
                >
                  {user.status === "1" ? (
                    <>
                      <Ban className="h-4 w-4" />
                      ปิดใช้งานผู้ใช้
                    </>
                  ) : (
                    <>
                      <CheckCircle className="h-4 w-4" />
                      เปิดใช้งานผู้ใช้
                    </>
                  )}
                </DropdownMenuItem>
              </>
            )}
          </DropdownMenuContent>
        </DropdownMenu>
      ),
    },
  ];

  return (
    <DataTable
      columns={columns}
      data={data}
      keyExtractor={(user) => user.user_id}
      emptyMessage="ไม่พบผู้ใช้งาน"
      sortKey={sortKey ?? undefined}
      sortDirection={sortDirection}
      onSortChange={onSortChange}
    />
  );
}
