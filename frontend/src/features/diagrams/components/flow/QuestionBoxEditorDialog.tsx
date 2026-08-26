import { useEffect, useState, type DragEvent } from "react";
import axios from "axios";
import { toast } from "sonner";
import { GripVertical, Plus, Trash2 } from "lucide-react";
import { questionBoxApi } from "@/lib/api/questionBox";
import { answerChoiceApi } from "@/lib/api/answerChoice";
import type { QuestionBox, QuestionType } from "@/types/questionBox";
import { Button } from "@/components/ui/Button";
import { SimpleSelect } from "@/components/ui/SimpleSelect";
import { Textarea } from "@/components/ui/Textarea";
import { Tooltip, TooltipContent, TooltipTrigger } from "@/components/ui/Tooltip";
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
  DialogFooter,
} from "@/components/ui/Dialog";
import {
  AlertDialog,
  AlertDialogContent,
  AlertDialogHeader,
  AlertDialogTitle,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogAction,
  AlertDialogCancel,
} from "@/components/ui/AlertDialog";
import { truncateText } from "./flowTree";

const NEW_BOX_VALUE = "__new__";
const NONE_VALUE = "__none__";
type AnswerMode = "binary" | "multiple" | "checklist";

function binaryChoices(): ChoiceDraft[] {
  return [
    {
      key: `yes-${Date.now()}`,
      choice_text: "ใช่",
      choice_text_en: "Yes",
      order: 1,
      status: "1",
      next_box_id: null,
      newBoxDraftText: "",
    },
    {
      key: `no-${Date.now()}`,
      choice_text: "ไม่ใช่",
      choice_text_en: "No",
      order: 2,
      status: "1",
      next_box_id: null,
      newBoxDraftText: "",
    },
  ];
}

function isBinaryChoiceSet(choices: ChoiceDraft[]) {
  const active = choices.filter((choice) => !choice.removed);
  if (active.length !== 2) return false;
  const texts = active.map((choice) => choice.choice_text.trim().toLocaleLowerCase());
  return texts.some((text) => /^(ใช่|yes)$/.test(text)) && texts.some((text) => /^(ไม่|ไม่ใช่|no)$/.test(text));
}

interface ChoiceDraft {
  key: string;
  choice_id?: string;
  choice_text: string;
  choice_text_en: string;
  order: number;
  status: "1" | "2";
  next_box_id: string | null;
  newBoxDraftText: string;
  removed?: boolean;
}

interface QuestionBoxEditorDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  diagramId: string;
  box: QuestionBox | null;
  allBoxes: QuestionBox[];
  onSaved: (savedBox?: QuestionBox) => void;
}

