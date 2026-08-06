import { Fragment, useState } from "react";
import { Handle, Position, type Node, type NodeProps } from "@xyflow/react";
import { Check, Edit3, Plus, X } from "lucide-react";
import type { QuestionBox } from "@/types/questionBox";
import type { AnswerChoice } from "@/types/answerChoice";
import type { DiagnosisRule } from "@/types/diagnosisRule";
import { URGENCY_COLORS } from "@/types/diagnosisRule";
import { Tooltip, TooltipContent, TooltipTrigger } from "@/components/ui/Tooltip";
import { Textarea } from "@/components/ui/Textarea";

export interface QuestionFlowNodeData extends Record<string, unknown> {
  box: QuestionBox;
  stepNumber: string;
  isEntry: boolean;
  onEdit: (box: QuestionBox) => void;
  onCreateChoice: (boxId: string, choiceText: string) => void;
  onConfigureRule: (choice: AnswerChoice) => void;
  onCreateNext: (boxId: string, handleId: string, questionText: string) => void;
  choiceDirections: Record<string, "right" | "down">;
  choicesWithResults: Record<string, boolean>;
  checklistResults?: { yes: boolean; no: boolean };
  onConfigureChecklistRule?: (outcome: "yes" | "no") => void;
}

export type QuestionFlowNode = Node<QuestionFlowNodeData, "question">;

export interface ResultFlowNodeData extends Record<string, unknown> {
  rules: DiagnosisRule[];
  choice: AnswerChoice;
  onConfigure: (choice: AnswerChoice) => void;
}

export type ResultFlowNode = Node<ResultFlowNodeData, "result">;

const handleClass = "!h-4 !w-4 !border-[3px] !border-white !bg-[var(--color-primary)] !shadow-md";

function InlineNextQuestion({ onSubmit, onCancel, placeholder = "พิมพ์คำถามถัดไป...", submitLabel = "สร้างและเชื่อม" }: { onSubmit: (text: string) => void; onCancel: () => void; placeholder?: string; submitLabel?: string }) {
  const [text, setText] = useState("");

  return (
    <div className="nodrag nowheel mt-2 rounded-lg border border-blue-200 bg-blue-50 p-2">
      <Textarea
        value={text}
        onChange={(event) => setText(event.target.value)}
        placeholder={placeholder}
        rows={2}
        autoFocus
        className="min-h-14 w-full border-blue-200 bg-white px-2 py-1.5 text-xs focus:border-[var(--color-primary)]"
        onKeyDown={(event) => {
          if (event.key === "Enter" && !event.shiftKey && text.trim()) {
            event.preventDefault();
            onSubmit(text.trim());
          }
        }}
      />
      <div className="mt-1.5 flex justify-end gap-1">
        <button type="button" onClick={onCancel} className="rounded p-1 text-slate-500 hover:bg-white" title="ยกเลิก">
          <X className="h-3.5 w-3.5" />
        </button>
        <button
          type="button"
          disabled={!text.trim()}
          onClick={() => text.trim() && onSubmit(text.trim())}
          className="flex items-center gap-1 rounded bg-[var(--color-primary)] px-2 py-1 text-[10px] font-medium text-white disabled:opacity-40"
        >
          <Check className="h-3 w-3" /> {submitLabel}
        </button>
      </div>
    </div>
  );
}

