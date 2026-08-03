import { useState, useEffect, useCallback } from "react";
import { toast } from "sonner";
import { Plus } from "lucide-react";
import { questionBoxApi } from "@/lib/api/questionBox";
import { diagramApi } from "@/lib/api/diagram";
import type { QuestionBox, QuestionType } from "@/types/questionBox";
import { Button } from "../../../components/ui/Button";
import { Input } from "../../../components/ui/Input";
import { Label } from "../../../components/ui/Label";
import { SimpleSelect } from "../../../components/ui/SimpleSelect";
import { Card } from "../../../components/ui/Card";
import { Spinner } from "../../../components/ui/Spinner";
import { getErrorMessage } from "@/lib/getErrorMessage";
import { QuestionBoxCard } from "./QuestionBoxCard";

interface QuestionBoxSectionProps {
  diagramId: string;
  entryBoxId: string | null;
  onEntryBoxChange: (boxId: string | null) => void;
}

const EMPTY_NEW_BOX = {
  question_text: "",
  question_text_en: "",
  question_type: "S" as QuestionType,
};

export function QuestionBoxSection({
  diagramId,
  entryBoxId,
  onEntryBoxChange,
}: QuestionBoxSectionProps) {
  const [boxes, setBoxes] = useState<QuestionBox[]>([]);
  const [loading, setLoading] = useState(true);
  const [adding, setAdding] = useState(false);
  const [savingNew, setSavingNew] = useState(false);
  const [newBox, setNewBox] = useState(EMPTY_NEW_BOX);
  const [settingEntryId, setSettingEntryId] = useState<string | null>(null);

  const loadBoxes = useCallback(() => {
    setLoading(true);
    return questionBoxApi
      .list(diagramId, { per_page: 100 })
      .then((res) => setBoxes(res.data))
      .catch(() => toast.error("ไม่สามารถโหลดกรอบคำถามได้"))
      .finally(() => setLoading(false));
  }, [diagramId]);

  useEffect(() => {
    loadBoxes();
  }, [loadBoxes]);

  const handleAddBox = async () => {
    if (!newBox.question_text.trim()) {
      toast.error("กรุณากรอกคำถาม");
      return;
    }
    setSavingNew(true);
    try {
      const created = await questionBoxApi.create(diagramId, newBox);
      setBoxes((prev) => [...prev, created]);
      setNewBox(EMPTY_NEW_BOX);
      setAdding(false);
      toast.success("เพิ่มกรอบคำถามสำเร็จ");
    } catch (err) {
      toast.error(getErrorMessage(err));
    } finally {
      setSavingNew(false);
    }
  };

  const handleBoxUpdated = (updated: QuestionBox) => {
    setBoxes((prev) =>
      prev.map((b) => (b.box_id === updated.box_id ? updated : b)),
    );
  };

  const handleBoxDeleted = (boxId: string) => {
    setBoxes((prev) => prev.filter((b) => b.box_id !== boxId));
    if (entryBoxId === boxId) onEntryBoxChange(null);
  };

  const handleSetEntry = async (boxId: string) => {
    if (boxId === entryBoxId) return;
    setSettingEntryId(boxId);
    try {
      await diagramApi.update(diagramId, { entry_box_id: boxId });
      onEntryBoxChange(boxId);
      toast.success("ตั้งเป็นกรอบเริ่มต้นสำเร็จ");
    } catch (err) {
      toast.error(getErrorMessage(err));
    } finally {
      setSettingEntryId(null);
    }
  };

  if (loading) {
    return (
      <div className="flex justify-center py-8">
        <Spinner label="กำลังโหลดกรอบคำถาม..." />
      </div>
    );
  }

  return (
    <div className="space-y-4">
      {boxes.length === 0 && !adding && (
        <p className="rounded-lg border border-dashed border-[var(--color-border)] p-6 text-center text-sm text-[var(--color-text-secondary)]">
          ยังไม่มีกรอบคำถามในแผนภูมินี้
        </p>
      )}

      {boxes.map((box) => (
        <QuestionBoxCard
          key={box.box_id}
          box={box}
          diagramId={diagramId}
          allBoxes={boxes}
          isEntryBox={box.box_id === entryBoxId}
          settingEntry={settingEntryId === box.box_id}
          onSetEntry={() => handleSetEntry(box.box_id)}
          onUpdated={handleBoxUpdated}
          onDeleted={handleBoxDeleted}
        />
      ))}

      {adding ? (
        <Card className="border-dashed">
          <div className="grid gap-3 sm:grid-cols-2">
            <div>
              <Label>คำถาม (ภาษาไทย)</Label>
              <Input
                value={newBox.question_text}
                onChange={(e) =>
                  setNewBox({ ...newBox, question_text: e.target.value })
                }
                placeholder="เช่น มีไข้สูงกว่า 39 องศาหรือไม่?"
                autoFocus
              />
            </div>
            <div>
              <Label>คำถาม (ภาษาอังกฤษ)</Label>
              <Input
                value={newBox.question_text_en}
                onChange={(e) =>
                  setNewBox({ ...newBox, question_text_en: e.target.value })
                }
              />
            </div>
          </div>
          <div className="mt-3 sm:w-52">
            <Label>รูปแบบคำตอบ</Label>
            <SimpleSelect
              value={newBox.question_type}
              onChange={(v) =>
                setNewBox({ ...newBox, question_type: v as QuestionType })
              }
              options={[
                { label: "เลือกได้ 1 ข้อ", value: "S" },
                { label: "เลือกได้หลายข้อ", value: "M" },
              ]}
            />
          </div>
          <div className="mt-4 flex justify-end gap-2">
            <Button
              variant="ghost"
              onClick={() => {
                setAdding(false);
                setNewBox(EMPTY_NEW_BOX);
              }}
            >
              ยกเลิก
            </Button>
            <Button onClick={handleAddBox} loading={savingNew}>
              เพิ่มกรอบคำถาม
            </Button>
          </div>
        </Card>
      ) : (
        <Button variant="outline" onClick={() => setAdding(true)}>
          <Plus className="h-4 w-4" />
          เพิ่มกรอบคำถาม
        </Button>
      )}
    </div>
  );
}