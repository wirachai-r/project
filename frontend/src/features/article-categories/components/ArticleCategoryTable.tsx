import { MoreHorizontal, Newspaper, Pencil, Trash2 } from "lucide-react";
import { LUCIDE_ICONS } from "@/lib/lucideIconRegistry";
import type { ArticleCategory } from "@/types/articleCategory";
import { formatAdminDateTime } from "@/lib/formatDate";
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

interface ArticleCategoryTableProps {
  data: ArticleCategory[];
  loading: boolean;
  onView: (category: ArticleCategory) => void;
  onEdit: (category: ArticleCategory) => void;
  onToggleStatus: (category: ArticleCategory) => void;
  onDelete: (category: ArticleCategory) => void;
  sortKey: string | null;
  sortDirection: "asc" | "desc" | null;
  onSortChange: (key: string, direction: "asc" | "desc" | null) => void;
}

export function ArticleCategoryTable({
  data,
  loading,
  onEdit,
  onToggleStatus,
  onDelete,
  sortKey,
  sortDirection,
  onSortChange,
}: ArticleCategoryTableProps) {
  if (loading) {
    return (
      <TableSkeleton
        columns={5}
        columnWidths={["w-20", "w-48", "w-24", "w-20", "w-16"]}
      />
    );
  }

  const columns: Column<ArticleCategory>[] = [
    {
      key: "id",
      label: "รหัส",
      sortable: true,
      render: (category) => (
        <span className="font-mono text-xs text-[var(--color-text-secondary)]">
          {category.article_category_id}
        </span>
      ),
    },
    {
      key: "name",
      label: "หมวดหมู่",
      sortable: true,
      render: (category) => {
        const CategoryIcon = category.icon
          ? LUCIDE_ICONS[category.icon]
          : null;

        return (
          <div className="flex items-center gap-3">
            <div className="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-[var(--color-primary-light)]">
              {CategoryIcon ? (
                <CategoryIcon className="h-4 w-4 text-[var(--color-primary)]" />
              ) : (
                <Newspaper className="h-4 w-4 text-[var(--color-primary)]" />
              )}
            </div>
            <div>
              <p className="font-medium text-[var(--color-text-primary)]">
                {category.category_name}
              </p>
              {/* {category.category_name_en && (
                <p className="text-xs text-[var(--color-text-secondary)]">
                  {category.category_name_en}
                </p>
              )} */}
            </div>
          </div>
        );
      },
    },
    {
      key: "articles_count",
      label: "จำนวนบทความ",
      render: (category) => (
        <Badge variant="default">{category.articles_count ?? 0} รายการ</Badge>
      ),
    },
    {
      key: "updated_at",
      label: "แก้ไขล่าสุด",
      sortable: true,
      render: (category) => formatAdminDateTime(category.updated_at || category.created_at),
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
            <DropdownMenuItem onClick={() => onEdit(category)}>
              <Pencil className="h-4 w-4 text-[var(--color-text-secondary)]" />
              แก้ไขข้อมูล
            </DropdownMenuItem>
            <DropdownMenuSeparator />
            <DropdownMenuItem
              onClick={() => onDelete(category)}
              variant="danger"
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
      keyExtractor={(category) => category.article_category_id}
      emptyMessage="ไม่พบหมวดหมู่บทความ"
      sortKey={sortKey ?? undefined}
      sortDirection={sortDirection}
      onSortChange={onSortChange}
    />
  );
}
