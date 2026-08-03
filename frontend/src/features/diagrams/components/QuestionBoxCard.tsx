import { useState } from "react";
import { toast } from "sonner";
import { MoreHorizontal, Trash2, Star, Plus, GripVertical } from "lucide-react";
import { questionBoxApi } from "@/lib/api/questionBox";
import { answerChoiceApi } from "@/lib/api/answerChoice";
import type { QuestionBox, QuestionType } from "@/types/questionBox";
import type { AnswerChoice, AnswerChoiceFormValues } from "@/types/answerChoice";
import { Card } from "../../../components/ui/Card";
import { Button } from "../../../components/ui/Button";
import { Input } from "../../../components/ui/Input";
import { Label } from "../../../components/ui/Label";
import { Badge } from "../../../components/ui/Badge";
import { SimpleSelect } from "../../../components/ui/SimpleSelect";
import { StatusToggle } from "../../../components/ui/StatusToggle";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from "../../../components/ui/DropdownMenu";
import {
  AlertDialog,
  AlertDialogContent,
  AlertDialogHeader,
  AlertDialogFooter,
  AlertDialogTitle,
  AlertDialogDescription,
  AlertDialogAction,
  AlertDialogCancel,
} from "../../../components/ui/AlertDialog";
import { getErrorMessage } from "@/lib/getErrorMessage";

interface QuestionBoxCardProps {
  box: QuestionBox;
  diagramId: string;
  allBoxes: QuestionBox[];
  isEntryBox: boolean;
  settingEntry: boolean;
  onSetEntry: () => void;
  onUpdated: (box: QuestionBox) => void;
  onDeleted: (boxId: string) => void;
}

const EMPTY_NEW_CHOICE = {
  choice_text: "",
  choice_text_en: "",
  next_box_id: null as string | null,
};

