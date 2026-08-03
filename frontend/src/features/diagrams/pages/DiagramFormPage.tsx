import { useState, useEffect, useRef } from "react";
import { useNavigate, useParams, useBlocker } from "react-router-dom";
import { toast } from "sonner";
import { ArrowLeft, Save, FileText, Tags, ListTree } from "lucide-react";
import { diagramApi } from "@/lib/api/diagram";
import { symptomApi } from "@/lib/api/symptom";
import { decodeId } from "@/lib/idCodec";
import type { DiagramFormValues } from "@/types/diagram";
import { EMPTY_DIAGRAM_FORM } from "@/types/diagram";
import type { Symptom } from "@/types/symptom";
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
import { QuestionBoxSection } from "../components/QuestionBoxSection";
import { RelatedSymptomsPicker } from "../components/RelatedSymptomsPicker";

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

export function DiagramFormPage() {
  const navigate = useNavigate();
  const { diagramId: encodedId } = useParams();
  const isEdit = !!encodedId;

const diagramId = encodedId ? decodeId(encodedId, 5) : null;
  const invalidId = isEdit && diagramId === null;

  const [form, setForm] = useState<DiagramFormValues>(EMPTY_DIAGRAM_FORM);
  const [entryBoxId, setEntryBoxId] = useState<string | null>(null);
  const [symptoms, setSymptoms] = useState<Symptom[]>([]);
  const [loading, setLoading] = useState(isEdit);
  const [saving, setSaving] = useState(false);
  const [diagramName, setDiagramName] = useState("");

  const originalFormRef = useRef<DiagramFormValues>(EMPTY_DIAGRAM_FORM);
  const justSavedRef = useRef(false);

  useEffect(() => {
    if (invalidId) {
      toast.error("ไม่พบข้อมูลแผนภูมิที่ต้องการแก้ไข");
      navigate("/diagrams", { replace: true });
    }
  }, [invalidId, navigate]);

  useEffect(() => {
    symptomApi
      .list({ per_page: 200 })
      .then((res) => setSymptoms(res.data))
      .catch(() => toast.error("ไม่สามารถโหลดรายการอาการได้"));
  }, []);

  useEffect(() => {
    if (invalidId) return;

    if (!isEdit) {
      originalFormRef.current = EMPTY_DIAGRAM_FORM;
      return;
    }
    if (!diagramId) return;

    diagramApi.show(diagramId).then((d) => {
      const fetched: DiagramFormValues = {
        diagram_name: d.diagram_name,
        diagram_name_en: d.diagram_name_en ?? "",
        description: d.description ?? "",
        status: d.status,
        symptom_ids: d.symptoms?.map((s) => s.symptom_id) ?? [],
      };
      originalFormRef.current = fetched;
      setForm(fetched);
      setDiagramName(d.diagram_name);
      setEntryBoxId(d.entry_box_id);
      setLoading(false);
    });
  }, [diagramId, isEdit, invalidId]);

  const isDirty =
    !loading && JSON.stringify(form) !== JSON.stringify(originalFormRef.current);

  const blocker = useBlocker(
    ({ currentLocation, nextLocation }) =>
      isDirty &&
      !justSavedRef.current &&
      currentLocation.pathname !== nextLocation.pathname,
  );



  const handleSave = async () => {
    if (!form.diagram_name.trim()) return toast.error("กรุณากรอกชื่อแผนภูมิ");

    setSaving(true);
    try {
      if (isEdit && diagramId) {
        await diagramApi.update(diagramId, form);
        toast.success("บันทึกข้อมูลแผนภูมิสำเร็จ");
        originalFormRef.current = form;
        setDiagramName(form.diagram_name);
        justSavedRef.current = true;
        setSaving(false);
        // อยู่หน้าเดิมต่อ เพื่อให้จัดการกรอบคำถามได้ทันที (ไม่ navigate ออก)
        return;
      } else {
        const created = await diagramApi.create(form);
        toast.success("เพิ่มแผนภูมิสำเร็จ กรุณาเพิ่มกรอบคำถามต่อ");
        justSavedRef.current = true;
        navigate(`/diagrams/edit/${encodeURIComponent(created.diagram_id)}`, {
          replace: true,
        });
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

  const handleBack = () => navigate("/diagrams");

  useBreadcrumb(
    loading ? null : isEdit ? ["แก้ไข", diagramName] : ["เพิ่มแผนภูมิใหม่"],
  );

  if (invalidId) return <Spinner fullscreen label="กำลังนำทางกลับ..." />;
  if (loading) return <Spinner fullscreen label="กำลังโหลดข้อมูลแผนภูมิ..." />;

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
            {isEdit ? "แก้ไขแผนภูมิ" : "เพิ่มแผนภูมิใหม่"}
          </h1>
          <p className="mt-1 text-sm text-[var(--color-text-secondary)]">
            กรอกข้อมูลพื้นฐานของแผนภูมิ แล้วจัดการกรอบคำถามด้านล่าง
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
              <Label htmlFor="diagram_name">ชื่อแผนภูมิ (ภาษาไทย)</Label>
              <Input
                id="diagram_name"
                value={form.diagram_name}
                onChange={(e) =>
                  setForm({ ...form, diagram_name: e.target.value })
                }
                placeholder="เช่น ไข้"
              />
            </div>
            <div>
              <Label htmlFor="diagram_name_en">ชื่อแผนภูมิ (ภาษาอังกฤษ)</Label>
              <Input
                id="diagram_name_en"
                value={form.diagram_name_en}
                onChange={(e) =>
                  setForm({ ...form, diagram_name_en: e.target.value })
                }
                placeholder="e.g. Fever"
              />
            </div>
          </div>
          <div className="mt-4">
            <Label>คำอธิบาย</Label>
            <textarea
              value={form.description}
              onChange={(e) =>
                setForm({ ...form, description: e.target.value })
              }
              rows={3}
              className="w-full rounded-lg border border-[var(--color-border)] bg-[var(--color-surface)] px-3 py-2 text-sm text-[var(--color-text-primary)] outline-none focus:border-[var(--color-primary)]"
              placeholder="คำอธิบายเกี่ยวกับแผนภูมินี้ เช่น เกณฑ์อุณหภูมิที่ถือว่าเป็นไข้"
            />
          </div>
          <div className="mt-4 sm:w-52">
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
        </Card>

        <Card>
          <SectionHeading
            icon={Tags}
            title="อาการที่เกี่ยวข้อง"
            hint="เลือกอาการที่จะนำไปสู่แผนภูมินี้ (ผู้ใช้เลือกอาการ → ระบบพาไปยัง diagram) — ลากอาการจากซ้ายไปวางขวาเพื่อเพิ่ม"
          />
          <RelatedSymptomsPicker
            symptoms={symptoms}
            selectedIds={form.symptom_ids}
            onChange={(ids) => setForm((f) => ({ ...f, symptom_ids: ids }))}
          />
        </Card>

        <Card>
          <SectionHeading
            icon={ListTree}
            title="กรอบคำถาม (Question Flow)"
            hint={
              isEdit
                ? "จัดการกรอบคำถามและตัวเลือกคำตอบ — บันทึกทันทีต่อรายการ ไม่ต้องกดบันทึกรวม"
                : "บันทึกข้อมูลทั่วไปก่อน จึงจะสามารถเพิ่มกรอบคำถามได้"
            }
          />
          {isEdit && diagramId ? (
            <QuestionBoxSection
              diagramId={diagramId}
              entryBoxId={entryBoxId}
              onEntryBoxChange={setEntryBoxId}
            />
          ) : (
            <p className="rounded-lg border border-dashed border-[var(--color-border)] p-6 text-center text-sm text-[var(--color-text-secondary)]">
              กด "บันทึก" ด้านบนก่อน เพื่อเริ่มเพิ่มกรอบคำถาม
            </p>
          )}
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