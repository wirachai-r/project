import { useEffect, useState } from "react";
import axios from "axios";
import { toast } from "sonner";
import { diagnosisRuleApi } from "@/lib/api/diagnosisRule";
import { diseaseApi } from "@/lib/api/disease";
import { diagramApi } from "@/lib/api/diagram";
import type {
  DiagnosisRule,
  DiagnosisRuleFormValues,
  UrgencyLevel,
  RuleConditionInput,
} from "@/types/diagnosisRule";
import { URGENCY_OPTIONS } from "@/types/diagnosisRule";
import type { QuestionBox } from "@/types/questionBox";
import type { Disease } from "@/types/disease";
import type { Diagram } from "@/types/diagram";
import type { PathCondition } from "./flowTree";
import { Button } from "@/components/ui/Button";
import { SimpleSelect } from "@/components/ui/SimpleSelect";
import { Textarea } from "@/components/ui/Textarea";
import { RelatedSymptomsPicker } from "../RelatedSymptomsPicker";
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
  const [note, setNote] = useState("");
  const [diseaseIds, setDiseaseIds] = useState<string[]>([]);
  const [diseases, setDiseases] = useState<Disease[]>([]);
  const [diagrams, setDiagrams] = useState<Diagram[]>([]);
  const [nextDiagramIds, setNextDiagramIds] = useState<string[]>([]);
  const [saving, setSaving] = useState(false);
  const [deleting, setDeleting] = useState(false);
  const [confirmDeleteOpen, setConfirmDeleteOpen] = useState(false);

  useEffect(() => {
    if (!open) return;

    const controller = new AbortController();
    const loadAllDiseases = async () => {
      const firstPage = await diseaseApi.list(
        { page: 1, per_page: 100, status: "1", sort_by: "name", sort_direction: "asc" },
        controller.signal,
      );
      const lastPage = firstPage.meta?.last_page ?? 1;
      if (lastPage === 1) return firstPage.data;

      const remainingPages = await Promise.all(
        Array.from({ length: lastPage - 1 }, (_, index) =>
          diseaseApi.list(
            {
              page: index + 2,
              per_page: 100,
              status: "1",
              sort_by: "name",
              sort_direction: "asc",
            },
            controller.signal,
          ),
        ),
      );

      return [firstPage, ...remainingPages].flatMap((page) => page.data);
    };

    loadAllDiseases()
      .then(setDiseases)
      .catch(() => {
        if (!controller.signal.aborted) toast.error("โหลดรายชื่อโรคไม่สำเร็จ");
      });

    const loadAllDiagrams = async () => {
      const firstPage = await diagramApi.list(
        { page: 1, per_page: 100, status: "1", sort_by: "name", sort_direction: "asc" },
        controller.signal,
      );
      const lastPage = firstPage.meta?.last_page ?? 1;
      const remainingPages = await Promise.all(
        Array.from({ length: Math.max(0, lastPage - 1) }, (_, index) =>
          diagramApi.list(
            { page: index + 2, per_page: 100, status: "1", sort_by: "name", sort_direction: "asc" },
            controller.signal,
          ),
        ),
      );
      return [firstPage, ...remainingPages]
        .flatMap((page) => page.data)
        .filter((item) => item.diagram_id !== diagramId);
    };

    loadAllDiagrams()
      .then(setDiagrams)
      .catch(() => {
        if (!controller.signal.aborted) toast.error("โหลดรายชื่อแผนภูมิไม่สำเร็จ");
      });

    return () => controller.abort();
  }, [open, diagramId]);

  useEffect(() => {
    if (!open) return;
    const rule = existingRules[0];
    if (rule) {
      setSelectedRuleId(rule.rule_id);
      setUrgencyLevel(rule.urgency_level);
      setMedicalReference(rule.medical_reference ?? "");
      setTimeFrame(rule.time_frame ?? "");
      setNote(rule.note ?? "");
      setDiseaseIds((rule.diseases ?? []).map((d) => d.disease_id));
      setNextDiagramIds((rule.next_diagrams ?? []).map((d) => d.diagram_id));
      return;
    }

    setSelectedRuleId("__new__");
    setUrgencyLevel("G");
    setMedicalReference("");
    setTimeFrame("");
    setNote("");
    setDiseaseIds([]);
    setNextDiagramIds([]);
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
      time_frame_en: "",
      note: note.trim(),
      note_en: "",
      status: "1",
      diagram_id: diagramId,
      disease_ids: diseaseIds,
      next_diagrams: nextDiagramIds.map((nextDiagramId, index) => ({
        diagram_id: nextDiagramId,
        display_order: index,
      })),
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

  async function handleDelete() {
    if (selectedRuleId === "__new__") return;
    setDeleting(true);
    try {
      await diagnosisRuleApi.delete(selectedRuleId);
      toast.success("ลบผลลัพธ์ปลายทางสำเร็จ");
      setConfirmDeleteOpen(false);
      onOpenChange(false);
      onSaved();
    } catch (err) {
      const message =
        (axios.isAxiosError(err) && err.response?.data?.message) ||
        "ไม่สามารถลบผลลัพธ์ปลายทางได้ กรุณาลองใหม่";
      toast.error(message);
    } finally {
      setDeleting(false);
    }
  }

  return (
    <>
      <Dialog open={open} onOpenChange={onOpenChange}>
        <DialogContent maxWidth="2xl" className="max-w-[100vw] overflow-x-hidden p-4 sm:p-6 md:max-h-[90vh] md:overflow-y-auto">
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
            <div className="max-h-36 space-y-1 overflow-y-auto rounded-lg border border-[var(--color-border)] bg-[var(--color-bg-subtle,#f8fafc)] p-2.5 sm:max-h-56">
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
              โรคที่เกี่ยวข้อง
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
              compactOnMobile
            />
          </div>

          <div>
            <label className="mb-2 block text-xs font-medium text-[var(--color-text-secondary)]">
              แผนภูมิที่แนะนำให้ประเมินต่อ
            </label>
            <RelatedSymptomsPicker
              items={diagrams.map((item) => ({
                id: item.diagram_id,
                name: item.diagram_name,
                nameEn: item.diagram_name_en,
              }))}
              selectedIds={nextDiagramIds}
              onChange={setNextDiagramIds}
              availableTitle="แผนภูมิทั้งหมด"
              selectedTitle="แผนภูมิที่แนะนำ"
              searchPlaceholder="ค้นหาแผนภูมิ..."
              itemNoun="แผนภูมิ"
              compactOnMobile
            />
          </div>

          <SimpleSelect
            label="ระดับความเร่งด่วน *"
            value={urgencyLevel}
            onChange={(v) => setUrgencyLevel(v as UrgencyLevel)}
            options={URGENCY_OPTIONS.map((o) => ({ value: o.value, label: o.label }))}
          />

          <div>
              <label className="mb-1 block text-xs font-medium text-[var(--color-text-secondary)]">
                กรอบเวลา
              </label>
              <input
                value={timeFrame}
                onChange={(e) => setTimeFrame(e.target.value)}
                className="w-full rounded-lg border border-[var(--color-border)] px-3 py-2 text-sm"
                placeholder="เช่น ภายใน 24 ชั่วโมง"
              />
          </div>

          <div>
            <label className="mb-1 block text-xs font-medium text-[var(--color-text-secondary)]">
              หมายเหตุ/คำแนะนำ
            </label>
            <Textarea
              value={note}
              onChange={(e) => setNote(e.target.value)}
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

          <DialogFooter className="flex items-center gap-2 sm:gap-3">
            {selectedRuleId !== "__new__" ? (
              <Button className="shrink-0 px-3 text-xs sm:px-5 sm:text-sm" variant="danger" onClick={() => setConfirmDeleteOpen(true)} disabled={saving}>
                ลบผลลัพธ์
              </Button>
            ) : (
              <span />
            )}
            <div className="ml-auto flex shrink-0 gap-2 sm:gap-3">
              <Button className="px-3 text-xs sm:px-5 sm:text-sm" variant="outline" onClick={() => onOpenChange(false)}>
                ยกเลิก
              </Button>
              <Button className="px-3 text-xs sm:px-5 sm:text-sm" onClick={handleSave} loading={saving} disabled={deleting}>
                บันทึกกฎ
              </Button>
            </div>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      <AlertDialog open={confirmDeleteOpen} onOpenChange={setConfirmDeleteOpen}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>ยืนยันการลบผลลัพธ์ปลายทาง</AlertDialogTitle>
            <AlertDialogDescription>
              ต้องการลบผลลัพธ์ปลายทางนี้หรือไม่? โรค คำแนะนำ และเงื่อนไขที่เกี่ยวข้องจะถูกลบ
              และไม่สามารถกู้คืนได้
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel disabled={deleting}>ยกเลิก</AlertDialogCancel>
            <AlertDialogAction onClick={handleDelete} loading={deleting}>
              ลบผลลัพธ์
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </>
  );
}
