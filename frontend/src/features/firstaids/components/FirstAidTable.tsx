import { MoreHorizontal, Eye, Pencil, Trash2, HeartPulse } from "lucide-react";
import type { FirstAid } from "@/types/firstaid";
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

interface FirstAidTableProps {
  data: FirstAid[];
  loading: boolean;
  onView: (firstAid: FirstAid) => void;
  onEdit: (firstAid: FirstAid) => void;
  onDelete: (firstAid: FirstAid) => void;
  onStatusChange: (firstAid: FirstAid, status: "1" | "2" | "3") => void;
  sortKey: string | null;
  sortDirection: "asc" | "desc" | null;
  onSortChange: (key: string, direction: "asc" | "desc" | null) => void;
}

const STATUS_OPTIONS = [
  { value: "1", label: "เผยแพร่" },
  { value: "2", label: "ฉบับร่าง" },
  { value: "3", label: "เก็บถาวร" },
];

export function FirstAidTable({
  data,
  loading,
  onView,
  onEdit,
  onDelete,
  onStatusChange,
  sortKey,
  sortDirection,
  onSortChange,
}: FirstAidTableProps) {
  if (loading) {
    return (
      <TableSkeleton
        columns={5}
        rows={5}
        columnWidths={["w-20", "w-60", "w-32", "w-20", "w-16"]}
      />
    );
  }

  const columns: Column<FirstAid>[] = [
    {
      key: "id",
      label: "รหัส",
      sortable: true,
      className: "w-20",
      render: (firstAid) => (
        <span className="font-mono text-xs text-[var(--color-text-secondary)]">
          {firstAid.first_aid_id}
        </span>
      ),
    },
    {
      key: "title",
      label: "หัวข้อปฐมพยาบาล",
      sortable: true,
      className: "w-60",
      render: (firstAid) => (
        <div className="flex items-center gap-3">
          <div className="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-[var(--color-primary-light)]">
            {firstAid.thumbnail ? (
              <img
                src={firstAid.thumbnail}
                alt={firstAid.title}
                className="h-full w-full object-cover"
              />
            ) : (
              <HeartPulse className="h-4 w-4 text-[var(--color-primary)]" />
            )}
          </div>
          <div className="min-w-0">
            <p className="truncate font-medium text-[var(--color-text-primary)]">
              {firstAid.title}
            </p>
            {firstAid.title_en && (
              <p className="truncate text-xs text-[var(--color-text-secondary)]">
                {firstAid.title_en}
              </p>
            )}
          </div>
        </div>
      ),
    },
    {
      key: "category",
      label: "หมวดหมู่",
      className: "w-32",
      render: (firstAid) => (
        <Badge variant="default">
          {firstAid.category?.category_name ?? "-"}
        </Badge>
      ),
    },
    {
      key: "status",
      label: "สถานะ",
      className: "w-20",
      render: (firstAid) => (
        <div onClick={(e) => e.stopPropagation()}>
          <SimpleSelect
            value={firstAid.status}
            onChange={(v) => onStatusChange(firstAid, v as "1" | "2" | "3")}
            options={STATUS_OPTIONS}
          />
        </div>
      ),
    },
    {
      key: "actions",
      label: "",
      className: "w-16 text-right",
      render: (firstAid) => (
        <DropdownMenu>
          <DropdownMenuTrigger asChild>
            <Button variant="ghost" size="icon">
              <MoreHorizontal className="h-4 w-4" />
            </Button>
          </DropdownMenuTrigger>
          <DropdownMenuContent align="end">
            <DropdownMenuItem onClick={() => onView(firstAid)}>
              <Eye className="h-4 w-4 text-[var(--color-text-secondary)]" />
              ดูรายละเอียด
            </DropdownMenuItem>
            <DropdownMenuItem onClick={() => onEdit(firstAid)}>
              <Pencil className="h-4 w-4 text-[var(--color-text-secondary)]" />
              แก้ไขข้อมูล
            </DropdownMenuItem>
            <DropdownMenuSeparator />
            <DropdownMenuItem
              onClick={() => onDelete(firstAid)}
              className="text-[var(--color-danger)] focus:bg-[var(--color-danger)]/10 focus:text-[var(--color-danger)]"
            >
              <Trash2 className="h-4 w-4" />
              ลบข้อมูลปฐมพยาบาล
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
      keyExtractor={(firstAid) => firstAid.first_aid_id}
      emptyMessage="ไม่พบข้อมูลปฐมพยาบาล"
      sortKey={sortKey ?? undefined}
      sortDirection={sortDirection}
      onSortChange={onSortChange}
    />
  );
}