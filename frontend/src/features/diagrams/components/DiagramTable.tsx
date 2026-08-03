import { MoreHorizontal, Pencil, Trash2, GitBranch } from "lucide-react";
import type { Diagram } from "@/types/diagram";
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

interface DiagramTableProps {
  data: Diagram[];
  loading: boolean;
  onEdit: (diagram: Diagram) => void;
  onViewFlow: (diagram: Diagram) => void;
  onToggleStatus: (diagram: Diagram) => void;
  onDelete: (diagram: Diagram) => void;
  sortKey: string | null;
  sortDirection: "asc" | "desc" | null;
  onSortChange: (key: string, direction: "asc" | "desc" | null) => void;
}

export function DiagramTable({
  data,
  loading,
  onEdit,
  onViewFlow,
  onToggleStatus,
  onDelete,
  sortKey,
  sortDirection,
  onSortChange,
}: DiagramTableProps) {
  if (loading) {
    return (
      <TableSkeleton
        columns={5}
        rows={5}
        columnWidths={["w-20", "w-56", "w-40", "w-20", "w-16"]}
      />
    );
  }

  const columns: Column<Diagram>[] = [
    {
      key: "id",
      label: "รหัส",
      sortable: true,
      render: (diagram) => (
        <span className="font-mono text-xs text-[var(--color-text-secondary)]">
          {diagram.diagram_id}
        </span>
      ),
    },
    {
      key: "name",
      label: "แผนภูมิ",
      sortable: true,
      render: (diagram) => (
        <div className="flex items-center gap-3">
          <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-[var(--color-primary-light)]">
            <GitBranch className="h-4 w-4 text-[var(--color-primary)]" />
          </div>
          <div>
            <p className="font-medium text-[var(--color-text-primary)]">
              {diagram.diagram_name}
            </p>
            {diagram.diagram_name_en && (
              <p className="text-xs text-[var(--color-text-secondary)]">
                {diagram.diagram_name_en}
              </p>
            )}
          </div>
        </div>
      ),
    },
    {
      key: "symptoms",
      label: "อาการที่เกี่ยวข้อง",
      render: (diagram) => (
        <div className="flex flex-wrap gap-1">
          {diagram.symptoms && diagram.symptoms.length > 0 ? (
            diagram.symptoms.slice(0, 2).map((s) => (
              <Badge key={s.symptom_id} variant="default">
                {s.symptom_name}
              </Badge>
            ))
          ) : (
            <span className="text-xs text-[var(--color-text-secondary)]">
              -
            </span>
          )}
          {diagram.symptoms && diagram.symptoms.length > 2 && (
            <Badge variant="default">+{diagram.symptoms.length - 2}</Badge>
          )}
        </div>
      ),
    },
    {
      key: "entry_box",
      label: "กรอบเริ่มต้น",
      render: (diagram) =>
        diagram.entry_box_id ? (
          <Badge variant="default">พร้อมใช้งาน</Badge>
        ) : (
          <span className="text-xs text-[var(--color-danger)]">
            ยังไม่กำหนด
          </span>
        ),
    },
    {
      key: "status",
      label: "สถานะ",
      render: (diagram) => (
        <Tooltip>
          <TooltipTrigger asChild>
            <span>
              <StatusToggle
                active={diagram.status === "1"}
                onChange={() => onToggleStatus(diagram)}
              />
            </span>
          </TooltipTrigger>
          <TooltipContent>
            {diagram.status === "1"
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
      render: (diagram) => (
        <DropdownMenu>
          <DropdownMenuTrigger asChild>
            <Button variant="ghost" size="icon">
              <MoreHorizontal className="h-4 w-4" />
            </Button>
          </DropdownMenuTrigger>
          <DropdownMenuContent align="end">
            <DropdownMenuItem onClick={() => onEdit(diagram)}>
              <Pencil className="h-4 w-4 text-[var(--color-text-secondary)]" />
              แก้ไขข้อมูลแผนภูมิ
            </DropdownMenuItem>
            <DropdownMenuItem onClick={() => onViewFlow(diagram)}>
              <GitBranch className="h-4 w-4 text-[var(--color-text-secondary)]" />
              จัดการผังงาน
            </DropdownMenuItem>
            <DropdownMenuSeparator />
            <DropdownMenuItem
              onClick={() => onDelete(diagram)}
              className="text-[var(--color-danger)] focus:bg-[var(--color-danger)]/10 focus:text-[var(--color-danger)]"
            >
              <Trash2 className="h-4 w-4" />
              ลบแผนภูมิ
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
      keyExtractor={(diagram) => diagram.diagram_id}
      emptyMessage="ไม่พบข้อมูลแผนภูมิ"
      sortKey={sortKey ?? undefined}
      sortDirection={sortDirection}
      onSortChange={onSortChange}
    />
  );
}
