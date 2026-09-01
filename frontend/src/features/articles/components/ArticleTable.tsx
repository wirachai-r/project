import { MoreHorizontal, Eye, Pencil, Trash2, Newspaper } from "lucide-react";
import type { Article } from "@/types/article";
import { formatAdminDateTime } from "@/lib/formatDate";
import { DataTable, type Column } from "../../../components/ui/DataTable";
import { Badge } from "../../../components/ui/Badge";
import { Button } from "../../../components/ui/Button";
import { TableSkeleton } from "../../../components/ui/TableSkeleton";
import { SimpleSelect } from "../../../components/ui/SimpleSelect";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "../../../components/ui/DropdownMenu";

interface ArticleTableProps {
  data: Article[];
  loading: boolean;
  onView: (article: Article) => void;
  onEdit: (article: Article) => void;
  onDelete: (article: Article) => void;
  onStatusChange: (article: Article, status: "1" | "2" | "3") => void;
  sortKey: string | null;
  sortDirection: "asc" | "desc" | null;
  onSortChange: (key: string, direction: "asc" | "desc" | null) => void;
}

const STATUS_OPTIONS = [
  { value: "1", label: "เผยแพร่" },
  { value: "2", label: "ฉบับร่าง" },
  { value: "3", label: "เก็บถาวร" },
];

export function ArticleTable({
  data,
  loading,
  onView,
  onEdit,
  onDelete,
  onStatusChange,
  sortKey,
  sortDirection,
  onSortChange,
}: ArticleTableProps) {
  if (loading) {
    return (
      <TableSkeleton
        columns={5}
        rows={5}
        columnWidths={["w-20", "w-60", "w-32", "w-20", "w-16"]}
      />
    );
  }

  const columns: Column<Article>[] = [
    {
      key: "id",
      label: "รหัส",
      sortable: true,
      className: "w-20",
      render: (article) => (
        <span className="font-mono text-xs text-[var(--color-text-secondary)]">
          {article.article_id}
        </span>
      ),
    },
    {
      key: "title",
      label: "บทความ",
      sortable: true,
      className: "w-60",
      render: (article) => (
        <div className="flex items-center gap-3">
          <div className="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-[var(--color-primary-light)]">
            {article.thumbnail ? (
              <img
                src={article.thumbnail}
                alt={article.title}
                loading="lazy"
                decoding="async"
                className="h-full w-full object-cover"
              />
            ) : (
              <Newspaper className="h-4 w-4 text-[var(--color-primary)]" />
            )}
          </div>
          <div className="min-w-0">
            <p className="truncate font-medium text-[var(--color-text-primary)]">
              {article.title}
            </p>
            {/* {article.title_en && (
              <p className="truncate text-xs text-[var(--color-text-secondary)]">
                {article.title_en}
              </p>
            )} */}
          </div>
        </div>
      ),
    },
    {
      key: "category",
      label: "หมวดหมู่",
      className: "w-32",
      render: (article) => (
        <Badge variant="default">
          {article.category?.category_name ?? "-"}
        </Badge>
      ),
    },
    {
      key: "updated_at",
      label: "แก้ไขล่าสุด",
      sortable: true,
      className: "w-36",
      render: (article) => formatAdminDateTime(article.updated_at || article.created_at),
    },
    {
      key: "status",
      label: "สถานะ",
      className: "w-20",
      render: (article) => (
        <div onClick={(e) => e.stopPropagation()}>
          <SimpleSelect
            value={article.status}
            onChange={(v) => onStatusChange(article, v as "1" | "2" | "3")}
            options={STATUS_OPTIONS}
          />
        </div>
      ),
    },
    {
      key: "actions",
      label: "",
      className: "w-16 text-right",
      render: (article) => (
        <DropdownMenu>
          <DropdownMenuTrigger asChild>
            <Button variant="ghost" size="icon">
              <MoreHorizontal className="h-4 w-4" />
            </Button>
          </DropdownMenuTrigger>
          <DropdownMenuContent align="end">
            <DropdownMenuItem onClick={() => onView(article)}>
              <Eye className="h-4 w-4 text-[var(--color-text-secondary)]" />
              ดูตัวอย่าง
            </DropdownMenuItem>
            <DropdownMenuItem onClick={() => onEdit(article)}>
              <Pencil className="h-4 w-4 text-[var(--color-text-secondary)]" />
              แก้ไขข้อมูล
            </DropdownMenuItem>
            <DropdownMenuSeparator />
            <DropdownMenuItem
              onClick={() => onDelete(article)}
              variant="danger"
            >
              <Trash2 className="h-4 w-4" />
              ลบบทความ
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
      keyExtractor={(article) => article.article_id}
      emptyMessage="ไม่พบข้อมูลบทความ"
      sortKey={sortKey ?? undefined}
      sortDirection={sortDirection}
      onSortChange={onSortChange}
    />
  );
}
