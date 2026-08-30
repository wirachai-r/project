import { Fragment, useState } from "react";
import { GripVertical, Layers3, MoreHorizontal, Pencil, Trash2 } from "lucide-react";
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
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "@/components/ui/DropdownMenu";

interface Props {
  data: BodyAreaGroup[];
  canReorder: boolean;
  draggingId: number | null;
  onDragStart: (id: number) => void;
  onDragEnd: () => void;
  onDrop: (id: number, position: "before" | "after") => void;
  onEdit: (group: BodyAreaGroup) => void;
  onManageSubgroups: (group: BodyAreaGroup) => void;
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
  onManageSubgroups,
  onDelete,
  onStatusChange,
  statusBusyId,
}: Props) {
  const [dropTarget, setDropTarget] = useState<{
    id: number;
    position: "before" | "after";
  } | null>(null);

  if (data.length === 0) return <EmptyState title="ไม่พบกลุ่มบริเวณร่างกาย" />;

  return (
    <Table>
      <TableHeader>
        <TableRow>
          <TableHead className="w-14 text-center">ลำดับ</TableHead>
          <TableHead>กลุ่มบริเวณ</TableHead>
          <TableHead className="w-32">จำนวนอาการ</TableHead>
          <TableHead className="w-56">บริเวณย่อย</TableHead>
          <TableHead className="w-28">สถานะ</TableHead>
          <TableHead className="w-32 text-center">จัดการ</TableHead>
        </TableRow>
      </TableHeader>
      <TableBody>
        {data.map((group, index) => (
          <Fragment key={group.id}>
          <TableRow
            key={group.id}
            draggable={canReorder}
            onDragStart={() => onDragStart(group.id)}
            onDragEnd={() => {
              setDropTarget(null);
              onDragEnd();
            }}
            onDragOver={(event) => {
              if (!canReorder || draggingId === group.id) return;
              event.preventDefault();
              event.dataTransfer.dropEffect = "move";
              const rect = event.currentTarget.getBoundingClientRect();
              const position = event.clientY < rect.top + rect.height / 2
                ? "before"
                : "after";
              setDropTarget((current) =>
                current?.id === group.id && current.position === position
                  ? current
                  : { id: group.id, position },
              );
            }}
            onDrop={(event) => {
              event.preventDefault();
              if (!dropTarget || dropTarget.id !== group.id) return;
              const { position } = dropTarget;
              setDropTarget(null);
              onDrop(group.id, position);
            }}
            className={
              draggingId === group.id
                ? "opacity-40"
                : dropTarget?.id === group.id
                  ? "bg-[var(--color-primary-light)]/35 outline outline-2 -outline-offset-2 outline-[var(--color-primary)]"
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
              {group.subgroups?.length ? (
                <div>
                  <p className="text-sm font-medium text-[var(--color-text-primary)]">
                    {group.subgroups.length} บริเวณย่อย
                  </p>
                  <p className="mt-0.5 max-w-52 truncate text-xs text-[var(--color-text-secondary)]">
                    {group.subgroups.map((subgroup) => subgroup.name).join(", ")}
                  </p>
                </div>
              ) : (
                <span className="text-sm text-[var(--color-text-secondary)]">ยังไม่มี</span>
              )}
            </TableCell>
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
              <div className="flex justify-center">
                <DropdownMenu>
                  <DropdownMenuTrigger asChild>
                    <Button variant="ghost" size="icon" aria-label={`จัดการ ${group.name}`}>
                      <MoreHorizontal className="h-4 w-4" />
                    </Button>
                  </DropdownMenuTrigger>
                  <DropdownMenuContent align="end" className="min-w-52">
                   
                    <DropdownMenuItem onClick={() => onEdit(group)}>
                      <Pencil className="h-4 w-4" />
                      แก้ไขข้อมูล
                    </DropdownMenuItem>
                     <DropdownMenuItem onClick={() => onManageSubgroups(group)}>
                      <Layers3 className="h-4 w-4" />
                      จัดการบริเวณย่อย
                    </DropdownMenuItem>
                    <DropdownMenuSeparator />
                    <DropdownMenuItem
                      onClick={() => onDelete(group)}
                      variant="danger"
                    >
                      <Trash2 className="h-4 w-4" />
                      ลบกลุ่มบริเวณ
                    </DropdownMenuItem>
                  </DropdownMenuContent>
                </DropdownMenu>
              </div>
            </TableCell>
          </TableRow>
          </Fragment>
        ))}
      </TableBody>
    </Table>
  );
}
