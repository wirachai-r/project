import { GripVertical, Pencil, Trash2 } from "lucide-react";
import { Button } from "@/components/ui/Button";
import { EmptyState } from "@/components/ui/EmptyState";
import { StatusToggle } from "@/components/ui/StatusToggle";
import {
  Tooltip,
  TooltipContent,
  TooltipTrigger,
} from "@/components/ui/Tooltip";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/Table";
import type { BodyAreaGroup } from "@/types/bodyAreaGroup";

interface Props {
  data: BodyAreaGroup[];
  canReorder: boolean;
  draggingId: number | null;
  onDragStart: (id: number) => void;
  onDragEnd: () => void;
  onDrop: (id: number) => void;
  onEdit: (group: BodyAreaGroup) => void;
  onDelete: (group: BodyAreaGroup) => void;
  onStatusChange: (group: BodyAreaGroup) => void;
  statusBusyId: number | null;
}

export function BodyAreaGroupTable({
  data,
  canReorder,
  draggingId,
  onDragStart,
  onDragEnd,
  onDrop,
  onEdit,
  onDelete,
  onStatusChange,
  statusBusyId,
}: Props) {
  if (data.length === 0) return <EmptyState title="ไม่พบกลุ่มบริเวณร่างกาย" />;

  return (
    <Table>
      <TableHeader>
        <TableRow>
          <TableHead className="w-14 text-center">ลำดับ</TableHead>
          <TableHead>กลุ่มบริเวณ</TableHead>
          <TableHead className="w-32">จำนวนอาการ</TableHead>
          <TableHead className="w-28">สถานะ</TableHead>
          <TableHead className="w-32 text-center">จัดการ</TableHead>
        </TableRow>
      </TableHeader>
      <TableBody>
        {data.map((group, index) => (
          <TableRow
            key={group.id}
            draggable={canReorder}
            onDragStart={() => onDragStart(group.id)}
            onDragEnd={onDragEnd}
            onDragOver={(event) => canReorder && event.preventDefault()}
            onDrop={() => onDrop(group.id)}
            className={
              draggingId === group.id
                ? "opacity-40"
                : canReorder
                  ? "cursor-grab active:cursor-grabbing"
                  : ""
            }
          >
            <TableCell className="text-center">
              <span className="inline-flex items-center gap-1 text-[var(--color-text-secondary)]">
                {canReorder && (
                  <GripVertical className="h-4 w-4" aria-hidden="true" />
                )}
                {index + 1}
              </span>
            </TableCell>
            <TableCell>
              <div className="flex min-w-0 items-center gap-3">
                {group.image_url ? (
                  <img
                    src={group.image_url}
                    alt=""
                    className="h-12 w-16 shrink-0 rounded-lg object-cover"
                  />
                ) : (
                  <div className="h-12 w-16 shrink-0 rounded-lg bg-[var(--color-surface)]" />
                )}
                <div className="min-w-0">
                  <p className="truncate font-medium text-[var(--color-text-primary)]">
                    {group.name}
                  </p>
                  <p className="max-w-xl truncate text-xs text-[var(--color-text-secondary)]">
                    {group.description || "ไม่มีคำอธิบาย"}
                  </p>
                </div>
              </div>
            </TableCell>
            <TableCell>{group.symptoms_count} อาการ</TableCell>
            <TableCell>
              <Tooltip>
                <TooltipTrigger asChild>
                  <span>
                    <StatusToggle
                      active={group.status === "1"}
                      onChange={() => onStatusChange(group)}
                      disabled={statusBusyId === group.id}
                    />
                  </span>
                </TooltipTrigger>
                <TooltipContent>
                  {group.status === "1"
                    ? "คลิกเพื่อปิดใช้งาน"
                    : "คลิกเพื่อเปิดใช้งาน"}
                </TooltipContent>
              </Tooltip>
            </TableCell>
            <TableCell>
              <div className="flex justify-center gap-1">
                <Button
                  variant="ghost"
                  size="icon"
                  onClick={() => onEdit(group)}
                  aria-label={`แก้ไข ${group.name}`}
                >
                  <Pencil className="h-4 w-4" />
                </Button>
                <Button
                  variant="ghost"
                  size="icon"
                  className="text-red-600 hover:bg-red-50"
                  onClick={() => onDelete(group)}
                  aria-label={`ลบ ${group.name}`}
                >
                  <Trash2 className="h-4 w-4" />
                </Button>
              </div>
            </TableCell>
          </TableRow>
        ))}
      </TableBody>
    </Table>
  );
}
