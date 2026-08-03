import { useEffect, useState } from "react";
import axios from "axios";
import { toast } from "sonner";
import { Plus, Trash2 } from "lucide-react";
import { questionBoxApi } from "@/lib/api/questionBox";
import { answerChoiceApi } from "@/lib/api/answerChoice";
import type { QuestionBox, QuestionType } from "@/types/questionBox";
import { Button } from "@/components/ui/Button";
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
const NONE_VALUE = "";
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
  const [questionTextEn, setQuestionTextEn] = useState("");
  const [questionType, setQuestionType] = useState<QuestionType>("S");
  const [answerMode, setAnswerMode] = useState<AnswerMode>("binary");
  const [status, setStatus] = useState<"1" | "2">("1");
  const [choices, setChoices] = useState<ChoiceDraft[]>([]);

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
      setQuestionTextEn(box.question_text_en ?? "");
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
      setAnswerMode(box.question_type === "M" ? "checklist" : isBinaryChoiceSet(drafts) ? "binary" : "multiple");
    } else {
      setQuestionText("");
      setQuestionTextEn("");
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
    setAnswerMode(mode);
    setQuestionType(mode === "checklist" ? "M" : "S");
    if (mode === "binary" && !isBinaryChoiceSet(choices)) {
      const existing = choices.filter((choice) => !choice.removed);
      const yes = existing[0];
      const no = existing[1];
      setChoices([
        { ...(yes ?? binaryChoices()[0]), key: yes?.key ?? `yes-${Date.now()}`, choice_text: "ใช่", choice_text_en: "Yes", order: 1, removed: false },
        { ...(no ?? binaryChoices()[1]), key: no?.key ?? `no-${Date.now()}`, choice_text: "ไม่ใช่", choice_text_en: "No", order: 2, removed: false },
        ...choices.filter((choice) => choice.choice_id && choice !== yes && choice !== no).map((choice) => ({ ...choice, removed: true })),
      ]);
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
        question_text_en: questionTextEn.trim() || null,
        question_type: questionType,
        status,
        ...(questionType === "M"
          ? {
              min_required: minRequired,
              yes_next_box_id: yesNextBoxId,
              no_next_box_id: noNextBoxId,
            }
          : {
              min_required: null,
              yes_next_box_id: null,
              no_next_box_id: null,
            }),
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

      toast.success(box ? "บันทึกกล่องคำถามสำเร็จ" : "สร้างกล่องคำถามสำเร็จ");
      onSaved(savedBox);
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

  const visibleChoices = choices.filter((c) => !c.removed);
  const nextBoxOptions = allBoxes.filter((b) => b.box_id !== box?.box_id);

  return (
    <>
      <Dialog open={open} onOpenChange={onOpenChange}>
        <DialogContent maxWidth="2xl" className="md:max-h-[90vh] md:overflow-y-auto">
          <DialogHeader>
            <DialogTitle>{box ? `แก้ไขกล่องคำถาม ${box.box_id}` : "สร้างกล่องคำถามแรก"}</DialogTitle>
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
              <textarea
                value={questionText}
                onChange={(e) => setQuestionText(e.target.value)}
                rows={2}
                className="w-full rounded-lg border border-[var(--color-border)] px-3 py-2 text-sm"
                placeholder="เช่น ไม่ค่อยรู้สึกตัว? ปวดศีรษะมาก? หรือ มีอาการดังต่อไปนี้อย่างน้อย 2 ข้อ?"
              />
            </div>

            <div>
              <label className="mb-1 block text-xs font-medium text-[var(--color-text-secondary)]">
                ข้อความคำถาม (English)
              </label>
              <textarea
                value={questionTextEn}
                onChange={(e) => setQuestionTextEn(e.target.value)}
                rows={2}
                className="w-full rounded-lg border border-[var(--color-border)] px-3 py-2 text-sm"
              />
            </div>

            <div className="flex gap-3">
              <div className="flex-1">
                <label className="mb-1 block text-xs font-medium text-[var(--color-text-secondary)]">
                  รูปแบบคำตอบ
                </label>
                <select
                  value={answerMode}
                  onChange={(e) => changeAnswerMode(e.target.value as AnswerMode)}
                  className="w-full rounded-lg border border-[var(--color-border)] px-3 py-2 text-sm"
                >
                  <option value="binary">ใช่ / ไม่ใช่ — สร้างให้อัตโนมัติ</option>
                  <option value="multiple">มีหลายตัวเลือก — พิมพ์ตัวเลือกเอง</option>
                  <option value="checklist">เลือกได้หลายข้อ — พิมพ์รายการเอง</option>
                </select>
              </div>
              <div className="flex-1">
                <label className="mb-1 block text-xs font-medium text-[var(--color-text-secondary)]">
                  สถานะ
                </label>
                <select
                  value={status}
                  onChange={(e) => setStatus(e.target.value as "1" | "2")}
                  className="w-full rounded-lg border border-[var(--color-border)] px-3 py-2 text-sm"
                >
                  <option value="1">เปิดใช้งาน</option>
                  <option value="2">ปิดใช้งาน</option>
                </select>
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

                <div className="grid grid-cols-2 gap-3">
                  <div>
                    <label className="mb-1 block text-xs font-medium text-green-700">
                      ถ้า "ใช่" → ไปกล่องคำถาม
                    </label>
                    <select
                      value={yesNextBoxId ?? NONE_VALUE}
                      onChange={(e) => setYesNextBoxId(e.target.value || null)}
                      className="w-full rounded-lg border border-[var(--color-border)] px-2 py-1.5 text-xs"
                    >
                      <option value={NONE_VALUE}>— ไม่เชื่อมโยง (จบ/ผลลัพธ์) —</option>
                      {nextBoxOptions.map((b) => (
                        <option key={b.box_id} value={b.box_id}>
                          {b.box_id} — {truncateText(b.question_text, 25)}
                        </option>
                      ))}
                    </select>
                  </div>
                  <div>
                    <label className="mb-1 block text-xs font-medium text-red-700">
                      ถ้า "ไม่ใช่" → ไปกล่องคำถาม
                    </label>
                    <select
                      value={noNextBoxId ?? NONE_VALUE}
                      onChange={(e) => setNoNextBoxId(e.target.value || null)}
                      className="w-full rounded-lg border border-[var(--color-border)] px-2 py-1.5 text-xs"
                    >
                      <option value={NONE_VALUE}>— ไม่เชื่อมโยง (จบ/ผลลัพธ์) —</option>
                      {nextBoxOptions.map((b) => (
                        <option key={b.box_id} value={b.box_id}>
                          {b.box_id} — {truncateText(b.question_text, 25)}
                        </option>
                      ))}
                    </select>
                  </div>
                </div>
              </div>
            )}

            {answerMode === "binary" && (
              <div className="rounded-lg border border-emerald-200 bg-emerald-50/60 p-3 text-sm text-emerald-800">
                ระบบสร้างคำตอบ <strong>ใช่</strong> และ <strong>ไม่ใช่</strong> ให้แล้วโดยอัตโนมัติ — ไม่ต้องพิมพ์ข้อความคำตอบเอง
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
                <div className="space-y-3">
                  {visibleChoices.map((c) => (
                    <div key={c.key} className="rounded-lg border border-[var(--color-border)] p-3">
                      <div className="mb-2 flex items-start gap-2">
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
                            questionType === "M" ? "เช่น เหนื่อยง่าย, มือสั่น, คอพอก" : "ข้อความตัวเลือก เช่น ใช่ / ไม่ใช่"
                          }
                          className="flex-1 rounded-lg border border-[var(--color-border)] px-2.5 py-1.5 text-sm read-only:bg-slate-50 read-only:font-semibold"
                        />
                        {answerMode !== "binary" && <input
                          type="number"
                          value={c.order}
                          onChange={(e) => updateChoice(c.key, { order: Number(e.target.value) })}
                          className="w-16 rounded-lg border border-[var(--color-border)] px-2 py-1.5 text-sm"
                          title="ลำดับ"
                        />}
                        {answerMode !== "binary" && <button
                          type="button"
                          onClick={() => removeChoice(c.key)}
                          className="rounded-lg p-1.5 text-red-500 hover:bg-red-50"
                        >
                          <Trash2 className="h-4 w-4" />
                        </button>}
                      </div>

                      <div className="flex gap-2">
                        <select
                          value={c.status}
                          onChange={(e) => updateChoice(c.key, { status: e.target.value as "1" | "2" })}
                          className="rounded-lg border border-[var(--color-border)] px-2 py-1.5 text-xs"
                        >
                          <option value="1">เปิดใช้งาน</option>
                          <option value="2">ปิดใช้งาน</option>
                        </select>

                        {/* next_box_id เลือกได้เฉพาะ type S เท่านั้น — type M ไม่แสดง dropdown นี้เลย */}
                        {questionType === "S" && (
                          <select
                            value={c.next_box_id ?? ""}
                            onChange={(e) => updateChoice(c.key, { next_box_id: e.target.value || null })}
                            className="flex-1 rounded-lg border border-[var(--color-border)] px-2 py-1.5 text-xs"
                          >
                            <option value="">— ไม่เชื่อมโยง (จบ/ผลลัพธ์) —</option>
                            {nextBoxOptions.map((b) => (
                              <option key={b.box_id} value={b.box_id}>
                                {b.box_id} — {truncateText(b.question_text, 30)}
                              </option>
                            ))}
                            <option value={NEW_BOX_VALUE}>+ สร้างกล่องคำถามใหม่...</option>
                          </select>
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

          <DialogFooter className="justify-between sm:justify-between">
            {box ? (
              <Button variant="danger" onClick={() => setConfirmDeleteOpen(true)}>
                ลบกล่องคำถาม
              </Button>
            ) : (
              <span />
            )}
            <div className="flex gap-2">
              <Button variant="outline" onClick={() => onOpenChange(false)}>
                ยกเลิก
              </Button>
              <Button onClick={handleSave} loading={saving}>
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