export function FlowNode({ data, selected }: NodeProps<QuestionFlowNode>) {
  const { box } = data;
  const frameNumber = data.stepNumber;
  const [addingFrom, setAddingFrom] = useState<string | null>(null);
  const choices = [...(box.choices ?? [])]
    .filter((choice) => choice.status === "1")
    .sort((a, b) => a.order - b.order);

  return (
    <div
      className={`relative w-[290px] overflow-visible rounded-xl border bg-white shadow-sm transition-shadow ${
        selected
          ? "border-[var(--color-primary)] shadow-lg"
          : data.isEntry
            ? "border-emerald-400"
            : "border-[var(--color-border)]"
      }`}
    >
      <div className="absolute -top-7 left-1 text-sm font-bold text-[var(--color-text-primary)]">
        กรอบ {frameNumber}
      </div>
      <Handle type="target" position={Position.Top} id="target-top" className={handleClass} />
      <Handle type="target" position={Position.Left} id="target-left" className={handleClass} />

      <div className="flex items-start gap-2 rounded-t-[11px] border-b border-[var(--color-border)] bg-slate-50 px-3 py-2.5">
        <div className="min-w-0 flex-1">
          <p className="line-clamp-3 text-sm font-semibold text-[var(--color-text-primary)]">
            {box.question_text}
          </p>
          {box.detail && (
            <p className="mt-1 line-clamp-2 whitespace-pre-line text-xs font-normal text-[var(--color-text-secondary)]">
              {box.detail}
            </p>
          )}
          <p className="mt-1 text-[10px] text-[var(--color-text-secondary)]">
            {box.question_type === "M"
              ? `เลือกตั้งแต่ ${box.min_required ?? 1} รายการขึ้นไป → “ใช่” · เลือกน้อยกว่า ${box.min_required ?? 1} รายการ → “ไม่ใช่”`
              : "เลือกได้ข้อเดียว"}
            {data.isEntry ? " · จุดเริ่มต้น" : ""}
          </p>
        </div>
        <Tooltip>
          <TooltipTrigger asChild>
            <button
              type="button"
              className="nodrag rounded-md p-1.5 text-[var(--color-text-secondary)] hover:bg-white hover:text-[var(--color-primary)]"
              onClick={() => data.onEdit(box)}
            >
              <Edit3 className="h-4 w-4" />
            </button>
          </TooltipTrigger>
          <TooltipContent>แก้ไขกล่องคำถาม</TooltipContent>
        </Tooltip>
      </div>

      {box.question_type === "M" ? (
        <div>
          <div className="divide-y divide-[var(--color-border)]">
            {choices.map((choice) => (
              <div key={choice.choice_id} className="flex min-h-9 items-center gap-2 px-3 py-2 text-xs">
                <span className="h-3.5 w-3.5 shrink-0 rounded border border-slate-300" />
                <span className="min-w-0 flex-1 truncate" title={choice.choice_text}>
                  {choice.choice_text}
                </span>
              </div>
            ))}
            <button type="button" className="nodrag flex w-full items-center justify-center gap-1 px-3 py-2 text-[11px] text-[var(--color-text-secondary)] hover:bg-slate-50 hover:text-[var(--color-primary)]" onClick={() => setAddingFrom("new-choice")}>
              <Plus className="h-3.5 w-3.5" /> เพิ่มรายการอาการ
            </button>
            {addingFrom === "new-choice" && (
              <div className="px-3 pb-2">
                <InlineNextQuestion placeholder="พิมพ์รายการอาการใหม่..." submitLabel="เพิ่มรายการ" onCancel={() => setAddingFrom(null)} onSubmit={(text) => { data.onCreateChoice(box.box_id, text); setAddingFrom(null); }} />
              </div>
            )}
          </div>
          <div className="grid grid-cols-2 divide-x divide-[var(--color-border)] border-t border-[var(--color-border)]">
            <div className="relative px-3 py-3 text-center text-xs font-medium text-rose-700">
              <div>ไม่ใช่</div>
              {!box.no_next_box_id && (
                <div className="nodrag mt-1 flex justify-center gap-1">
                  {!data.checklistResults?.no && (
                    <button type="button" onClick={() => setAddingFrom("no")} className="rounded-md border border-dashed border-rose-400 px-1.5 py-1 text-[10px] hover:bg-rose-50">+ ต่อคำถาม</button>
                  )}
                  {!data.checklistResults?.no && (
                    <button type="button" onClick={() => data.onConfigureChecklistRule?.("no")} className="rounded-md border border-amber-300 bg-amber-50 px-1.5 py-1 text-[10px] text-amber-800 hover:bg-amber-100">ตั้งผลลัพธ์</button>
                  )}
                </div>
              )}
              <Handle type="source" position={Position.Bottom} id="no" className={`${handleClass} !bg-rose-500`} />
            </div>
            <div className="relative px-3 py-3 text-center text-xs font-medium text-emerald-700">
              <div>ใช่</div>
              {!box.yes_next_box_id && (
                <div className="nodrag mt-1 flex justify-center gap-1">
                  {!data.checklistResults?.yes && (
                    <button type="button" onClick={() => setAddingFrom("yes")} className="rounded-md border border-dashed border-emerald-400 px-1.5 py-1 text-[10px] hover:bg-emerald-50">+ ต่อคำถาม</button>
                  )}
                  {!data.checklistResults?.yes && (
                    <button type="button" onClick={() => data.onConfigureChecklistRule?.("yes")} className="rounded-md border border-amber-300 bg-amber-50 px-1.5 py-1 text-[10px] text-amber-800 hover:bg-amber-100">ตั้งผลลัพธ์</button>
                  )}
                </div>
              )}
              <Handle type="source" position={Position.Bottom} id="yes" className={`${handleClass} !bg-emerald-500`} />
            </div>
          </div>
          {(addingFrom === "yes" || addingFrom === "no") && (
            <div className="px-3 pb-3">
              <InlineNextQuestion
                onCancel={() => setAddingFrom(null)}
                onSubmit={(text) => {
                  data.onCreateNext(box.box_id, addingFrom, text);
                  setAddingFrom(null);
                }}
              />
            </div>
          )}
        </div>
      ) : (
        <div className="divide-y divide-[var(--color-border)]">
          {choices.map((choice) => (
            <Fragment key={choice.choice_id}>
            <div className="relative flex min-h-10 items-center gap-2 px-3 py-2 pr-6 text-xs">
              <span className={`rounded px-1.5 py-0.5 text-[10px] font-semibold ${data.choiceDirections[choice.choice_id] === "down" ? "bg-rose-50 text-rose-700" : "bg-emerald-50 text-emerald-700"}`}>
                {data.choiceDirections[choice.choice_id] === "down" ? "ลง" : "ข้าง"}
              </span>
              <span className="min-w-0 flex-1 truncate" title={choice.choice_text}>
                {choice.choice_text}
              </span>
              {!choice.next_box_id && !choice.next_diagram_id && !data.choicesWithResults[choice.choice_id] && (
                <div className="nodrag flex shrink-0 gap-1">
                  <button type="button" className="rounded-md border border-dashed border-blue-300 bg-blue-50 px-1.5 py-1 text-[10px] font-semibold text-blue-700 hover:bg-blue-100" onClick={() => setAddingFrom(`choice:${choice.choice_id}`)}>
                    + ต่อคำถาม
                  </button>
                  <button type="button" className="rounded-md border border-amber-300 bg-amber-50 px-1.5 py-1 text-[10px] font-semibold text-amber-800 hover:bg-amber-100" onClick={() => data.onConfigureRule(choice)}>
                    ตั้งผลลัพธ์
                  </button>
                </div>
              )}
              <Handle
                type="source"
                position={data.choiceDirections[choice.choice_id] === "down" ? Position.Bottom : Position.Right}
                id={`choice:${choice.choice_id}`}
                className={`${handleClass} ${data.choiceDirections[choice.choice_id] === "down" ? "!bg-rose-500" : "!bg-emerald-500"}`}
              />
            </div>
            {addingFrom === `choice:${choice.choice_id}` && (
              <div className="px-3 pb-2">
                <InlineNextQuestion
                  onCancel={() => setAddingFrom(null)}
                  onSubmit={(text) => {
                    data.onCreateNext(box.box_id, `choice:${choice.choice_id}`, text);
                    setAddingFrom(null);
                  }}
                />
              </div>
            )}
            </Fragment>
          ))}
          {!((choices.length === 2) && choices.some((choice) => /^(ใช่|yes)$/i.test(choice.choice_text.trim())) && choices.some((choice) => /^(ไม่|ไม่ใช่|no)$/i.test(choice.choice_text.trim()))) && <button
            type="button"
            className="nodrag flex w-full items-center justify-center gap-1 px-3 py-2 text-[11px] text-[var(--color-text-secondary)] hover:bg-slate-50 hover:text-[var(--color-primary)]"
            onClick={() => setAddingFrom("new-choice")}
          >
            <Plus className="h-3.5 w-3.5" /> เพิ่มตัวเลือก
          </button>}
          {addingFrom === "new-choice" && (
            <div className="px-3 pb-2">
              <InlineNextQuestion placeholder="พิมพ์ตัวเลือกใหม่ เช่น ใช่ หรือ ไม่..." submitLabel="เพิ่มตัวเลือก" onCancel={() => setAddingFrom(null)} onSubmit={(text) => { data.onCreateChoice(box.box_id, text); setAddingFrom(null); }} />
            </div>
          )}
        </div>
      )}
    </div>
  );
}

