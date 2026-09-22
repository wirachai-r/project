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
import { MoreHorizontal, Pencil, Trash2 } from "lucide-react";
import type { AdaptiveQuestion } from "@/types/adaptiveQuestion";
import { answerTypeOptions, statusOptions } from "./AdaptiveQuestionFilters";

export type AdaptiveQuestionRow = AdaptiveQuestion & { rowNumber: number };

interface AdaptiveQuestionTableProps {
  data: AdaptiveQuestionRow[];
  emptyMessage: string;
  onEdit: (question: AdaptiveQuestion) => void;
  onDelete: (question: AdaptiveQuestion) => void;
}

const optionLabel = (
  options: Array<{ value: string; label: string }>,
  value: string,
) => options.find((option) => option.value === value)?.label ?? value;

const sourceUrls = (source: string) =>
  source.match(/https?:\/\/[^\s;]+/g)?.map((url) => url.replace(/[),.]+$/, "")) ?? [];

export function AdaptiveQuestionTable({
  data,
  emptyMessage,
  onEdit,
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
      render: (item) =>
        `${item.symptoms?.length ?? (item.symptom ? 1 : 0)} อาการ`,
    },
    {
      key: "initial_symptoms",
      label: "ใช้กับอาการ",
      render: (item) => (
        <div className="max-w-48 text-sm">
          {item.rules.slice(0, 2).map((rule) => (
            <div key={rule.initial_symptom_id} className="truncate">
              {rule.initial_symptom?.symptom_name ?? rule.initial_symptom_id}
            </div>
          ))}
          {item.rules.length > 2 && (
            <span className="text-xs text-[var(--color-text-secondary)]">
              และอีก {item.rules.length - 2} อาการ
            </span>
          )}
        </div>
      ),
    },
    {
      key: "evidence_source",
      label: "แหล่งอ้างอิง",
      render: (item) => {
        const urls = sourceUrls(item.evidence_source ?? "");
        const sourcedRules = item.rules.filter((rule) => rule.evidence_source);
        if (!item.evidence_source) {
          return sourcedRules.length > 0 ? (
            <div className="text-xs">
              <Badge>{sourcedRules.length} เส้นทางมีที่มา</Badge>
              <p className="mt-1 line-clamp-2 text-[var(--color-text-secondary)]">
                {sourcedRules[0].evidence_source}
              </p>
            </div>
          ) : (
            <Badge>ยังไม่มี</Badge>
          );
        }

        return (
          <div className="max-w-48 text-xs">
            <p className="line-clamp-2 text-[var(--color-text-secondary)]">
              {item.evidence_source}
            </p>
            {urls.length > 0 && (
              <div className="mt-1 flex gap-2">
                {urls.slice(0, 2).map((url, index) => (
                  <a
                    key={url}
                    href={url}
                    target="_blank"
                    rel="noreferrer"
                    className="text-[var(--color-primary)] hover:underline"
                    onClick={(event) => event.stopPropagation()}
                  >
                    แหล่งที่ {index + 1}
                  </a>
                ))}
              </div>
            )}
          </div>
        );
      },
    },
    {
      key: "status",
      label: "สถานะ",
      render: (item) => (
        <Badge>{optionLabel(statusOptions, item.status)}</Badge>
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
