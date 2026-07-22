import { MoreHorizontal, Eye, Pencil, Trash2, Stethoscope } from "lucide-react";
import type { Disease } from "@/types/disease";
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

interface DiseaseTableProps {
  data: Disease[];
  loading: boolean;
  onView: (disease: Disease) => void;
  onEdit: (disease: Disease) => void;
  onToggleStatus: (disease: Disease) => void;
  onDelete: (disease: Disease) => void;
  sortKey: string | null;
  sortDirection: "asc" | "desc" | null;
  onSortChange: (key: string, direction: "asc" | "desc" | null) => void;
}

export function DiseaseTable({
  data,
  loading,
  onView,
  onEdit,
  onToggleStatus,
  onDelete,
  sortKey,
  sortDirection,
  onSortChange,
}: DiseaseTableProps) {
  if (loading) {
    return (
      <TableSkeleton
        columns={5}
        rows={5}
        columnWidths={["w-20", "w-48", "w-32", "w-20", "w-16"]}
      />
    );
  }

  const columns: Column<Disease>[] = [
    {
      key: "id",
      label: "รหัส",
      sortable: true,
      render: (disease) => (
        <span className="font-mono text-xs text-[var(--color-text-secondary)]">
          {disease.disease_id}
        </span>
      ),
    },
    {
      key: "name",
      label: "โรค",
      sortable: true,
      render: (disease) => (
        <div className="flex items-center gap-3">
          <div className="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-[var(--color-primary-light)]">
            {disease.disease_image ? (
              <img
                src={disease.disease_image}
                alt={disease.disease_name}
                className="h-full w-full object-cover"
              />
            ) : (
              <Stethoscope className="h-4 w-4 text-[var(--color-primary)]" />
            )}
          </div>
          <div>
            <p className="font-medium text-[var(--color-text-primary)]">
              {disease.disease_name}
            </p>
            {disease.disease_name_en && (
              <p className="text-xs text-[var(--color-text-secondary)]">
                {disease.disease_name_en}
              </p>
            )}
          </div>
        </div>
      ),
    },
    {
      key: "category",
      label: "หมวดหมู่",
      render: (disease) => (
        <Badge variant="default">
          {disease.category?.category_name ?? "-"}
        </Badge>
      ),
    },
    {
      key: "status",
      label: "สถานะ",
      render: (disease) => (
        <Tooltip>
          <TooltipTrigger asChild>
            <span>
              <StatusToggle
                active={disease.status === "1"}
                onChange={() => onToggleStatus(disease)}
              />
            </span>
          </TooltipTrigger>
          <TooltipContent>
            {disease.status === "1"
              ? "คลิกเพื่อปิดใช้งาน"
              : "คลิกเพื่อเปิดใช้งาน"}
          </TooltipContent>
        </Tooltip>
      ),
    },
    {
      key: "actions",
      label: "",
      className: "w-10 text-right",
      render: (disease) => (
        <DropdownMenu>
          <DropdownMenuTrigger asChild>
            <Button variant="ghost" size="icon">
              <MoreHorizontal className="h-4 w-4" />
            </Button>
          </DropdownMenuTrigger>
          <DropdownMenuContent align="end">
            <DropdownMenuItem onClick={() => onView(disease)}>
              <Eye className="h-4 w-4 text-[var(--color-text-secondary)]" />
              ดูรายละเอียด
            </DropdownMenuItem>
            <DropdownMenuItem onClick={() => onEdit(disease)}>
              <Pencil className="h-4 w-4 text-[var(--color-text-secondary)]" />
              แก้ไขข้อมูล
            </DropdownMenuItem>
            <DropdownMenuSeparator />
            <DropdownMenuItem
              onClick={() => onDelete(disease)}
              className="text-[var(--color-danger)] focus:bg-[var(--color-danger)]/10 focus:text-[var(--color-danger)]"
            >
              <Trash2 className="h-4 w-4" />
              ลบโรค
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
      keyExtractor={(disease) => disease.disease_id}
      emptyMessage="ไม่พบข้อมูลโรค"
      sortKey={sortKey ?? undefined}
      sortDirection={sortDirection}
      onSortChange={onSortChange}
    />
  );
}
