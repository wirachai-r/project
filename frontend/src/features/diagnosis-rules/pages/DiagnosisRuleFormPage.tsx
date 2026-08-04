import { useState, useEffect, useRef } from "react";
import { useNavigate, useParams, useBlocker } from "react-router-dom";
import { toast } from "sonner";
import { ArrowLeft, Save, FileText, GitBranch, Stethoscope } from "lucide-react";
import { diagnosisRuleApi } from "@/lib/api/diagnosisRule";
import { diagramApi } from "@/lib/api/diagram";
import { diseaseApi } from "@/lib/api/disease";
import { decodeId } from "@/lib/idCodec";
import type { DiagnosisRuleFormValues, UrgencyLevel, ConditionDraft } from "../types";
import { EMPTY_DIAGNOSIS_RULE_FORM, URGENCY_OPTIONS, makeConditionKey } from "../types";
import { Card } from "../../../components/ui/Card";
import { Button } from "../../../components/ui/Button";
import { Input } from "../../../components/ui/Input";
import { Label } from "../../../components/ui/Label";
import { SimpleSelect } from "../../../components/ui/SimpleSelect";
import { Spinner } from "../../../components/ui/Spinner";
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
import { useBreadcrumb } from "../../../hooks/useBreadcrumb";
import { getErrorMessage } from "@/lib/getErrorMessage";
import { RuleConditionGraph } from "../components/RuleConditionGraph";
import { DiseaseMultiPicker } from "../components/DiseaseMultiPicker";

function SectionHeading({
  icon: Icon,
  title,
  hint,
}: {
  icon: React.ComponentType<{ className?: string }>;
  title: string;
  hint?: string;
}) {
  return (
    <div className="flex items-center gap-2.5 border-b border-[var(--color-border)] pb-3">
      <span className="flex h-7 w-7 shrink-0 items-center justify-center rounded-md bg-[var(--color-primary-light)]">
        <Icon className="h-4 w-4 text-[var(--color-primary)]" />
      </span>
      <div>
        <h3 className="font-semibold leading-none text-[var(--color-text-primary)]">
          {title}
        </h3>
        {hint && (
          <p className="mt-1.5 text-xs text-[var(--color-text-secondary)]">
            {hint}
          </p>
        )}
      </div>
    </div>
  );
}

interface DiagramOption {
  diagram_id: string;
  diagram_name: string;
  entry_box_id?: string | null;
}

interface DiseaseOption {
  disease_id: string;
  disease_name: string;
}

// state ในหน้านี้ = DiagnosisRuleFormValues แต่ conditions เป็น ConditionDraft (มี key ไว้ผูกกับกราฟ)
interface RuleFormState extends Omit<DiagnosisRuleFormValues, "conditions"> {
  conditions: ConditionDraft[];
}

const EMPTY_FORM: RuleFormState = { ...EMPTY_DIAGNOSIS_RULE_FORM, conditions: [] };