export function QuestionBoxEditorDialog({
  open,
  onOpenChange,
  diagramId,
  box,
  allBoxes,
  onSaved,
}: QuestionBoxEditorDialogProps) {
  const [questionText, setQuestionText] = useState("");
  const [detail, setDetail] = useState("");
  const [questionType, setQuestionType] = useState<QuestionType>("S");
  const [answerMode, setAnswerMode] = useState<AnswerMode>("binary");
  const [status, setStatus] = useState<"1" | "2">("1");
  const [choices, setChoices] = useState<ChoiceDraft[]>([]);
  const [draggedChoiceKey, setDraggedChoiceKey] = useState<string | null>(null);
  const [dragOverChoiceKey, setDragOverChoiceKey] = useState<string | null>(null);

  // ---- ใช้เฉพาะ type M ----
  const [minRequired, setMinRequired] = useState<number>(1);
  const [yesNextBoxId, setYesNextBoxId] = useState<string | null>(null);
  const [noNextBoxId, setNoNextBoxId] = useState<string | null>(null);
  // -------------------------

  const [saving, setSaving] = useState(false);
  const [deleting, setDeleting] = useState(false);
  const [confirmDeleteOpen, setConfirmDeleteOpen] = useState(false);

  useEffect(() => {
    if (!open) return;

    if (box) {
      setQuestionText(box.question_text);
      setDetail(box.detail ?? "");
      setQuestionType(box.question_type);
      setStatus(box.status);
      setMinRequired(Math.max(1, box.min_required ?? 1));
      setYesNextBoxId(box.yes_next_box_id);
      setNoNextBoxId(box.no_next_box_id);

      const sorted = [...(box.choices ?? [])].sort((a, b) => a.order - b.order);
      const drafts = sorted.map((c) => ({
          key: c.choice_id,
          choice_id: c.choice_id,
          choice_text: c.choice_text,
          choice_text_en: c.choice_text_en ?? "",
          order: c.order,
          status: c.status,
          next_box_id: c.next_box_id,
          newBoxDraftText: "",
        }));
      setChoices(drafts);
      setAnswerMode(
        box.answer_mode ?? (box.question_type === "M" ? "checklist" : isBinaryChoiceSet(drafts) ? "binary" : "multiple"),
      );
    } else {
      setQuestionText("");
      setDetail("");
      setQuestionType("S");
      setAnswerMode("binary");
      setStatus("1");
      setMinRequired(1);
      setYesNextBoxId(null);
      setNoNextBoxId(null);
      setChoices(binaryChoices());
    }
  }, [open, box]);

  function addChoice() {
    setChoices((prev) => {
      const nextOrder = Math.max(0, ...prev.filter((choice) => !choice.removed).map((choice) => choice.order)) + 1;
      return [
        ...prev,
        {
        key: `new-${Date.now()}-${Math.random()}`,
        choice_text: "",
        choice_text_en: "",
        order: nextOrder,
        status: "1",
        next_box_id: null,
        newBoxDraftText: "",
        },
      ];
    });
  }

  function changeAnswerMode(mode: AnswerMode) {
    const previousMode = answerMode;
    setAnswerMode(mode);
    setQuestionType(mode === "checklist" ? "M" : "S");
    if (mode === "binary") {
      const existing = choices.filter((choice) => !choice.removed);
      const yes = existing[0];
      const no = existing[1];
      setChoices([
        { ...(yes ?? binaryChoices()[0]), key: yes?.key ?? `yes-${Date.now()}`, choice_text: "ใช่", choice_text_en: "Yes", order: 1, status: "1", removed: false },
        { ...(no ?? binaryChoices()[1]), key: no?.key ?? `no-${Date.now()}`, choice_text: "ไม่ใช่", choice_text_en: "No", order: 2, status: "1", removed: false },
        ...existing.slice(2).map((choice) => ({ ...choice, status: "2" as const, removed: false })),
        ...choices.filter((choice) => choice.removed),
      ]);
    } else if (previousMode === "binary") {
      setChoices((prev) => prev.map((choice) => (
        choice.removed ? choice : { ...choice, status: "1" as const }
      )));
    }
  }

  function updateChoice(key: string, patch: Partial<ChoiceDraft>) {
    setChoices((prev) => prev.map((c) => (c.key === key ? { ...c, ...patch } : c)));
  }

  function removeChoice(key: string) {
    setChoices((prev) => {
      const target = prev.find((c) => c.key === key);
      if (!target) return prev;
      if (target.choice_id) {
        return prev.map((c) => (c.key === key ? { ...c, removed: true } : c));
      }
      return prev.filter((c) => c.key !== key);
    });
  }

  function reorderChoice(targetKey: string) {
    if (!draggedChoiceKey || draggedChoiceKey === targetKey) return;

    setChoices((prev) => {
      const active = prev.filter((choice) => !choice.removed);
      const fromIndex = active.findIndex((choice) => choice.key === draggedChoiceKey);
      const toIndex = active.findIndex((choice) => choice.key === targetKey);
      if (fromIndex < 0 || toIndex < 0) return prev;

      const reordered = [...active];
      const [dragged] = reordered.splice(fromIndex, 1);
      reordered.splice(toIndex, 0, dragged);
      const normalized = reordered.map((choice, index) => ({ ...choice, order: index + 1 }));

      return [...normalized, ...prev.filter((choice) => choice.removed)];
    });
  }

  function handleDragStart(event: DragEvent<HTMLElement>, choiceKey: string) {
    setDraggedChoiceKey(choiceKey);
    event.dataTransfer.effectAllowed = "move";

    const card = event.currentTarget.closest<HTMLElement>("[data-choice-card]");
    if (!card) return;

    const preview = card.cloneNode(true) as HTMLElement;
    preview.style.width = `${card.getBoundingClientRect().width}px`;
    preview.style.position = "fixed";
    preview.style.top = "-10000px";
    preview.style.left = "-10000px";
    preview.style.background = "white";
    preview.style.boxShadow = "0 12px 30px rgb(15 23 42 / 0.2)";
    document.body.appendChild(preview);
    event.dataTransfer.setDragImage(preview, 28, 28);
    window.setTimeout(() => preview.remove(), 0);
  }

  async function handleSave() {
    if (!questionText.trim()) {
      toast.error("กรุณากรอกข้อความคำถาม");
      return;
    }

    const visibleChoices = choices.filter((c) => !c.removed);

    if (questionType === "M" && visibleChoices.length < 2) {
      toast.error("กล่องคำถามแบบติ๊กหลายข้อต้องมีตัวเลือกอย่างน้อย 2 ข้อ");
      return;
    }

    if (questionType === "M" && minRequired < 1) {
      toast.error("จำนวนข้อขั้นต่ำต้องเริ่มตั้งแต่ 1 ข้อ");
      return;
    }

    if (questionType === "M" && minRequired > visibleChoices.length) {
      toast.error("จำนวนข้อขั้นต่ำต้องไม่มากกว่าจำนวนตัวเลือกทั้งหมด");
      return;
    }

    for (const c of visibleChoices) {
      if (!c.choice_text.trim()) {
        toast.error("กรุณากรอกข้อความตัวเลือกให้ครบทุกข้อ");
        return;
      }
      if (questionType === "S" && c.next_box_id === NEW_BOX_VALUE && !c.newBoxDraftText.trim()) {
        toast.error("กรุณากรอกคำถามของกล่องใหม่ที่จะเชื่อมโยง");
        return;
      }
    }

    setSaving(true);
    try {
      const boxPayload = {
        question_text: questionText.trim(),
        detail: detail.trim() || null,
        question_type: questionType,
        answer_mode: answerMode,
        status,
        ...(questionType === "M"
          ? {
              min_required: minRequired,
              yes_next_box_id: yesNextBoxId,
              no_next_box_id: noNextBoxId,
            }
          : { min_required: null }),
      };

      const savedBox = box
        ? await questionBoxApi.update(diagramId, box.box_id, boxPayload)
        : await questionBoxApi.create(diagramId, boxPayload);

      for (const draft of choices) {
        if (draft.removed) {
          if (draft.choice_id) {
            await answerChoiceApi.delete(savedBox.box_id, draft.choice_id);
          }
          continue;
        }

        let resolvedNextBoxId: string | null = null;

        // next_box_id ต่อ choice ใช้ได้เฉพาะ type S เท่านั้น
        if (questionType === "S") {
          resolvedNextBoxId = draft.next_box_id;
          if (draft.next_box_id === NEW_BOX_VALUE) {
            const newBox = await questionBoxApi.create(diagramId, {
              question_text: draft.newBoxDraftText.trim(),
            });
            resolvedNextBoxId = newBox.box_id;
          }
        }

        const choicePayload = {
          choice_text: draft.choice_text.trim(),
          choice_text_en: draft.choice_text_en.trim() || null,
          order: draft.order,
          status: draft.status,
          ...(questionType === "S"
            ? { next_box_id: resolvedNextBoxId, next_diagram_id: null }
            : {}),
        };

        if (draft.choice_id) {
          await answerChoiceApi.update(savedBox.box_id, draft.choice_id, choicePayload);
        } else {
          await answerChoiceApi.create(savedBox.box_id, choicePayload);
        }
      }

      const finalizedBox = await questionBoxApi.update(diagramId, savedBox.box_id, {
        question_type: questionType,
        answer_mode: answerMode,
        sync_result_bindings: true,
      });

      toast.success(box ? "บันทึกกล่องคำถามสำเร็จ" : "สร้างกล่องคำถามสำเร็จ");
      onSaved(finalizedBox);
      onOpenChange(false);
    } catch (err) {
      const message =
        (axios.isAxiosError(err) && err.response?.data?.message) || "บันทึกไม่สำเร็จ กรุณาลองใหม่";
      toast.error(message);
    } finally {
      setSaving(false);
    }
  }

  async function handleDeleteBox() {
    if (!box) return;
    setDeleting(true);
    try {
      await questionBoxApi.delete(diagramId, box.box_id);
      toast.success("ลบกล่องคำถามสำเร็จ");
      setConfirmDeleteOpen(false);
      onOpenChange(false);
      onSaved();
    } catch (err) {
      const message =
        (axios.isAxiosError(err) && err.response?.data?.message) || "ไม่สามารถลบได้ กรุณาลองใหม่";
      toast.error(message);
    } finally {
      setDeleting(false);
    }
  }

  const availableChoices = choices.filter((c) => !c.removed);
  const visibleChoices = answerMode === "binary" ? availableChoices.slice(0, 2) : availableChoices;
  const nextBoxOptions = allBoxes.filter((b) => b.box_id !== box?.box_id);

  return (
    <>
      <Dialog open={open} onOpenChange={onOpenChange}>
        <DialogContent maxWidth="2xl" className="max-w-[100vw] overflow-x-hidden p-4 sm:p-6 md:max-h-[90vh] md:overflow-y-auto">
          <DialogHeader>
            <DialogTitle>{box ? "แก้ไขกล่องคำถาม" : "สร้างกล่องคำถามแรก"}</DialogTitle>
            <DialogDescription>
              {box
                ? "แก้ไขข้อความคำถามและจัดการตัวเลือกทั้งหมดของกล่องนี้"
                : "กรอกคำถามเริ่มต้นของแผนภูมินี้ พร้อมเพิ่มตัวเลือกได้เลยในหน้านี้"}
            </DialogDescription>
          </DialogHeader>

          <div className="space-y-4">
            <div>
              <label className="mb-1 block text-xs font-medium text-[var(--color-text-secondary)]">
                ข้อความคำถาม *
              </label>
              <Textarea
                value={questionText}
                onChange={(e) => setQuestionText(e.target.value)}
                rows={2}
                className="w-full rounded-lg border border-[var(--color-border)] px-3 py-2 text-sm"
                placeholder="เช่น ไม่ค่อยรู้สึกตัว? ปวดศีรษะมาก? หรือ มีอาการดังต่อไปนี้อย่างน้อย 2 ข้อ?"
              />
            </div>

            <div>
              <label className="mb-1 block text-xs font-medium text-[var(--color-text-secondary)]">
                รายละเอียด
              </label>
              <Textarea
                value={detail}
                onChange={(e) => setDetail(e.target.value)}
                rows={3}
                className="w-full rounded-lg border border-[var(--color-border)] px-3 py-2 text-sm"
                placeholder="ระบุรายละเอียดหรือคำอธิบายเพิ่มเติม..."
              />
            </div>

            <div className="flex flex-col gap-3 sm:flex-row">
              <div className="min-w-0 flex-1">
                <label className="mb-1 block text-xs font-medium text-[var(--color-text-secondary)]">
                  รูปแบบคำตอบ
                </label>
                <SimpleSelect
                  value={answerMode}
                  onChange={(value) => changeAnswerMode(value as AnswerMode)}
                  options={[
                    { value: "binary", label: "ใช่ / ไม่ใช่ — สร้างให้อัตโนมัติ" },
                    { value: "multiple", label: "มีหลายตัวเลือก — พิมพ์ตัวเลือกเอง" },
                    { value: "checklist", label: "เลือกได้หลายข้อ — พิมพ์รายการเอง" },
                  ]}
                />
              </div>
              <div className="min-w-0 flex-1">
                <label className="mb-1 block text-xs font-medium text-[var(--color-text-secondary)]">
                  สถานะ
                </label>
                <SimpleSelect
                  value={status}
                  onChange={(value) => setStatus(value as "1" | "2")}
                  options={[
                    { value: "1", label: "เปิดใช้งาน" },
                    { value: "2", label: "ปิดใช้งาน" },
                  ]}
                />
              </div>
            </div>

            {questionType === "M" && (
              <div className="rounded-lg border border-[var(--color-primary)]/30 bg-[var(--color-primary)]/5 p-3 space-y-3">
                <div>
                  <label className="mb-1 block text-xs font-medium text-[var(--color-text-secondary)]">
                    ต้องเลือกอย่างน้อยกี่รายการ จึงไปทาง "ใช่" *
                  </label>
                  <input
                    type="number"
                    min={1}
                    max={visibleChoices.length || undefined}
                    value={minRequired}
                    onChange={(e) => setMinRequired(Math.max(1, Number(e.target.value) || 1))}
                    className="w-24 rounded-lg border border-[var(--color-border)] px-3 py-2 text-sm"
                  />
                  <span className="ml-2 text-xs text-[var(--color-text-secondary)]">
                    จากทั้งหมด {visibleChoices.length} รายการ
                  </span>
                  <p className="mt-1.5 text-xs text-[var(--color-text-secondary)]">
                    เลือกตั้งแต่ {minRequired} รายการขึ้นไป → "ใช่" · เลือกน้อยกว่า {minRequired} รายการ → "ไม่ใช่"
                  </p>
                </div>

                <div className="grid gap-3 sm:grid-cols-2">
                  <div>
                    <label className="mb-1 block text-xs font-medium text-green-700">
                      ถ้า "ใช่" → ไปกล่องคำถาม
                    </label>
                    <SimpleSelect
                      value={yesNextBoxId ?? NONE_VALUE}
                      onChange={(value) => setYesNextBoxId(value === NONE_VALUE ? null : value)}
                      options={[
                        { value: NONE_VALUE, label: "— ไม่เชื่อมโยง (จบ/ผลลัพธ์) —" },
                        ...nextBoxOptions.map((b) => ({
                          value: b.box_id,
                          label: truncateText(b.question_text, 25),
                        })),
                      ]}
                    />
                  </div>
                  <div>
                    <label className="mb-1 block text-xs font-medium text-red-700">
                      ถ้า "ไม่ใช่" → ไปกล่องคำถาม
                    </label>
                    <SimpleSelect
                      value={noNextBoxId ?? NONE_VALUE}
                      onChange={(value) => setNoNextBoxId(value === NONE_VALUE ? null : value)}
                      options={[
                        { value: NONE_VALUE, label: "— ไม่เชื่อมโยง (จบ/ผลลัพธ์) —" },
                        ...nextBoxOptions.map((b) => ({
                          value: b.box_id,
                          label: truncateText(b.question_text, 25),
                        })),
                      ]}
                    />
                  </div>
                </div>
              </div>
            )}

            {answerMode === "binary" && (
              <div className="rounded-lg border border-emerald-200 bg-emerald-50/60 p-3 text-sm text-emerald-800">
                ระบบสร้างคำตอบ <strong>ใช่</strong> และ <strong>ไม่ใช่</strong> ให้แล้วโดยอัตโนมัติ — ไม่ต้องพิมพ์ข้อความคำตอบเอง
                {availableChoices.length > 2 && (
                  <span className="mt-1 block text-xs">
                    ตัวเลือกเดิมอีก {availableChoices.length - 2} รายการและผลลัพธ์ที่เชื่อมไว้ถูกพักไว้ และจะกลับมาเมื่อเปลี่ยนเป็นหลายตัวเลือก
                  </span>
                )}
              </div>
            )}

            <div className="border-t border-[var(--color-border)] pt-4">
              <div className="mb-2 flex items-center justify-between">
                <span className="text-sm font-semibold text-[var(--color-text-primary)]">
                  {questionType === "M" ? "รายการอาการให้เลือก" : "ตัวเลือก"} ({visibleChoices.length})
                </span>
                {answerMode !== "binary" && (
                  <Button variant="outline" size="sm" onClick={addChoice}>
                    <Plus className="h-3.5 w-3.5" />
                    เพิ่ม{questionType === "M" ? "รายการ" : "ตัวเลือก"}
                  </Button>
                )}
              </div>

              {visibleChoices.length === 0 ? (
                <p className="rounded-lg border border-dashed border-[var(--color-border)] p-3 text-center text-xs text-[var(--color-text-secondary)]">
                  ยังไม่มีตัวเลือก คลิก "เพิ่ม" เพื่อเริ่มต้น
                </p>
              ) : (
                <div className="max-h-[45vh] space-y-3 overflow-y-auto pr-1 sm:max-h-none sm:overflow-visible sm:pr-0">
                  {visibleChoices.map((c) => (
                    <div
                      key={c.key}
                      data-choice-card
                      onDragEnter={() => setDragOverChoiceKey(c.key)}
                      onDragOver={(event) => {
                        event.preventDefault();
                        event.dataTransfer.dropEffect = "move";
                      }}
                      onDragLeave={(event) => {
                        if (!event.currentTarget.contains(event.relatedTarget as Node | null)) {
                          setDragOverChoiceKey(null);
                        }
                      }}
                      onDrop={() => {
                        reorderChoice(c.key);
                        setDragOverChoiceKey(null);
                      }}
                      className={`rounded-lg border p-3 transition-all ${
                        dragOverChoiceKey === c.key && draggedChoiceKey !== c.key
                          ? "border-[var(--color-primary)] bg-[var(--color-primary)]/5 shadow-[0_0_0_2px_rgb(37_99_235_/_0.12)]"
                          : "border-[var(--color-border)]"
                      } ${
                        draggedChoiceKey === c.key ? "scale-[0.99] opacity-40" : ""
                      }`}
                    >
                      <div className="mb-2 flex min-w-0 items-start gap-2">
                        {answerMode !== "binary" && (
                          <Tooltip>
                            <TooltipTrigger asChild>
                              <span
                                draggable
                                onDragStart={(event) => handleDragStart(event, c.key)}
                                onDragEnd={() => {
                                  setDraggedChoiceKey(null);
                                  setDragOverChoiceKey(null);
                                }}
                                className="mt-1 flex cursor-grab touch-none rounded p-1 text-[var(--color-text-secondary)] hover:bg-[var(--color-surface)] active:cursor-grabbing"
                                aria-label="ลากเพื่อเปลี่ยนลำดับ"
                              >
                                <GripVertical className="h-4 w-4" />
                              </span>
                            </TooltipTrigger>
                            <TooltipContent>กดค้างแล้วลากเพื่อเปลี่ยนลำดับ</TooltipContent>
                          </Tooltip>
                        )}
                        {questionType === "M" && (
                          <span className="mt-1.5 flex h-4 w-4 flex-shrink-0 items-center justify-center rounded border border-[var(--color-border)]">
                            <span className="h-2 w-2 rounded-sm bg-[var(--color-border)]" />
                          </span>
                        )}
                        <input
                          value={c.choice_text}
                          readOnly={answerMode === "binary"}
                          onChange={(e) => updateChoice(c.key, { choice_text: e.target.value })}
                          placeholder={
                            questionType === "M" ? "เช่น เหนื่อยง่าย, มือสั่น, คอพอก" : "ข้อความตัวเลือก เช่น ใช่ / ไม่"
                          }
                          className="min-w-0 flex-1 rounded-lg border border-[var(--color-border)] px-2.5 py-1.5 text-sm read-only:bg-slate-50 read-only:font-semibold"
                        />
                        {answerMode !== "binary" && (
                          <span className="min-w-8 pt-1.5 text-center text-sm text-[var(--color-text-secondary)]">
                            {c.order}
                          </span>
                        )}
                        {answerMode !== "binary" && (
                          <Tooltip>
                            <TooltipTrigger asChild>
                              <button
                                type="button"
                                onClick={() => removeChoice(c.key)}
                                className="rounded-lg p-1.5 text-red-500 hover:bg-red-50"
                                aria-label="ลบรายการ"
                              >
                                <Trash2 className="h-4 w-4" />
                              </button>
                            </TooltipTrigger>
                            <TooltipContent>ลบรายการ</TooltipContent>
                          </Tooltip>
                        )}
                      </div>

                      <div className="flex min-w-0 flex-col gap-2 sm:flex-row">
                        <SimpleSelect
                          value={c.status}
                          onChange={(value) => updateChoice(c.key, { status: value as "1" | "2" })}
                          options={[
                            { value: "1", label: "เปิดใช้งาน" },
                            { value: "2", label: "ปิดใช้งาน" },
                          ]}
                          className="sm:w-36"
                        />

                        {/* next_box_id เลือกได้เฉพาะ type S เท่านั้น — type M ไม่แสดง dropdown นี้เลย */}
                        {questionType === "S" && (
                          <SimpleSelect
                            value={c.next_box_id ?? NONE_VALUE}
                            onChange={(value) => updateChoice(c.key, {
                              next_box_id: value === NONE_VALUE ? null : value,
                            })}
                            options={[
                              { value: NONE_VALUE, label: "— ไม่เชื่อมโยง (จบ/ผลลัพธ์) —" },
                              ...nextBoxOptions.map((b) => ({
                                value: b.box_id,
                                label: truncateText(b.question_text, 30),
                              })),
                              { value: NEW_BOX_VALUE, label: "+ สร้างกล่องคำถามใหม่..." },
                            ]}
                            className="min-w-0 flex-1"
                          />
                        )}
                      </div>

                      {questionType === "S" && c.next_box_id === NEW_BOX_VALUE && (
                        <input
                          value={c.newBoxDraftText}
                          onChange={(e) => updateChoice(c.key, { newBoxDraftText: e.target.value })}
                          placeholder="ข้อความคำถามของกล่องใหม่ที่จะสร้าง"
                          className="mt-2 w-full rounded-lg border border-[var(--color-primary)] px-2.5 py-1.5 text-sm"
                        />
                      )}
                    </div>
                  ))}
                </div>
              )}
            </div>
          </div>

          <DialogFooter className="flex items-center gap-2 sm:gap-3">
            {box ? (
              <Button className="shrink-0 px-3 text-xs sm:px-5 sm:text-sm" variant="danger" onClick={() => setConfirmDeleteOpen(true)}>
                ลบกล่องคำถาม
              </Button>
            ) : (
              <span />
            )}
            <div className="ml-auto flex shrink-0 gap-2 sm:gap-3">
              <Button className="px-3 text-xs sm:px-5 sm:text-sm" variant="outline" onClick={() => onOpenChange(false)}>
                ยกเลิก
              </Button>
              <Button className="px-3 text-xs sm:px-5 sm:text-sm" onClick={handleSave} loading={saving}>
                บันทึก
              </Button>
            </div>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      <AlertDialog open={confirmDeleteOpen} onOpenChange={setConfirmDeleteOpen}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>ยืนยันการลบกล่องคำถาม</AlertDialogTitle>
            <AlertDialogDescription>
              ต้องการลบกล่องคำถาม "{box?.question_text}" หรือไม่?
              ตัวเลือก ผลลัพธ์ และเส้นเชื่อมที่เกี่ยวข้องกับกล่องนี้จะถูกลบโดยอัตโนมัติ
              และไม่สามารถกู้คืนได้
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel disabled={deleting}>ยกเลิก</AlertDialogCancel>
            <AlertDialogAction onClick={handleDeleteBox} loading={deleting}>
              ลบกล่องคำถาม
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </>
  );
}
