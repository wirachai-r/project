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
import { ListTree, MoreHorizontal, Pencil, Trash2 } from "lucide-react";
import type { AdaptiveQuestion } from "@/types/adaptiveQuestion";

export type AdaptiveQuestionRow = AdaptiveQuestion & {
  rowNumber: number;
  followUpSymptoms: string[];
};

interface AdaptiveQuestionTableProps {
  data: AdaptiveQuestionRow[];
  emptyMessage: string;
  onEdit: (question: AdaptiveQuestion) => void;
  onManageGroup: (question: AdaptiveQuestion) => void;
  onToggleStatus: (question: AdaptiveQuestion) => void;
  onDelete: (question: AdaptiveQuestion) => void;
}

export function AdaptiveQuestionTable({
  data,
  emptyMessage,
  onEdit,
  onManageGroup,
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
      key: "symptoms",
      label: "อาการ",
      render: (item) => {
        const symptom = item.symptom ?? item.symptoms?.[0];
        return symptom ? (
          <Badge variant="default">{symptom.symptom_name}</Badge>
        ) : (
          <span className="text-xs text-[var(--color-text-secondary)]">-</span>
        );
      },
    },
    {
      key: "question",
      label: "คำถามประจำอาการ",
      render: (item) => (
        <div className="max-w-md">
          <p className="truncate font-medium" title={item.question_text}>
            {item.question_text}
          </p>
          {item.explanation_text && (
            <p
              className="mt-1 truncate text-xs text-[var(--color-text-secondary)]"
              title={item.explanation_text}
            >
              {item.explanation_text}
            </p>
          )}
        </div>
      ),
    },
    {
      key: "follow-up-symptoms",
      label: "อาการที่ถามต่อ",
      render: (item) => {
        if (item.followUpSymptoms.length === 0) {
          return (
            <span className="text-xs text-[var(--color-text-secondary)]">
              ยังไม่ได้จัดกลุ่ม
            </span>
          );
        }

        const visibleSymptoms = item.followUpSymptoms.slice(0, 2);
        const remainingCount = item.followUpSymptoms.length - visibleSymptoms.length;

        return (
          <Tooltip>
            <TooltipTrigger asChild>
              <div className="flex max-w-64 flex-wrap gap-1.5">
                {visibleSymptoms.map((symptomName, index) => (
                  <Badge key={`${symptomName}-${index}`} variant="default">
                    {symptomName}
                  </Badge>
                ))}
                {remainingCount > 0 && (
                  <Badge variant="default">+{remainingCount}</Badge>
                )}
              </div>
            </TooltipTrigger>
            <TooltipContent className="max-w-sm">
              {item.followUpSymptoms.join(", ")}
            </TooltipContent>
          </Tooltip>
        );
      },
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
            <DropdownMenuItem onClick={() => onManageGroup(item)}>
              <ListTree className="h-4 w-4 text-[var(--color-text-secondary)]" />
              จัดกลุ่มคำถาม
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
