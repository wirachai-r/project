import { MoreHorizontal, Eye, Pencil, Trash2 } from "lucide-react";
import * as Icons from "lucide-react";
import type { FirstAidCategory } from "@/types/firstAidCategory";
import { DataTable, type Column } from "../../../components/ui/DataTable";
import { Badge } from "../../../components/ui/Badge";
import { Button } from "../../../components/ui/Button";
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

interface FirstAidCategoryTableProps {
  data: FirstAidCategory[];
  loading: boolean;
  onView: (category: FirstAidCategory) => void;
  onEdit: (category: FirstAidCategory) => void;
  onToggleStatus: (category: FirstAidCategory) => void;
  onDelete: (category: FirstAidCategory) => void;
  sortKey: string | null;
  sortDirection: "asc" | "desc" | null;
  onSortChange: (key: string, direction: "asc" | "desc" | null) => void;
}

export function FirstAidCategoryTable({
  data,
  loading,
  onView,
  onEdit,
  onToggleStatus,
  onDelete,
  sortKey,
  sortDirection,
  onSortChange,
}: FirstAidCategoryTableProps) {
  if (loading) {
    return (
      <TableSkeleton
        columns={5}
        rows={5}
        columnWidths={["w-20", "w-48", "w-24", "w-20", "w-16"]}
      />
    );
  }

  const columns: Column<FirstAidCategory>[] = [
    {
      key: "id",
      label: "รหัส",
      sortable: true,
      render: (category) => (
        <span className="font-mono text-xs text-[var(--color-text-secondary)]">
          {category.first_aid_category_id}
        </span>
      ),
    },
    {
      key: "name",
      label: "หมวดหมู่",
      sortable: true,
      render: (category) => {
        const CategoryIcon = category.icon
          ? ((Icons as Record<string, unknown>)[category.icon] as
              | typeof Icons.Activity
              | undefined)
          : null;

        return (
          <div className="flex items-center gap-3">
            <div className="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-[var(--color-primary-light)]">
              {CategoryIcon ? (
                <CategoryIcon className="h-4 w-4 text-[var(--color-primary)]" />
              ) : (
                <Icons.HeartPulse className="h-4 w-4 text-[var(--color-primary)]" />
              )}
            </div>
            <div>
              <p className="font-medium text-[var(--color-text-primary)]">
                {category.category_name}
              </p>
              {category.category_name_en && (
                <p className="text-xs text-[var(--color-text-secondary)]">
                  {category.category_name_en}
                </p>
              )}
            </div>
          </div>
        );
      },
    },
    {
      key: "first_aids_count",
      label: "จำนวนรายการ",
      render: (category) => (
        <Badge variant="default">
          {category.first_aids_count ?? 0} รายการ
        </Badge>
      ),
    },
    {
      key: "status",
      label: "สถานะ",
      render: (category) => (
        <Tooltip>
          <TooltipTrigger asChild>
            <span>
              <StatusToggle
                active={category.status === "1"}
                onChange={() => onToggleStatus(category)}
              />
            </span>
          </TooltipTrigger>
          <TooltipContent>
            {category.status === "1" ? "คลิกเพื่อปิดใช้งาน" : "คลิกเพื่อเปิดใช้งาน"}
          </TooltipContent>
        </Tooltip>
      ),
    },
    {
      key: "actions",
      label: "",
      className: "w-10 text-right",
      render: (category) => (
        <DropdownMenu>
          <DropdownMenuTrigger asChild>
            <Button variant="ghost" size="icon">
              <MoreHorizontal className="h-4 w-4" />
            </Button>
          </DropdownMenuTrigger>
          <DropdownMenuContent align="end">
            <DropdownMenuItem onClick={() => onView(category)}>
              <Eye className="h-4 w-4 text-[var(--color-text-secondary)]" />
              ดูรายละเอียด
            </DropdownMenuItem>
            <DropdownMenuItem onClick={() => onEdit(category)}>
              <Pencil className="h-4 w-4 text-[var(--color-text-secondary)]" />
              แก้ไขข้อมูล
            </DropdownMenuItem>
            <DropdownMenuSeparator />
            <DropdownMenuItem
              onClick={() => onDelete(category)}
              className="text-[var(--color-danger)] focus:bg-[var(--color-danger)]/10 focus:text-[var(--color-danger)]"
            >
              <Trash2 className="h-4 w-4" />
              ลบหมวดหมู่
            </DropdownMenuItem>
          </DropdownMenuContent>
        </DropdownMenu>
      ),
    },
  ];

  return (
    <DataTable
      columns={columns}
      data={data}
      keyExtractor={(category) => category.first_aid_category_id}
      emptyMessage="ไม่พบหมวดหมู่ปฐมพยาบาล"
      sortKey={sortKey ?? undefined}
      sortDirection={sortDirection}
      onSortChange={onSortChange}
    />
  );
}