export function ResultFlowNode({ data, selected }: NodeProps<ResultFlowNode>) {
  return (
    <div className={`isolate w-[330px] rounded-xl border-2 bg-white p-3 shadow-md ${selected ? "border-[var(--color-primary)] shadow-lg" : "border-slate-300"}`}>
      <Handle type="target" position={Position.Top} id="target-top" className={handleClass} />
      <Handle type="target" position={Position.Left} id="target-left" className={handleClass} />
      <div className="mb-2 text-[10px] font-semibold uppercase tracking-wide text-slate-500">ผลลัพธ์ / คำแนะนำ</div>
      <div className="space-y-2">
        {data.rules.map((rule) => (
          <div key={rule.rule_id} className={`rounded-lg border p-2.5 text-xs ${URGENCY_COLORS[rule.urgency_level]}`}>
            <div className="font-bold leading-5">
              {(rule.diseases?.length ?? 0) > 0
                ? rule.diseases?.map((disease) => `${disease.disease_name}${disease.reference ? ` (${disease.reference})` : ""}`).join(" / ")
                : "คำแนะนำ"}
              {rule.medical_reference ? ` (${rule.medical_reference})` : ""}
            </div>
            {rule.time_frame && <div className="mt-1 font-semibold">⊕ {rule.time_frame}</div>}
            {rule.note && <div className="mt-1.5 whitespace-pre-line leading-5">{rule.note}</div>}
            {(rule.next_diagrams?.length ?? 0) > 0 && (
              <div className="mt-2 border-t border-current/20 pt-2">
                <div className="font-semibold">แผนภูมิที่แนะนำ:</div>
                <ul className="mt-1 space-y-0.5 font-normal leading-5">
                  {rule.next_diagrams!.map((diagram) => (
                    <li key={diagram.diagram_id}>→ {diagram.diagram_name}</li>
                  ))}
                </ul>
              </div>
            )}
          </div>
        ))}
      </div>
      <button
        type="button"
        onClick={() => data.onConfigure(data.choice)}
        className="nodrag mt-2 w-full rounded-lg border border-dashed border-[var(--color-primary)] px-2 py-1.5 text-[11px] font-medium text-[var(--color-primary)] hover:bg-[var(--color-primary)]/5"
      >
        แก้ไขผลลัพธ์
      </button>
    </div>
  );
}
