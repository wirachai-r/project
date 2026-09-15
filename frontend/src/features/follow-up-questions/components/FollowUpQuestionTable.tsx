import { GitBranch, MoreHorizontal, Pencil, Trash2 } from "lucide-react";
import { Badge } from "@/components/ui/Badge";
import { Button } from "@/components/ui/Button";
import { DataTable, type Column } from "@/components/ui/DataTable";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "@/components/ui/DropdownMenu";
import { StatusToggle } from "@/components/ui/StatusToggle";
import type { FollowUpQuestionTemplate } from "@/types/followUpQuestion";
import { FOLLOW_UP_ANSWER_TYPES } from "../constants";

export type FollowUpQuestionRow = FollowUpQuestionTemplate & { rowNumber: number };

type Props = {
  rows: FollowUpQuestionRow[];
  onEdit: (item: FollowUpQuestionTemplate) => void;
  onManageRules: (item: FollowUpQuestionTemplate) => void;
  onToggleStatus: (item: FollowUpQuestionTemplate) => void;
  onDelete: (item: FollowUpQuestionTemplate) => void;
};

export function FollowUpQuestionTable({ rows, onEdit, onManageRules, onToggleStatus, onDelete }: Props) {
  const columns: Column<FollowUpQuestionRow>[] = [
    {
      key: "sequence",
      label: "ลำดับ",
      className: "w-20",
      render: (item) => <span className="text-sm text-[var(--color-text-secondary)]">{item.rowNumber}</span>,
    },
    {
      key: "question",
      label: "คำถาม",
      render: (item) => (
        <div className="max-w-lg">
          <p className="font-medium">{item.question_text}</p>
          {item.description && <p className="mt-1 line-clamp-1 text-xs text-[var(--color-text-secondary)]">{item.description}</p>}
        </div>
      ),
    },
    {
      key: "answer_type",
      label: "รูปแบบคำตอบ",
      render: (item) => <Badge>{FOLLOW_UP_ANSWER_TYPES.find((type) => type.value === item.answer_type)?.label}</Badge>,
    },
    { key: "scope", label: "ใช้กับอาการ", render: (item) => item.applies_to_all_symptoms ? "ทุกอาการ" : `${item.symptoms.length} อาการ` },
    { key: "required", label: "การตอบ", render: (item) => item.is_required ? "บังคับตอบ" : "ไม่บังคับ" },
    { key: "status", label: "สถานะ", render: (item) => <StatusToggle active={item.status === "1"} onChange={() => onToggleStatus(item)} /> },
    {
      key: "actions",
      label: "",
      className: "w-10 text-right",
      render: (item) => (
        <DropdownMenu>
          <DropdownMenuTrigger asChild><Button variant="ghost" size="icon" aria-label="จัดการคำถาม"><MoreHorizontal className="h-4 w-4" /></Button></DropdownMenuTrigger>
          <DropdownMenuContent align="end">
            <DropdownMenuItem onClick={() => onEdit(item)}><Pencil className="h-4 w-4 text-[var(--color-text-secondary)]" />แก้ไขข้อมูล</DropdownMenuItem>
            <DropdownMenuItem onClick={() => onManageRules(item)}><GitBranch className="h-4 w-4 text-[var(--color-text-secondary)]" />จัดการเงื่อนไข</DropdownMenuItem>
            <DropdownMenuSeparator />
            <DropdownMenuItem onClick={() => onDelete(item)} variant="danger"><Trash2 className="h-4 w-4" />ลบคำถาม</DropdownMenuItem>
          </DropdownMenuContent>
        </DropdownMenu>
      ),
    },
  ];

  return <DataTable columns={columns} data={rows} keyExtractor={(item) => item.id} emptyMessage="ไม่พบคำถามติดตามอาการ" />;
}
