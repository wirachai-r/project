import { useState, useEffect, useCallback, useRef, useMemo } from "react";
import { useNavigate, useParams, useBlocker } from "react-router-dom";
import { toast } from "sonner";
import {
  ArrowLeft,
  Save,
  ImagePlus,
  X,
  FileText,
  AlertTriangle,
  Stethoscope,
  ShieldCheck,
  Image as ImageIcon,
  FolderTree,
  Tags,
} from "lucide-react";
import { diseaseApi } from "@/lib/api/disease";
import { diseaseCategoryApi } from "@/lib/api/diseaseCategory";
import { symptomApi } from "@/lib/api/symptom";
import { uploadApi } from "@/lib/api/upload";
import { decodeId } from "@/lib/idCodec";
import type { DiseaseFormValues } from "@/types/disease";
import type { DiseaseCategory } from "@/types/diseaseCategory";
import type { Symptom } from "@/types/symptom";
import { RelatedSymptomsPicker } from "@/features/diagrams/components/RelatedSymptomsPicker";
import { Card } from "../../../components/ui/Card";
import { Button } from "../../../components/ui/Button";
import { Input } from "../../../components/ui/Input";
import { Label } from "../../../components/ui/Label";
import { SimpleSelect } from "../../../components/ui/SimpleSelect";
import { RichTextEditor } from "../../../components/ui/RichTextEditor";
import { ImageCropModal } from "../../../components/ui/ImageCropModal";
import { Spinner } from "../../../components/ui/Spinner";
import { DataLoadError } from "@/components/ui/DataLoadError";
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
import { ReferenceLinksInput } from "@/components/ui/ReferenceLinksInput";
import { toStoragePath } from "@/lib/storagePath";

const EMPTY_FORM: DiseaseFormValues = {
  disease_name: "",
  disease_name_en: "",
  description: "",
  cause: "",
  symptom_description: "",
  complications: "",
  diagnosis: "",
  medical_treatment: "",
  self_care: "",
  when_to_see_doctor: "",
  prevention: "",
  recommendations: "",
  references: [],
  disease_image: "", // เก็บ "path" เท่านั้น เช่น diseases/2026/07/xxx.webp
  status: "1",
  disease_category_id: "",
  symptom_ids: [],
};

const DISEASE_COVER_ASPECT = [{ label: "16:9", value: 16 / 9 }];

// ช่อง rich text ทั้งหมดที่อาจมีรูปฝังอยู่ใน HTML
const RICH_TEXT_FIELDS = [
  "description",
  "cause",
  "symptom_description",
  "complications",
  "diagnosis",
  "medical_treatment",
  "self_care",
  "when_to_see_doctor",
  "prevention",
  "recommendations",
] as const;

// ดึง url รูปทั้งหมดที่ฝังอยู่ใน HTML string (เฉพาะรูปที่มาจาก storage ของเราเอง)
// รูปใน rich text ยังคงเป็น full URL เสมอ เพราะฝังอยู่ใน HTML ต้อง render ได้ตรงๆ
function extractImageSrcs(html: string | undefined | null): string[] {
  if (!html) return [];
  const matches = [...html.matchAll(/<img[^>]+src="([^"]+)"/g)];
  return matches.map((m) => m[1]).filter((src) => src.includes("/storage/"));
}

// แปลงค่าที่อาจเป็น full URL หรือ path เปล่าๆ ให้เหลือแค่ "path" เสมอ
// รองรับทั้งข้อมูลเก่าที่ยังเป็น full URL และข้อมูลใหม่ที่เป็น path อยู่แล้ว
// รวม url/path รูปทั้งหมดในฟอร์ม (ทั้งรูปปก + รูปในทุกช่อง rich text)
// ใช้แค่สำหรับ diff หา orphaned file ไม่ได้ใช้ render จึงไม่สนว่าเป็น path หรือ url
function getAllImageUrls(f: DiseaseFormValues): Set<string> {
  const urls = new Set<string>();
  if (f.disease_image) urls.add(f.disease_image);
  RICH_TEXT_FIELDS.forEach((field) => {
    extractImageSrcs(f[field] as string).forEach((u) => urls.add(u));
  });
  return urls;
}

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

interface DraftShape {
  form: DiseaseFormValues;
  coverPreviewUrl: string;
}

