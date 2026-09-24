import { Badge } from "@/components/ui/Badge";
import { Button } from "@/components/ui/Button";
import { DataTable, type Column } from "@/components/ui/DataTable";
import { StatusToggle } from "@/components/ui/StatusToggle";
import {
  Tooltip,
  TooltipContent,
  TooltipTrigger,
} from "@/components/ui/Tooltip";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "@/components/ui/DropdownMenu";
import { MoreHorizontal, Pencil, Trash2 } from "lucide-react";
import type { AdaptiveQuestion } from "@/types/adaptiveQuestion";
import { answerTypeOptions } from "./AdaptiveQuestionFilters";

export type AdaptiveQuestionRow = AdaptiveQuestion & { rowNumber: number };

interface AdaptiveQuestionTableProps {
  data: AdaptiveQuestionRow[];
  emptyMessage: string;
  onEdit: (question: AdaptiveQuestion) => void;
  onToggleStatus: (question: AdaptiveQuestion) => void;
  onDelete: (question: AdaptiveQuestion) => void;
}

const optionLabel = (options: Array<{ value: string; label: string }>, value: string) =>
  options.find((option) => option.value === value)?.label ?? value;

export function AdaptiveQuestionTable({
  data,
  emptyMessage,
  onEdit,
  onToggleStatus,
  onDelete,
}: AdaptiveQuestionTableProps) {
  const columns: Column<AdaptiveQuestionRow>[] = [
    {
      key: "sequence",
      label: "ลำดับ",
      className: "w-20",
      render: (item) => (
        <span className="text-sm text-[var(--color-text-secondary)]">
          {item.rowNumber}
        </span>
      ),
    },
    {
      key: "question",
      label: "คำถาม",
      render: (item) => (
        <div className="max-w-xl">
          <p className="font-medium">{item.question_text}</p>
          {item.explanation_text && (
            <p className="mt-1 line-clamp-1 text-xs text-[var(--color-text-secondary)]">
              {item.explanation_text}
            </p>
          )}
        </div>
      ),
    },
    {
      key: "answer_type",
      label: "รูปแบบคำตอบ",
      render: (item) => (
        <Badge>{optionLabel(answerTypeOptions, item.answer_type)}</Badge>
      ),
    },
    {
      key: "symptoms",
      label: "อาการที่ตรวจ",
      render: (item) => {
        const symptoms =
          item.symptoms && item.symptoms.length > 0
            ? item.symptoms
            : item.symptom
              ? [item.symptom]
              : [];
        return (
          <div className="flex max-w-56 flex-wrap gap-1">
            {symptoms.length > 0 ? (
              symptoms.slice(0, 2).map((symptom) => (
                <Badge key={symptom.symptom_id} variant="default">
                  {symptom.symptom_name}
                </Badge>
              ))
            ) : (
              <span className="text-xs text-[var(--color-text-secondary)]">
                -
              </span>
            )}
            {symptoms.length > 2 && (
              <Badge variant="default">+{symptoms.length - 2}</Badge>
            )}
          </div>
        );
      },
    },
    {
      key: "initial_symptoms",
      label: "ใช้กับอาการ",
      render: (item) => (
        <div className="flex max-w-56 flex-wrap gap-1">
          {item.rules.length > 0 ? (
            item.rules.slice(0, 2).map((rule) => (
              <Badge key={rule.initial_symptom_id} variant="default">
                {rule.initial_symptom?.symptom_name ?? rule.initial_symptom_id}
              </Badge>
            ))
          ) : (
            <span className="text-xs text-[var(--color-text-secondary)]">
              -
            </span>
          )}
          {item.rules.length > 2 && (
            <Badge variant="default">+{item.rules.length - 2}</Badge>
          )}
        </div>
      ),
    },
    {
      key: "status",
      label: "สถานะ",
      render: (item) => (
        <Tooltip>
          <TooltipTrigger asChild>
            <span>
              <StatusToggle
                active={item.status === "approved"}
                onChange={() => onToggleStatus(item)}
              />
            </span>
          </TooltipTrigger>
          <TooltipContent>
            {item.status === "approved"
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
      render: (item) => (
        <DropdownMenu>
          <DropdownMenuTrigger asChild>
            <Button variant="ghost" size="icon" aria-label="จัดการคำถาม">
              <MoreHorizontal className="h-4 w-4" />
            </Button>
          </DropdownMenuTrigger>
          <DropdownMenuContent align="end">
            <DropdownMenuItem onClick={() => onEdit(item)}>
              <Pencil className="h-4 w-4 text-[var(--color-text-secondary)]" />
              แก้ไขข้อมูล
            </DropdownMenuItem>
            <DropdownMenuSeparator />
            <DropdownMenuItem onClick={() => onDelete(item)} variant="danger">
              <Trash2 className="h-4 w-4" />
              ลบคำถาม
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
      keyExtractor={(item) => item.id}
      emptyMessage={emptyMessage}
    />
  );
}
