import { useEffect, useState } from "react";
import axios from "axios";
import { toast } from "sonner";
import { diagnosisRuleApi } from "@/lib/api/diagnosisRule";
import { diseaseApi } from "@/lib/api/disease";
import type {
  DiagnosisRule,
  DiagnosisRuleFormValues,
  UrgencyLevel,
  RuleConditionInput,
} from "@/types/diagnosisRule";
import { URGENCY_OPTIONS } from "@/types/diagnosisRule";
import type { QuestionBox } from "@/types/questionBox";
import type { Disease } from "@/types/disease";
import type { PathCondition } from "./flowTree";
import { Button } from "@/components/ui/Button";
import { SimpleSelect } from "@/components/ui/SimpleSelect";
import { RelatedSymptomsPicker } from "../RelatedSymptomsPicker";
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
  DialogFooter,
} from "@/components/ui/Dialog";

interface RuleEditorDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  diagramId: string;
  pathConditions: PathCondition[];
  existingRules: DiagnosisRule[];
  boxMap: Map<string, QuestionBox>;
  onSaved: () => void;
}

export function RuleEditorDialog({
  open,
  onOpenChange,
  diagramId,
  pathConditions,
  existingRules,
  boxMap,
  onSaved,
}: RuleEditorDialogProps) {
  const [selectedRuleId, setSelectedRuleId] = useState<string>("__new__");
  const [urgencyLevel, setUrgencyLevel] = useState<UrgencyLevel>("G");
  const [medicalReference, setMedicalReference] = useState("");
  const [timeFrame, setTimeFrame] = useState("");
  const [timeFrameEn, setTimeFrameEn] = useState("");
  const [note, setNote] = useState("");
  const [noteEn, setNoteEn] = useState("");
  const [diseaseIds, setDiseaseIds] = useState<string[]>([]);
  const [diseases, setDiseases] = useState<Disease[]>([]);
  const [saving, setSaving] = useState(false);

  useEffect(() => {
    if (!open) return;

    const controller = new AbortController();
    diseaseApi
      .list({ per_page: 100, status: "1" }, controller.signal)
      .then((res) => setDiseases(res.data))
      .catch(() => {
        if (!controller.signal.aborted) toast.error("โหลดรายชื่อโรคไม่สำเร็จ");
      });

    return () => controller.abort();
  }, [open]);

  useEffect(() => {
    if (!open) return;
    const rule = existingRules[0];
    if (rule) {
      setSelectedRuleId(rule.rule_id);
      setUrgencyLevel(rule.urgency_level);
      setMedicalReference(rule.medical_reference ?? "");
      setTimeFrame(rule.time_frame ?? "");
      setTimeFrameEn(rule.time_frame_en ?? "");
      setNote(rule.note ?? "");
      setNoteEn(rule.note_en ?? "");
      setDiseaseIds((rule.diseases ?? []).map((d) => d.disease_id));
      return;
    }

    setSelectedRuleId("__new__");
    setUrgencyLevel("G");
    setMedicalReference("");
    setTimeFrame("");
    setTimeFrameEn("");
    setNote("");
    setNoteEn("");
    setDiseaseIds([]);
  }, [open, pathConditions, existingRules]);

  async function handleSave() {
    const conditions: RuleConditionInput[] = pathConditions.map((c) => ({
      box_id: c.box_id,
      choice_id: c.choice_id,
      logic_operator: "AND",
      status: "1",
    }));

    const payload: DiagnosisRuleFormValues = {
      medical_reference: medicalReference.trim(),
      urgency_level: urgencyLevel,
      time_frame: timeFrame.trim(),
      time_frame_en: timeFrameEn.trim(),
      note: note.trim(),
      note_en: noteEn.trim(),
      status: "1",
      diagram_id: diagramId,
      disease_ids: diseaseIds,
      conditions,
    };

    setSaving(true);
    try {
      if (selectedRuleId !== "__new__") {
        await diagnosisRuleApi.update(selectedRuleId, payload);
        toast.success("แก้ไขผลลัพธ์สำเร็จ");
      } else {
        await diagnosisRuleApi.create(payload);
        toast.success("กำหนดผลลัพธ์สำเร็จ");
      }
      onSaved();
      onOpenChange(false);
    } catch (err) {
      const message =
        (axios.isAxiosError(err) && err.response?.data?.message) || "บันทึกไม่สำเร็จ กรุณาลองใหม่";
      toast.error(message);
    } finally {
      setSaving(false);
    }
  }

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent maxWidth="2xl" className="md:max-h-[90vh] md:overflow-y-auto">
        <DialogHeader>
          <DialogTitle>ตั้งค่าผลลัพธ์ปลายทาง</DialogTitle>
          <DialogDescription>
            กำหนดระดับความเร่งด่วน กรอบเวลา และคำแนะนำของผลลัพธ์นี้
            สามารถระบุโรคที่เกี่ยวข้องเพิ่มเติมได้หากจำเป็น
          </DialogDescription>
        </DialogHeader>

        <div className="space-y-4">
          <div>
            <label className="mb-1 block text-xs font-medium text-[var(--color-text-secondary)]">
              คำตอบที่ใช้ตัดสินผลลัพธ์
            </label>
            <div className="space-y-1 rounded-lg border border-[var(--color-border)] bg-[var(--color-bg-subtle,#f8fafc)] p-2.5">
              {pathConditions.length === 0 ? (
                <p className="text-xs text-[var(--color-text-secondary)]">ไม่มีเงื่อนไข (เริ่มจากกรอบแรก)</p>
              ) : (
                pathConditions.map((c, i) => {
                  const box = boxMap.get(c.box_id);
                  const choice = box?.choices?.find((ch) => ch.choice_id === c.choice_id);
                  return (
                    <p key={i} className="text-xs text-[var(--color-text-primary)]">
                      {i + 1}. {box?.question_text ?? c.box_id} →{" "}
                      <span className="font-medium">{choice?.choice_text ?? c.choice_id}</span>
                    </p>
                  );
                })
              )}
            </div>
          </div>

          <div>
            <label className="mb-2 block text-xs font-medium text-[var(--color-text-secondary)]">
              โรคที่เกี่ยวข้อง (ไม่บังคับและเลือกได้หลายรายการ)
            </label>
            <RelatedSymptomsPicker
              items={diseases.map((disease) => ({
                id: disease.disease_id,
                name: disease.disease_name,
                nameEn: disease.disease_name_en,
              }))}
              selectedIds={diseaseIds}
              onChange={setDiseaseIds}
              availableTitle="โรคทั้งหมด"
              selectedTitle="โรคที่เลือก"
              searchPlaceholder="ค้นหาโรค..."
              itemNoun="โรค"
            />
          </div>

          <SimpleSelect
            label="ระดับความเร่งด่วน *"
            value={urgencyLevel}
            onChange={(v) => setUrgencyLevel(v as UrgencyLevel)}
            options={URGENCY_OPTIONS.map((o) => ({ value: o.value, label: o.label }))}
          />

          <div className="flex gap-3">
            <div className="flex-1">
              <label className="mb-1 block text-xs font-medium text-[var(--color-text-secondary)]">
                กรอบเวลา (time frame)
              </label>
              <input
                value={timeFrame}
                onChange={(e) => setTimeFrame(e.target.value)}
                className="w-full rounded-lg border border-[var(--color-border)] px-3 py-2 text-sm"
                placeholder="เช่น ภายใน 24 ชั่วโมง"
              />
            </div>
            <div className="flex-1">
              <label className="mb-1 block text-xs font-medium text-[var(--color-text-secondary)]">
                Time frame (English)
              </label>
              <input
                value={timeFrameEn}
                onChange={(e) => setTimeFrameEn(e.target.value)}
                className="w-full rounded-lg border border-[var(--color-border)] px-3 py-2 text-sm"
              />
            </div>
          </div>

          <div>
            <label className="mb-1 block text-xs font-medium text-[var(--color-text-secondary)]">
              หมายเหตุ/คำแนะนำ
            </label>
            <textarea
              value={note}
              onChange={(e) => setNote(e.target.value)}
              rows={2}
              className="w-full rounded-lg border border-[var(--color-border)] px-3 py-2 text-sm"
            />
          </div>

          <div>
            <label className="mb-1 block text-xs font-medium text-[var(--color-text-secondary)]">
              หมายเหตุ (English)
            </label>
            <textarea
              value={noteEn}
              onChange={(e) => setNoteEn(e.target.value)}
              rows={2}
              className="w-full rounded-lg border border-[var(--color-border)] px-3 py-2 text-sm"
            />
          </div>

          <div>
            <label className="mb-1 block text-xs font-medium text-[var(--color-text-secondary)]">
              อ้างอิงทางการแพทย์
            </label>
            <input
              value={medicalReference}
              onChange={(e) => setMedicalReference(e.target.value)}
              className="w-full rounded-lg border border-[var(--color-border)] px-3 py-2 text-sm"
              placeholder="เช่น ตำราการตรวจรักษาโรคทั่วไป เล่ม 1 หน้า..."
            />
          </div>
        </div>

        <DialogFooter>
          <Button variant="outline" onClick={() => onOpenChange(false)}>
            ยกเลิก
          </Button>
          <Button onClick={handleSave} loading={saving}>
            บันทึกกฎ
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
