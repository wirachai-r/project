import { MoreHorizontal, Pencil, Trash2 } from "lucide-react";
import type { Symptom } from "@/types/symptom";
import { formatAdminDateTime } from "@/lib/formatDate";
import { DataTable, type Column } from "../../../components/ui/DataTable";
import { Badge } from "../../../components/ui/Badge";
import { Button } from "../../../components/ui/Button";
import { StatusToggle } from "../../../components/ui/StatusToggle";
import { TableSkeleton } from "../../../components/ui/TableSkeleton";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "../../../components/ui/DropdownMenu";
import {
  Tooltip,
  TooltipTrigger,
  TooltipContent,
} from "../../../components/ui/Tooltip";
import * as Icons from "lucide-react";
import { HealthIconDisplay } from "@/components/ui/HealthIconPicker";

interface SymptomTableProps {
  data: Symptom[];
  loading: boolean;
  onView: (symptom: Symptom) => void;
  onEdit: (symptom: Symptom) => void;
  onToggleStatus: (symptom: Symptom) => void;
  onDelete: (symptom: Symptom) => void;
  sortKey: string | null;
  sortDirection: "asc" | "desc" | null;
  onSortChange: (key: string, direction: "asc" | "desc" | null) => void;
}

export function SymptomTable({
  data,
  loading,
  onEdit,
  onToggleStatus,
  onDelete,
  sortKey,
  sortDirection,
  onSortChange,
}: SymptomTableProps) {
  if (loading) {
    return (
      <TableSkeleton
        columns={5}
        rows={5}
        columnWidths={["w-20", "w-48", "w-32", "w-20", "w-16"]}
      />
    );
  }

  const columns: Column<Symptom>[] = [
    {
      key: "id",
      label: "รหัส",
      sortable: true,
      render: (symptom) => (
        <span className="font-mono text-xs text-[var(--color-text-secondary)]">
          {symptom.symptom_id}
        </span>
      ),
    },
    {
      key: "name",
      label: "อาการ",
      sortable: true,
      render: (symptom) => {
        const isHealthIcon =
          symptom.symptom_image?.startsWith("health:") == true;
        const SymptomIcon = symptom.symptom_image && !isHealthIcon
          ? ((Icons as Record<string, unknown>)[symptom.symptom_image] as
              | typeof Icons.Activity
              | undefined)
          : null;

        return (
          <div className="flex items-center gap-3">
            <div className="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-[var(--color-primary-light)]">
              {isHealthIcon ? (
                <HealthIconDisplay
                  value={symptom.symptom_image!}
                  className="h-5 w-5 text-[var(--color-primary)]"
                />
              ) : SymptomIcon ? (
                <SymptomIcon className="h-4 w-4 text-[var(--color-primary)]" />
              ) : (
                <Icons.Activity className="h-4 w-4 text-[var(--color-primary)]" />
              )}
            </div>
            <div>
              <p className="font-medium text-[var(--color-text-primary)]">
                {symptom.symptom_name}
              </p>
              {/* {symptom.symptom_name_en && (
                <p className="text-xs text-[var(--color-text-secondary)]">
                  {symptom.symptom_name_en}
                </p>
              )} */}
            </div>
          </div>
        );
      },
    },
    {
      key: "category",
      label: "หมวดหมู่",
      render: (symptom) =>
        symptom.category ? (
          <Badge variant="default">{symptom.category.category_name}</Badge>
        ) : (
          "-"
        ),
    },
    {
      key: "updated_at",
      label: "แก้ไขล่าสุด",
      sortable: true,
      render: (symptom) => formatAdminDateTime(symptom.updated_at || symptom.created_at),
    },
    {
      key: "status",
      label: "สถานะ",
      render: (symptom) => (
        <Tooltip>
          <TooltipTrigger asChild>
            <span>
              <StatusToggle
                active={symptom.status === "1"}
                onChange={() => onToggleStatus(symptom)}
              />
            </span>
          </TooltipTrigger>
          <TooltipContent>
            {symptom.status === "1"
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
      render: (symptom) => (
        <DropdownMenu>
          <DropdownMenuTrigger asChild>
            <Button variant="ghost" size="icon">
              <MoreHorizontal className="h-4 w-4" />
            </Button>
          </DropdownMenuTrigger>
          <DropdownMenuContent align="end">
            <DropdownMenuItem onClick={() => onEdit(symptom)}>
              <Pencil className="h-4 w-4 text-[var(--color-text-secondary)]" />
              แก้ไขข้อมูล
            </DropdownMenuItem>
            <DropdownMenuSeparator />
            <DropdownMenuItem
              onClick={() => onDelete(symptom)}
              variant="danger"
            >
              <Trash2 className="h-4 w-4" />
              ลบอาการ
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
      keyExtractor={(symptom) => symptom.symptom_id}
      emptyMessage="ไม่พบอาการ"
      sortKey={sortKey ?? undefined}
      sortDirection={sortDirection}
      onSortChange={onSortChange}
    />
  );
}