export function DiseaseFormPage() {
  const navigate = useNavigate();
  const { diseaseId: encodedId } = useParams();
  const isEdit = !!encodedId;

  // decode เป็น real disease_id (char(10)) ที่ backend ใช้จริง
  // ถ้า decode ไม่ได้ (URL ถูกแก้/พิมพ์มั่ว) diseaseId จะเป็น null → invalidId = true
  const diseaseId = encodedId ? decodeId(encodedId) : null;
  const invalidId = isEdit && diseaseId === null;

  const draftKey = useMemo(
    () => (isEdit ? `disease_draft_${diseaseId}` : "disease_draft_new"),
    [isEdit, diseaseId],
  );

  const [form, setForm] = useState<DiseaseFormValues>(EMPTY_FORM);
  // full URL แยกไว้โชว์ <img src> เท่านั้น ไม่ใช่ค่าที่ส่งไป backend
  const [coverPreviewUrl, setCoverPreviewUrl] = useState<string>("");
  const [categories, setCategories] = useState<DiseaseCategory[]>([]);
  const [symptoms, setSymptoms] = useState<Symptom[]>([]);
  const [loading, setLoading] = useState(true);
  const [loadError, setLoadError] = useState<string | null>(null);
  const [saving, setSaving] = useState(false);
  const [uploadingCover, setUploadingCover] = useState(false);
  const [removingCover, setRemovingCover] = useState(false);

  const [pendingFile, setPendingFile] = useState<File | null>(null);
  const [cropModalOpen, setCropModalOpen] = useState(false);

  // originalFormRef คือ "สถานะล่าสุดที่ผูกกับ DB จริง" ใช้เทียบทั้ง isDirty
  // และใช้หา diff รูป (ทั้งรูปปก และรูปใน rich text) ว่ารูปไหนบันทึกจริง รูปไหนแค่ลอยอยู่
  const originalFormRef = useRef<DiseaseFormValues>(EMPTY_FORM);
  const hasRestoredDraftRef = useRef(false);
  const justSavedRef = useRef(false);

  // รวม url รูปทั้งหมดที่ "เคยปรากฏ" ใน form ระหว่าง session นี้ (สะสมไปเรื่อยๆ ไม่ลดลง)
  // ใช้ดักรูปที่อัปโหลดแล้วถูกลบออกจาก editor ก่อนกดบันทึก/ก่อนออกจากหน้า
  // ซึ่ง diff แบบ current-vs-saved เฉยๆ จะจับไม่ได้ เพราะรูปนั้นไม่อยู่ทั้งสองฝั่ง
  const everSeenUrlsRef = useRef<Set<string>>(new Set());

  // URL ผิด/decode ไม่ได้ → เด้งกลับหน้า list ทันที
  useEffect(() => {
    if (invalidId) {
      toast.error("ไม่พบข้อมูลโรคที่ต้องการแก้ไข");
      navigate("/diseases", { replace: true });
    }
  }, [invalidId, navigate]);

  useEffect(() => {
    diseaseCategoryApi
      .list({ per_page: 100 })
      .then((res) => setCategories(res.data));
  }, []);

  useEffect(() => {
    symptomApi
      .listAll({
        sort_by: "name",
        sort_direction: "asc",
      })
      .then(setSymptoms);
  }, []);

  useEffect(() => {
    if (invalidId) return;

    const draftRaw = sessionStorage.getItem(draftKey);
    if (draftRaw) {
      try {
        const draft = JSON.parse(draftRaw) as DraftShape;
        setForm({
          ...EMPTY_FORM,
          ...draft.form,
          symptom_ids: draft.form?.symptom_ids ?? [],
        });
        setCoverPreviewUrl(draft.coverPreviewUrl ?? "");
        hasRestoredDraftRef.current = true;
        setLoading(false);
      } catch {
        sessionStorage.removeItem(draftKey);
      }
    }

    if (!isEdit) {
      if (!draftRaw) originalFormRef.current = EMPTY_FORM;
      setLoading(false);
      return;
    }

    if (!diseaseId) return;

    diseaseApi.show(diseaseId).then((d) => {
      // d.disease_image จาก backend เป็น full URL เสมอ (Resource generate ให้แล้ว)
      // ต้องแปลงกลับเป็น path ก่อนเก็บใน form
      const fetched: DiseaseFormValues = {
        disease_name: d.disease_name,
        disease_name_en: d.disease_name_en ?? "",
        description: d.description ?? "",
        cause: d.cause ?? "",
        symptom_description: d.symptom_description ?? "",
        complications: d.complications ?? "",
        diagnosis: d.diagnosis ?? "",
        medical_treatment: d.medical_treatment ?? "",
        self_care: d.self_care ?? "",
        when_to_see_doctor: d.when_to_see_doctor ?? "",
        prevention: d.prevention ?? "",
        recommendations: d.recommendations ?? "",
        references: d.references ?? [],
        disease_image: toStoragePath(d.disease_image),
        status: d.status,
        disease_category_id: d.disease_category_id,
        symptom_ids: d.symptoms?.map((symptom) => symptom.symptom_id) ?? [],
      };

      originalFormRef.current = fetched;

      if (!hasRestoredDraftRef.current) {
        setForm(fetched);
        setCoverPreviewUrl(d.disease_image ?? "");
      }
      setLoading(false);
    }).catch((error) => {
      setLoadError(getErrorMessage(error));
      setLoading(false);
    });
  }, [diseaseId, isEdit, draftKey, invalidId]);

  useEffect(() => {
    if (loading) return;
    const draft: DraftShape = { form, coverPreviewUrl };
    sessionStorage.setItem(draftKey, JSON.stringify(draft));

    // สะสม path/url รูปทุกตัวที่เคยเห็นใน form ระหว่าง session นี้ (union สะสม ไม่มีวันลดลง)
    // ครอบคลุมทั้งรูปที่โหลดมาจาก DB ตอนแรก และรูปที่อัปโหลดใหม่ระหว่างแก้ไข
    const seen = everSeenUrlsRef.current;
    getAllImageUrls(form).forEach((u) => seen.add(u));
  }, [form, coverPreviewUrl, loading, draftKey]);

  const isDirty =
    !loading &&
    JSON.stringify(form) !== JSON.stringify(originalFormRef.current);

  const blocker = useBlocker(
    ({ currentLocation, nextLocation }) =>
      isDirty &&
      !justSavedRef.current &&
      currentLocation.pathname !== nextLocation.pathname,
  );

  // รับได้ทั้ง full URL (รูปใน rich text) และ path เปล่าๆ (รูปปก disease_image)
  const deleteImageFile = useCallback(async (value: string) => {
    const path = toStoragePath(value) || value;
    if (!path) return;
    try {
      await uploadApi.deleteImage(path);
    } catch {
      // ไม่บล็อก flow แม้ลบไฟล์จริงไม่สำเร็จ
    }
  }, []);

  // ตอนกดยืนยันออกจากหน้าโดยไม่บันทึก:
  // รูปไหนก็ตามที่เคยปรากฏใน session นี้ (ไม่ว่าจะยังอยู่ใน form ตอนนี้หรือถูกลบไปแล้วก็ตาม)
  // แต่ไม่เคยอยู่ในสถานะที่บันทึกจริงมาก่อน แปลว่าเป็นไฟล์ที่ลอยอยู่ ไม่มีที่ไหนอ้างอิงถึง ลบทิ้งได้เลย
  const handleConfirmLeave = () => {
    const savedUrls = getAllImageUrls(originalFormRef.current);
    const orphaned = [...everSeenUrlsRef.current].filter(
      (u) => !savedUrls.has(u),
    );
    orphaned.forEach((u) => deleteImageFile(u));

    sessionStorage.removeItem(draftKey);
    if (blocker.state === "blocked") {
      blocker.proceed();
    }
  };

  const handleCancelLeave = () => {
    if (blocker.state === "blocked") {
      blocker.reset();
    }
  };

  const handleCoverFileSelect = useCallback(
    (e: React.ChangeEvent<HTMLInputElement>) => {
      const file = e.target.files?.[0];
      if (!file) return;
      setPendingFile(file);
      setCropModalOpen(true);
      e.target.value = "";
    },
    [],
  );

  const handleCropConfirm = useCallback(
    async (blob: Blob) => {
      setCropModalOpen(false);
      setUploadingCover(true);
      const oldPath = form.disease_image;
      try {
        const file = new File([blob], `cover-${Date.now()}.webp`, {
          type: "image/webp",
        });
        // upload คืน 2 ค่า: url ไว้โชว์ preview, path ไว้เก็บลง form ที่จะส่งไป backend
        const { url, path } = await uploadApi.uploadImage(file, "diseases");
        setForm((f) => ({ ...f, disease_image: path }));
        setCoverPreviewUrl(url);

        // ลบรูปเก่าทันทีได้เฉพาะกรณีที่มันเป็นไฟล์ที่อัปโหลดไว้ลอยๆ ยังไม่บันทึก
        if (oldPath && oldPath !== originalFormRef.current.disease_image) {
          await deleteImageFile(oldPath);
        }
      } catch {
        toast.error("อัปโหลดรูปปกไม่สำเร็จ");
      } finally {
        setUploadingCover(false);
        setPendingFile(null);
      }
    },
    [form.disease_image, deleteImageFile],
  );

  const handleCropCancel = useCallback(() => {
    setCropModalOpen(false);
    setPendingFile(null);
  }, []);

  const handleRemoveCover = useCallback(async () => {
    const currentPath = form.disease_image;
    setRemovingCover(true);
    try {
      if (
        currentPath &&
        currentPath !== originalFormRef.current.disease_image
      ) {
        await deleteImageFile(currentPath);
      }
      setForm((f) => ({ ...f, disease_image: "" }));
      setCoverPreviewUrl("");
    } finally {
      setRemovingCover(false);
    }
  }, [form.disease_image, deleteImageFile]);

  const handleSave = async () => {
    if (!form.disease_name.trim()) return toast.error("กรุณากรอกชื่อโรค");
    if (!form.disease_category_id) return toast.error("กรุณาเลือกหมวดหมู่");

    setSaving(true);
    try {
      const payload = {
        ...form,
        disease_image: toStoragePath(form.disease_image),
        references: form.references.map((link) => link.trim()).filter(Boolean),
      };
      if (isEdit && diseaseId) {
        await diseaseApi.update(diseaseId, payload);
        toast.success("บันทึกข้อมูลโรคสำเร็จ");
      } else {
        await diseaseApi.create(payload);
        toast.success("เพิ่มโรคสำเร็จ");
      }

      // บันทึกสำเร็จแล้ว: รูปไหนก็ตามที่เคยปรากฏใน session นี้ (ไม่ว่าจะเคยบันทึกมาก่อนแล้วถูกลบออก
      // หรืออัปโหลดใหม่แล้วลบทิ้งเองก่อนบันทึกก็ตาม) แต่สุดท้ายไม่ได้อยู่ใน form ที่เพิ่งบันทึกไป
      // ค่อยลบไฟล์จริงออกจาก storage ตอนนี้ ปลอดภัยแล้วเพราะ DB อัปเดตแล้ว
      const newUrls = getAllImageUrls(form);
      const removed = [...everSeenUrlsRef.current].filter(
        (u) => !newUrls.has(u),
      );
      await Promise.all(removed.map((u) => deleteImageFile(u)));

      originalFormRef.current = form;
      everSeenUrlsRef.current = new Set(newUrls);
      sessionStorage.removeItem(draftKey);
      justSavedRef.current = true;
      navigate("/diseases");
    } catch (err) {
      toast.error(getErrorMessage(err));
    } finally {
      setSaving(false);
    }
  };

  const handleBack = () => navigate("/diseases");

  const categoryOptions = categories
    .map((c) => ({ label: c.category_name, value: c.disease_category_id }))
    .sort((left, right) =>
      left.label.localeCompare(right.label, "th", {
        sensitivity: "base",
        numeric: true,
      }),
    );

  useBreadcrumb(
    loading
      ? null // ยังโหลดไม่เสร็จ ไม่ override
      : isEdit
        ? ["แก้ไข", originalFormRef.current.disease_name]
        : ["เพิ่มโรคใหม่"],
  );

  if (invalidId) {
    return <Spinner fullscreen label="กำลังนำทางกลับ..." />;
  }

  if (loading) {
    return <Spinner fullscreen label="กำลังโหลดข้อมูลโรค..." />;
  }

  if (loadError) {
    return <DataLoadError description={loadError} onRetry={() => window.location.reload()} />;
  }

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
            {isEdit ? "แก้ไขข้อมูลโรค" : "เพิ่มโรคใหม่"}
          </h1>
          {/* <p className="mt-1 text-sm text-[var(--color-text-secondary)]">
            กรอกข้อมูลโรคให้ครบถ้วนเพื่อใช้ในระบบ
          </p> */}
        </div>

        <Button onClick={handleSave} loading={saving}>
          <Save className="h-4 w-4" />
          บันทึก
        </Button>
      </div>

      <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div className="order-2 space-y-6 lg:order-1 lg:col-span-2">
          <Card>
            <SectionHeading icon={FileText} title="ข้อมูลทั่วไป" />
            <div className="grid gap-4 md:grid-cols-2">
              <div>
                <Label htmlFor="disease_name">ชื่อโรค</Label>
                <Input
                  id="disease_name"
                  value={form.disease_name}
                  onChange={(e) =>
                    setForm({ ...form, disease_name: e.target.value })
                  }
                  placeholder="กรุณากรอกชื่อโรค"
                />
              </div>
              <div>
                <Label htmlFor="disease_name_en">ชื่อโรค (ภาษาอังกฤษ)</Label>
                <Input
                  id="disease_name_en"
                  value={form.disease_name_en}
                  onChange={(e) =>
                    setForm({ ...form, disease_name_en: e.target.value })
                  }
                  placeholder="กรุณากรอกชื่อโรคภาษาอังกฤษ"
                />
              </div>
            </div>

            <div>
              <Label>คำอธิบายโรคเบื้องต้น</Label>
              <RichTextEditor
                value={form.description}
                onChange={(html) => setForm({ ...form, description: html })}
                placeholder="กรุณากรอกคำอธิบายโรคเบื้องต้น"
              />
            </div>
          </Card>

          <Card>
            <SectionHeading
              icon={AlertTriangle}
              title="สาเหตุ"
              hint="ปัจจัยหรือกลไกที่ทำให้เกิดโรคนี้"
            />
            <RichTextEditor
              value={form.cause}
              onChange={(html) => setForm({ ...form, cause: html })}
              placeholder="อธิบายสาเหตุการเกิดโรค..."
            />
          </Card>

          <Card>
            <SectionHeading
              icon={Stethoscope}
              title="ลักษณะอาการ"
              hint="อาการที่พบได้บ่อยและสัญญาณเตือนที่ควรสังเกต"
            />
            <RichTextEditor
              value={form.symptom_description}
              onChange={(html) =>
                setForm({ ...form, symptom_description: html })
              }
              placeholder="อธิบายลักษณะอาการของโรค..."
            />
          </Card>

          <Card>
            <SectionHeading icon={AlertTriangle} title="ภาวะแทรกซ้อน" />
            <RichTextEditor
              value={form.complications}
              onChange={(html) => setForm({ ...form, complications: html })}
              placeholder="ภาวะแทรกซ้อนที่อาจเกิดขึ้น..."
            />
          </Card>

          <Card>
            <SectionHeading icon={Stethoscope} title="การวินิจฉัย" />
            <RichTextEditor
              value={form.diagnosis}
              onChange={(html) => setForm({ ...form, diagnosis: html })}
              placeholder="แนวทางและวิธีการวินิจฉัยโรค..."
            />
          </Card>

          <Card>
            <SectionHeading icon={Stethoscope} title="การรักษาโดยแพทย์" />
            <RichTextEditor
              value={form.medical_treatment}
              onChange={(html) => setForm({ ...form, medical_treatment: html })}
              placeholder="แนวทางการรักษาโดยแพทย์..."
            />
          </Card>

          <Card>
            <SectionHeading icon={ShieldCheck} title="การดูแลตนเอง" />
            <RichTextEditor
              value={form.self_care}
              onChange={(html) => setForm({ ...form, self_care: html })}
              placeholder="แนวทางการดูแลตนเอง..."
            />
          </Card>

          <Card>
            <SectionHeading
              icon={AlertTriangle}
              title="ควรกลับไปพบแพทย์เมื่อใด"
            />
            <RichTextEditor
              value={form.when_to_see_doctor}
              onChange={(html) =>
                setForm({ ...form, when_to_see_doctor: html })
              }
              placeholder="อาการหรือเงื่อนไขที่ควรกลับไปพบแพทย์..."
            />
          </Card>

          <Card>
            <SectionHeading
              icon={ShieldCheck}
              title="การป้องกัน"
              hint="แนวทางลดความเสี่ยงหรือดูแลตนเองเบื้องต้น"
            />
            <RichTextEditor
              value={form.prevention}
              onChange={(html) => setForm({ ...form, prevention: html })}
              placeholder="แนวทางการป้องกัน..."
            />
          </Card>

          <Card>
            <SectionHeading icon={FileText} title="ข้อแนะนำ" />
            <RichTextEditor
              value={form.recommendations}
              onChange={(html) => setForm({ ...form, recommendations: html })}
              placeholder="ข้อแนะนำเพิ่มเติมสำหรับผู้ป่วย..."
            />
          </Card>

          <Card>
            <SectionHeading
              icon={Tags}
              title="อาการที่เกี่ยวข้อง"
              hint="เลือกอาการจากคลังอาการเพื่อเชื่อมกับโรคนี้ ข้อมูลส่วนนี้ใช้สำหรับค้นหาและประมวลผล"
            />
            <RelatedSymptomsPicker
              symptoms={symptoms}
              selectedIds={form.symptom_ids}
              onChange={(ids) =>
                setForm((current) => ({ ...current, symptom_ids: ids }))
              }
            />
          </Card>

          <Card>
            <ReferenceLinksInput
              value={form.references}
              onChange={(references) => setForm({ ...form, references })}
            />
          </Card>
        </div>

        <div className="order-1 space-y-6 lg:order-2 lg:sticky lg:top-6 lg:col-span-1 lg:self-start">
          <Card>
            <SectionHeading
              icon={ImageIcon}
              title="รูปปกโรค"
              hint="แนะนำอัตราส่วน 16:9 เพื่อความคมชัดสม่ำเสมอทุกหน้าจอ"
            />
            <div>
              {coverPreviewUrl ? (
                <div className="group relative w-full">
                  <img
                    src={coverPreviewUrl}
                    alt="cover"
                    className="aspect-video w-full rounded-xl object-cover"
                  />
                  {uploadingCover || removingCover ? (
                    <div className="absolute inset-0 z-10 flex items-center justify-center rounded-xl bg-black/50">
                      <div className="flex flex-col items-center gap-2 text-white">
                        <Spinner size="sm" />
                        <span className="text-xs">
                          {removingCover ? "กำลังลบ..." : "กำลังอัปโหลด..."}
                        </span>
                      </div>
                    </div>
                  ) : (
                    <>
                      <button
                        type="button"
                        onClick={(e) => {
                          e.stopPropagation();
                          handleRemoveCover();
                        }}
                        className="absolute -right-2 -top-2 z-10 flex h-7 w-7 cursor-pointer items-center justify-center rounded-full bg-[var(--color-danger)] text-white shadow-sm transition-colors hover:bg-[var(--color-danger)]/80"
                      >
                        <X className="h-3.5 w-3.5" />
                      </button>
                      <label className="absolute inset-0 z-0 flex cursor-pointer items-center justify-center rounded-xl bg-black/0 text-transparent transition-colors hover:bg-black/40 hover:text-white">
                        <span className="text-xs">เปลี่ยนรูป</span>
                        <input
                          type="file"
                          accept="image/png,image/jpeg,image/webp"
                          className="hidden"
                          onChange={handleCoverFileSelect}
                        />
                      </label>
                    </>
                  )}
                </div>
              ) : (
                <label className="flex aspect-video w-full cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border-2 border-dashed border-[var(--color-border)] text-[var(--color-text-secondary)] transition-colors hover:border-[var(--color-primary)] hover:bg-[var(--color-primary-light)] hover:text-[var(--color-primary)]">
                  {uploadingCover ? (
                    <Spinner size="sm" label="กำลังอัปโหลด..." />
                  ) : (
                    <>
                      <ImagePlus className="h-6 w-6" />
                      <span className="text-xs font-medium">อัปโหลดรูปปก</span>
                      <span className="text-[11px] text-[var(--color-text-secondary)]/70">
                        PNG, JPG หรือ WEBP
                      </span>
                    </>
                  )}
                  <input
                    type="file"
                    accept="image/png,image/jpeg,image/webp"
                    className="hidden"
                    onChange={handleCoverFileSelect}
                    disabled={uploadingCover}
                  />
                </label>
              )}
            </div>
          </Card>

          <Card>
            <SectionHeading icon={FolderTree} title="การจัดหมวดหมู่" />
            <div className="space-y-4">
              <div>
                <SimpleSelect
                  value={form.disease_category_id}
                  onChange={(v) => setForm({ ...form, disease_category_id: v })}
                  options={categoryOptions}
                  label="หมวดหมู่"
                  placeholder="เลือกหมวดหมู่"
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
          </Card>
        </div>
      </div>

      <ImageCropModal
        open={cropModalOpen}
        file={pendingFile}
        aspects={DISEASE_COVER_ASPECT}
        onCancel={handleCropCancel}
        onConfirm={handleCropConfirm}
      />

      <AlertDialog open={blocker.state === "blocked"}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>ข้อมูลยังไม่ได้บันทึก</AlertDialogTitle>
            <AlertDialogDescription>
              คุณมีข้อมูลที่ยังไม่ได้บันทึก หากออกจากหน้านี้ตอนนี้
              ข้อมูลที่แก้ไขจะหายไป ต้องการออกจากหน้านี้หรือไม่?
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