export function QuestionBoxCard({
  box,
  diagramId,
  allBoxes,
  isEntryBox,
  settingEntry,
  onSetEntry,
  onUpdated,
  onDeleted,
}: QuestionBoxCardProps) {
  const [form, setForm] = useState({
    question_text: box.question_text,
    question_text_en: box.question_text_en ?? "",
    question_type: box.question_type,
    status: box.status,
  });
  const [savingBox, setSavingBox] = useState(false);
  const [deleteOpen, setDeleteOpen] = useState(false);
  const [deleting, setDeleting] = useState(false);

  const [choices, setChoices] = useState<AnswerChoice[]>(box.choices ?? []);
  const [addingChoice, setAddingChoice] = useState(false);
  const [savingNewChoice, setSavingNewChoice] = useState(false);
  const [newChoice, setNewChoice] = useState(EMPTY_NEW_CHOICE);
  const [savingChoiceId, setSavingChoiceId] = useState<string | null>(null);
  const [deletingChoiceId, setDeletingChoiceId] = useState<string | null>(null);

  const boxDirty =
    form.question_text !== box.question_text ||
    form.question_text_en !== (box.question_text_en ?? "") ||
    form.question_type !== box.question_type ||
    form.status !== box.status;

  const otherBoxes = allBoxes.filter((b) => b.box_id !== box.box_id);
  const nextBoxOptions = [
    { label: "— จบการประเมิน —", value: "" },
    ...otherBoxes.map((b) => ({
      label: `${b.box_id} · ${b.question_text}`,
      value: b.box_id,
    })),
  ];

  const handleSaveBox = async () => {
    if (!form.question_text.trim()) {
      toast.error("กรุณากรอกคำถาม");
      return;
    }
    setSavingBox(true);
    try {
      const updated = await questionBoxApi.update(diagramId, box.box_id, form);
      onUpdated({ ...updated, choices });
      toast.success("บันทึกคำถามสำเร็จ");
    } catch (err) {
      toast.error(getErrorMessage(err));
    } finally {
      setSavingBox(false);
    }
  };

  const handleDeleteBox = async () => {
    setDeleting(true);
    try {
      await questionBoxApi.delete(diagramId, box.box_id);
      toast.success("ลบกรอบคำถามสำเร็จ");
      onDeleted(box.box_id);
    } catch (err) {
      // เช่น backend ปฏิเสธเพราะยังมีตัวเลือกคำตอบอยู่ในกรอบนี้
      toast.error(getErrorMessage(err));
    } finally {
      setDeleting(false);
      setDeleteOpen(false);
    }
  };

  const handleAddChoice = async () => {
    if (!newChoice.choice_text.trim()) {
      toast.error("กรุณากรอกข้อความตัวเลือก");
      return;
    }
    setSavingNewChoice(true);
    try {
      const created = await answerChoiceApi.create(box.box_id, {
        choice_text: newChoice.choice_text,
        choice_text_en: newChoice.choice_text_en || null,
        ...(form.question_type === "S" ? { next_box_id: newChoice.next_box_id } : {}),
        order: choices.length,
      });
      setChoices((prev) => [...prev, created]);
      setNewChoice(EMPTY_NEW_CHOICE);
      setAddingChoice(false);
      toast.success("เพิ่มตัวเลือกสำเร็จ");
    } catch (err) {
      toast.error(getErrorMessage(err));
    } finally {
      setSavingNewChoice(false);
    }
  };

  const handleUpdateChoice = async (
    choiceId: string,
    payload: Partial<AnswerChoiceFormValues>,
  ) => {
    setSavingChoiceId(choiceId);
    try {
      const updated = await answerChoiceApi.update(box.box_id, choiceId, payload);
      setChoices((prev) =>
        prev.map((c) => (c.choice_id === choiceId ? updated : c)),
      );
    } catch (err) {
      toast.error(getErrorMessage(err));
    } finally {
      setSavingChoiceId(null);
    }
  };

  const handleDeleteChoice = async (choiceId: string) => {
    setDeletingChoiceId(choiceId);
    try {
      await answerChoiceApi.delete(box.box_id, choiceId);
      setChoices((prev) => prev.filter((c) => c.choice_id !== choiceId));
      toast.success("ลบตัวเลือกสำเร็จ");
    } catch (err) {
      toast.error(getErrorMessage(err));
    } finally {
      setDeletingChoiceId(null);
    }
  };

  return (
    <Card className={isEntryBox ? "border-[var(--color-primary)]" : undefined}>
      <div className="mb-3 flex items-start justify-between gap-2">
        <div className="flex flex-wrap items-center gap-2">
          <span className="font-mono text-xs text-[var(--color-text-secondary)]">
            {box.box_id}
          </span>
          <Badge variant="default">
            {box.question_type === "M" ? "เลือกได้หลายข้อ" : "เลือกได้ 1 ข้อ"}
          </Badge>
          {isEntryBox && (
            <Badge
              variant="default"
              className="bg-[var(--color-primary-light)] text-[var(--color-primary)]"
            >
              <Star className="h-3 w-3" />
              กรอบเริ่มต้น
            </Badge>
          )}
        </div>

        <DropdownMenu>
          <DropdownMenuTrigger asChild>
            <Button variant="ghost" size="icon">
              <MoreHorizontal className="h-4 w-4" />
            </Button>
          </DropdownMenuTrigger>
          <DropdownMenuContent align="end">
            {!isEntryBox && (
              <DropdownMenuItem onClick={onSetEntry} disabled={settingEntry}>
                <Star className="h-4 w-4 text-[var(--color-text-secondary)]" />
                ตั้งเป็นกรอบเริ่มต้น
              </DropdownMenuItem>
            )}
            <DropdownMenuItem
              onClick={() => setDeleteOpen(true)}
              className="text-[var(--color-danger)] focus:bg-[var(--color-danger)]/10 focus:text-[var(--color-danger)]"
            >
              <Trash2 className="h-4 w-4" />
              ลบกรอบคำถาม
            </DropdownMenuItem>
          </DropdownMenuContent>
        </DropdownMenu>
      </div>

      <div className="grid gap-3 sm:grid-cols-2">
        <div>
          <Label>คำถาม (ภาษาไทย)</Label>
          <Input
            value={form.question_text}
            onChange={(e) => setForm({ ...form, question_text: e.target.value })}
          />
        </div>
        <div>
          <Label>คำถาม (ภาษาอังกฤษ)</Label>
          <Input
            value={form.question_text_en}
            onChange={(e) =>
              setForm({ ...form, question_text_en: e.target.value })
            }
          />
        </div>
      </div>

      <div className="mt-3 grid gap-3 sm:grid-cols-2">
        <div>
          <Label>รูปแบบคำตอบ</Label>
          <SimpleSelect
            value={form.question_type}
            onChange={(v) =>
              setForm({ ...form, question_type: v as QuestionType })
            }
            options={[
              { label: "เลือกได้ 1 ข้อ", value: "S" },
              { label: "เลือกได้หลายข้อ", value: "M" },
            ]}
          />
        </div>
        <div>
          <Label>สถานะ</Label>
          <SimpleSelect
            value={form.status}
            onChange={(v) => setForm({ ...form, status: v as "1" | "2" })}
            options={[
              { label: "ใช้งานได้", value: "1" },
              { label: "ปิดใช้งาน", value: "2" },
            ]}
          />
        </div>
      </div>

      {boxDirty && (
        <div className="mt-3 flex justify-end">
          <Button size="sm" onClick={handleSaveBox} loading={savingBox}>
            บันทึกคำถามนี้
          </Button>
        </div>
      )}

      <div className="mt-4 border-t border-[var(--color-border)] pt-3">
        <p className="mb-2 text-xs font-medium text-[var(--color-text-secondary)]">
          ตัวเลือกคำตอบ
        </p>

        <div className="space-y-2">
          {choices.map((choice) => (
            <div
              key={choice.choice_id}
              className="flex flex-col gap-2 rounded-lg border border-[var(--color-border)] p-2.5 sm:flex-row sm:items-center"
            >
              <GripVertical className="hidden h-4 w-4 shrink-0 text-[var(--color-text-secondary)] sm:block" />
              <Input
                className="sm:flex-1"
                defaultValue={choice.choice_text}
                onBlur={(e) => {
                  if (e.target.value !== choice.choice_text) {
                    handleUpdateChoice(choice.choice_id, {
                      choice_text: e.target.value,
                    });
                  }
                }}
              />
              {form.question_type === "S" && (
                <div className="sm:w-64">
                  <SimpleSelect
                    value={choice.next_box_id ?? ""}
                    onChange={(v) =>
                      handleUpdateChoice(choice.choice_id, {
                        next_box_id: v || null,
                      })
                    }
                    options={nextBoxOptions}
                    disabled={savingChoiceId === choice.choice_id}
                  />
                </div>
              )}
              <div className="flex items-center gap-2">
                <StatusToggle
                  active={choice.status === "1"}
                  onChange={() =>
                    handleUpdateChoice(choice.choice_id, {
                      status: choice.status === "1" ? "2" : "1",
                    })
                  }
                />
                <Button
                  variant="ghost"
                  size="icon"
                  onClick={() => handleDeleteChoice(choice.choice_id)}
                  loading={deletingChoiceId === choice.choice_id}
                >
                  <Trash2 className="h-4 w-4 text-[var(--color-danger)]" />
                </Button>
              </div>
            </div>
          ))}

          {choices.length === 0 && !addingChoice && (
            <p className="text-xs text-[var(--color-text-secondary)]">
              ยังไม่มีตัวเลือกคำตอบ
            </p>
          )}
        </div>

        {addingChoice ? (
          <div className="mt-2 flex flex-col gap-2 rounded-lg border border-dashed border-[var(--color-border)] p-2.5 sm:flex-row sm:items-center">
            <Input
              className="sm:flex-1"
              placeholder="ข้อความตัวเลือก"
              value={newChoice.choice_text}
              onChange={(e) =>
                setNewChoice({ ...newChoice, choice_text: e.target.value })
              }
              autoFocus
            />
            {form.question_type === "S" && (
              <div className="sm:w-64">
                <SimpleSelect
                  value={newChoice.next_box_id ?? ""}
                  onChange={(v) =>
                    setNewChoice({ ...newChoice, next_box_id: v || null })
                  }
                  options={nextBoxOptions}
                />
              </div>
            )}
            <div className="flex gap-2">
              <Button size="sm" onClick={handleAddChoice} loading={savingNewChoice}>
                เพิ่ม
              </Button>
              <Button
                size="sm"
                variant="ghost"
                onClick={() => {
                  setAddingChoice(false);
                  setNewChoice(EMPTY_NEW_CHOICE);
                }}
              >
                ยกเลิก
              </Button>
            </div>
          </div>
        ) : (
          <Button
            variant="ghost"
            size="sm"
            className="mt-2"
            onClick={() => setAddingChoice(true)}
          >
            <Plus className="h-4 w-4" />
            เพิ่มตัวเลือก
          </Button>
        )}
      </div>

      <AlertDialog open={deleteOpen} onOpenChange={setDeleteOpen}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>ลบกรอบคำถามนี้?</AlertDialogTitle>
            <AlertDialogDescription>
              หากยังมีตัวเลือกคำตอบอยู่ในกรอบนี้ ระบบจะไม่อนุญาตให้ลบ
              กรุณาลบตัวเลือกทั้งหมดก่อน
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel onClick={() => setDeleteOpen(false)}>
              ยกเลิก
            </AlertDialogCancel>
            <AlertDialogAction
              onClick={(e) => {
                e.preventDefault();
                handleDeleteBox();
              }}
              disabled={deleting}
            >
              ลบ
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </Card>
  );
}