export function DiagnosisRuleFormPage() {
  const navigate = useNavigate();
  const { ruleId: encodedId } = useParams();
  const isEdit = !!encodedId;

  const ruleId = encodedId ? decodeId(encodedId, 10) : null;
  const invalidId = isEdit && ruleId === null;

  const [form, setForm] = useState<RuleFormState>(EMPTY_FORM);
  const [diagrams, setDiagrams] = useState<DiagramOption[]>([]);
  const [diseases, setDiseases] = useState<DiseaseOption[]>([]);
  const [loading, setLoading] = useState(isEdit);
  const [saving, setSaving] = useState(false);

  const originalFormRef = useRef<RuleFormState>(EMPTY_FORM);
  const justSavedRef = useRef(false);

  useEffect(() => {
    if (invalidId) {
      toast.error("ไม่พบข้อมูลกฎการวินิจฉัยที่ต้องการแก้ไข");
      navigate("/diagnosis-rules", { replace: true });
    }
  }, [invalidId, navigate]);

  useEffect(() => {
    diagramApi
      .list({ per_page: 200 })
      .then((res) => setDiagrams(res.data))
      .catch(() => toast.error("ไม่สามารถโหลดรายการแผนภูมิได้"));
    diseaseApi
      .list({ per_page: 200 })
      .then((res) => setDiseases(res.data))
      .catch(() => toast.error("ไม่สามารถโหลดรายการโรคได้"));
  }, []);

  useEffect(() => {
    if (invalidId) return;

    if (!isEdit) {
      originalFormRef.current = EMPTY_FORM;
      return;
    }
    if (!ruleId) return;

    diagnosisRuleApi.show(ruleId).then((r) => {
      const fetched: RuleFormState = {
        medical_reference: r.medical_reference ?? "",
        urgency_level: r.urgency_level,
        time_frame: r.time_frame ?? "",
        time_frame_en: r.time_frame_en ?? "",
        note: r.note ?? "",
        note_en: r.note_en ?? "",
        status: r.status,
        diagram_id: r.diagram_id,
        disease_ids: (r.diseases ?? []).map((d) => d.disease_id),
        conditions: (r.conditions ?? []).map((c) => ({
          key: makeConditionKey(c.box_id, c.choice_id),
          box_id: c.box_id,
          choice_id: c.choice_id,
          logic_operator: c.logic_operator,
          status: c.status,
        })),
      };
      originalFormRef.current = fetched;
      setForm(fetched);
      setLoading(false);
    });
  }, [ruleId, isEdit, invalidId]);

  const isDirty =
    !loading && JSON.stringify(form) !== JSON.stringify(originalFormRef.current);

  const blocker = useBlocker(
    ({ currentLocation, nextLocation }) =>
      isDirty &&
      !justSavedRef.current &&
      currentLocation.pathname !== nextLocation.pathname,
  );

  const selectedDiagram = diagrams.find((d) => d.diagram_id === form.diagram_id) ?? null;

  const ruleTitle =
    diseases
      .filter((d) => form.disease_ids.includes(d.disease_id))
      .map((d) => d.disease_name)
      .join(", ") || (isEdit ? "แก้ไขกฎการวินิจฉัย" : "");

  const handleDiagramChange = (diagramId: string) => {
    setForm((f) => ({
      ...f,
      diagram_id: diagramId,
      // เปลี่ยนแผนภูมิแล้ว เงื่อนไขเดิมอ้างอิงกรอบคำถามของแผนภูมิเก่า ต้องเริ่มเลือกใหม่
      conditions: diagramId === f.diagram_id ? f.conditions : [],
    }));
  };

  const handleSave = async () => {
    if (!form.diagram_id) return toast.error("กรุณาเลือกแผนภูมิ");
    if (form.disease_ids.length === 0) return toast.error("กรุณาเลือกโรคอย่างน้อย 1 รายการ");
    if (form.conditions.length === 0)
      return toast.error("กรุณาเลือกอย่างน้อย 1 เงื่อนไขจากกราฟ");

    setSaving(true);
    try {
      const payload: DiagnosisRuleFormValues = {
        medical_reference: form.medical_reference,
        urgency_level: form.urgency_level,
        time_frame: form.time_frame,
        time_frame_en: form.time_frame_en,
        note: form.note,
        note_en: form.note_en,
        status: form.status,
        diagram_id: form.diagram_id,
        disease_ids: form.disease_ids,
        conditions: form.conditions.map(({ box_id, choice_id, logic_operator, status }) => ({
          box_id,
          choice_id,
          logic_operator,
          status,
        })),
      };

      if (isEdit && ruleId) {
        await diagnosisRuleApi.update(ruleId, payload);
        toast.success("บันทึกกฎการวินิจฉัยสำเร็จ");
        originalFormRef.current = form;
        justSavedRef.current = true;
        navigate("/diagnosis-rules");
        return;
      } else {
        await diagnosisRuleApi.create(payload);
        toast.success("เพิ่มกฎการวินิจฉัยสำเร็จ");
        justSavedRef.current = true;
        navigate("/diagnosis-rules");
        return;
      }
    } catch (err) {
      toast.error(getErrorMessage(err));
    } finally {
      setSaving(false);
    }
  };

  const handleConfirmLeave = () => {
    if (blocker.state === "blocked") blocker.proceed();
  };
  const handleCancelLeave = () => {
    if (blocker.state === "blocked") blocker.reset();
  };

  const handleBack = () => navigate("/diagnosis-rules");

  useBreadcrumb(
    loading ? null : isEdit ? ["แก้ไข", ruleTitle] : ["เพิ่มกฎการวินิจฉัยใหม่"],
  );

  if (invalidId) return <Spinner fullscreen label="กำลังนำทางกลับ..." />;
  if (loading) return <Spinner fullscreen label="กำลังโหลดข้อมูลกฎการวินิจฉัย..." />;

  return (
    <div>
      <div className="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <button
            onClick={handleBack}
            className="mb-1 flex items-center gap-1.5 text-sm text-[var(--color-text-secondary)] hover:text-[var(--color-text-primary)]"
          >
            <ArrowLeft className="h-4 w-4" />
            กลับ
          </button>
          <h1 className="text-xl font-semibold text-[var(--color-text-primary)]">
            {isEdit ? "แก้ไขกฎการวินิจฉัย" : "เพิ่มกฎการวินิจฉัยใหม่"}
          </h1>
          <p className="mt-1 text-sm text-[var(--color-text-secondary)]">
            เลือกแผนภูมิ แล้วคลิกเส้นทางบนกราฟเพื่อกำหนดเงื่อนไขของกฎ
          </p>
        </div>

        <Button onClick={handleSave} loading={saving}>
          <Save className="h-4 w-4" />
          บันทึก
        </Button>
      </div>

      <div className="space-y-6">
        <Card>
          <SectionHeading icon={FileText} title="ข้อมูลทั่วไป" />

          <div className="grid gap-4 sm:grid-cols-2">
            <div>
              <Label>แผนภูมิ (diagram)</Label>
              <SimpleSelect
                value={form.diagram_id}
                onChange={handleDiagramChange}
                placeholder="เลือกแผนภูมิ"
                options={diagrams.map((d) => ({
                  label: d.diagram_name,
                  value: d.diagram_id,
                }))}
              />
            </div>
            <div>
              <Label>ระดับความเร่งด่วน</Label>
              <SimpleSelect
                value={form.urgency_level}
                onChange={(v) =>
                  setForm({ ...form, urgency_level: v as UrgencyLevel })
                }
                options={URGENCY_OPTIONS.map((u) => ({
                  label: u.label,
                  value: u.value,
                }))}
              />
            </div>
          </div>

          <div className="mt-4 grid gap-4 sm:grid-cols-2">
            <div>
              <Label htmlFor="time_frame">กรอบเวลา (ภาษาไทย)</Label>
              <Input
                id="time_frame"
                value={form.time_frame}
                onChange={(e) => setForm({ ...form, time_frame: e.target.value })}
                placeholder="เช่น ภายใน 24 ชั่วโมง"
              />
            </div>
          </div>

          <div className="mt-4">
            <Label>สถานะ</Label>
            <div className="sm:w-52">
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

          <div className="mt-4 grid gap-4 sm:grid-cols-2">
            <div>
              <Label htmlFor="note">คำแนะนำ (ภาษาไทย)</Label>
              <textarea
                id="note"
                value={form.note}
                onChange={(e) => setForm({ ...form, note: e.target.value })}
                rows={3}
                className="w-full rounded-lg border border-[var(--color-border)] bg-[var(--color-surface)] px-3 py-2 text-sm text-[var(--color-text-primary)] outline-none focus:border-[var(--color-primary)]"
                placeholder="คำแนะนำที่จะแสดงให้ผู้ใช้เมื่อผลประเมินตรงกับกฎนี้"
              />
            </div>
          </div>

          <div className="mt-4">
            <Label>อ้างอิงทางการแพทย์ (medical reference)</Label>
            <Input
              value={form.medical_reference}
              onChange={(e) =>
                setForm({ ...form, medical_reference: e.target.value })
              }
              placeholder="เช่น ตำราการตรวจรักษาโรคทั่วไป หน้า 8 แผนภูมิที่ 1"
            />
          </div>
        </Card>

        <Card>
          <SectionHeading
            icon={Stethoscope}
            title="โรคที่จะวินิจฉัย"
            hint="เลือกได้หลายโรค ลำดับที่เลือกไว้จะใช้เป็นลำดับการแสดงผล"
          />
          <DiseaseMultiPicker
            diseases={diseases}
            selectedIds={form.disease_ids}
            onChange={(ids) => setForm((f) => ({ ...f, disease_ids: ids }))}
          />
        </Card>

        <Card>
          <SectionHeading
            icon={GitBranch}
            title="เงื่อนไขของกฎ (Rule Conditions)"
            hint="คลิกป้ายตัวเลือกบนกราฟเพื่อเพิ่ม/เอาออกจากเงื่อนไข แล้วจัดลำดับและตั้งค่า และ/หรือ ทางด้านขวา"
          />
          <RuleConditionGraph
            diagramId={form.diagram_id || null}
            entryBoxId={selectedDiagram?.entry_box_id ?? null}
            conditions={form.conditions}
            onChange={(conditions) => setForm((f) => ({ ...f, conditions }))}
          />
        </Card>
      </div>

      <AlertDialog open={blocker.state === "blocked"}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>ข้อมูลยังไม่ได้บันทึก</AlertDialogTitle>
            <AlertDialogDescription>
              คุณมีข้อมูลที่ยังไม่ได้บันทึก หากออกจากหน้านี้ตอนนี้ ข้อมูลที่แก้ไขจะหายไป
              ต้องการออกจากหน้านี้หรือไม่?
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel onClick={handleCancelLeave}>
              ยกเลิก
            </AlertDialogCancel>
            <AlertDialogAction onClick={handleConfirmLeave}>
              ออกจากหน้านี้
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </div>
  );
}
