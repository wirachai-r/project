import { useState, useEffect, useCallback, useRef, useMemo } from "react";
import { useNavigate, useParams, useBlocker } from "react-router-dom";
import { toast } from "sonner";
import {
  ArrowLeft,
  Save,
  ImagePlus,
  X,
  FileText,
  Image as ImageIcon,
  FolderTree,
} from "lucide-react";
import { articleApi } from "@/lib/api/article";
import { articleCategoryApi } from "@/lib/api/articleCategory";
import { uploadApi } from "@/lib/api/upload";
import { decodeId } from "@/lib/idCodec";
import type { ArticleFormValues } from "@/types/article";
import type { ArticleCategory } from "@/types/articleCategory";
import { Card } from "../../../components/ui/Card";
import { Button } from "../../../components/ui/Button";
import { Input } from "../../../components/ui/Input";
import { Label } from "../../../components/ui/Label";
import { SimpleSelect } from "../../../components/ui/SimpleSelect";
import { RichTextEditor } from "../../../components/ui/RichTextEditor";
import { ImageCropModal } from "../../../components/ui/ImageCropModal";
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

const EMPTY_FORM: ArticleFormValues = {
  title: "",
  title_en: "",
  content: "",
  content_en: "",
  thumbnail: "", // เก็บ "path" เท่านั้น เช่น articles/2026/07/xxx.webp
  status: "2",
  article_category_id: "",
};

const ARTICLE_COVER_ASPECT = [{ label: "16:9", value: 16 / 9 }];

// ช่อง rich text ทั้งหมดที่อาจมีรูปฝังอยู่ใน HTML
const RICH_TEXT_FIELDS = ["content"] as const;

function extractImageSrcs(html: string | undefined | null): string[] {
  if (!html) return [];
  const matches = [...html.matchAll(/<img[^>]+src="([^"]+)"/g)];
  return matches.map((m) => m[1]).filter((src) => src.includes("/storage/"));
}

function toStoragePath(value: string | null | undefined): string {
  if (!value) return "";
  return value.includes("/storage/") ? value.split("/storage/")[1] : value;
}

function getAllImageUrls(f: ArticleFormValues): Set<string> {
  const urls = new Set<string>();
  if (f.thumbnail) urls.add(f.thumbnail);
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
  form: ArticleFormValues;
  coverPreviewUrl: string;
}

export function ArticleFormPage() {
  const navigate = useNavigate();
  const { articleId: encodedId } = useParams();
  const isEdit = !!encodedId;

  const articleId = encodedId ? decodeId(encodedId) : null;
  const invalidId = isEdit && articleId === null;

  const draftKey = useMemo(
    () => (isEdit ? `article_draft_${articleId}` : "article_draft_new"),
    [isEdit, articleId],
  );

  const [form, setForm] = useState<ArticleFormValues>(EMPTY_FORM);
  const [coverPreviewUrl, setCoverPreviewUrl] = useState<string>("");
  const [categories, setCategories] = useState<ArticleCategory[]>([]);
  const [loading, setLoading] = useState(isEdit);
  const [saving, setSaving] = useState(false);
  const [uploadingCover, setUploadingCover] = useState(false);
  const [removingCover, setRemovingCover] = useState(false);

  const [pendingFile, setPendingFile] = useState<File | null>(null);
  const [cropModalOpen, setCropModalOpen] = useState(false);

  const originalFormRef = useRef<ArticleFormValues>(EMPTY_FORM);
  const hasRestoredDraftRef = useRef(false);
  const justSavedRef = useRef(false);
  const everSeenUrlsRef = useRef<Set<string>>(new Set());

  useEffect(() => {
    if (invalidId) {
      toast.error("ไม่พบบทความที่ต้องการแก้ไข");
      navigate("/articles", { replace: true });
    }
  }, [invalidId, navigate]);

  useEffect(() => {
    articleCategoryApi
      .list({ per_page: 100 })
      .then((res) => setCategories(res.data));
  }, []);

  useEffect(() => {
    if (invalidId) return;

    const draftRaw = sessionStorage.getItem(draftKey);
    if (draftRaw) {
      try {
        const draft = JSON.parse(draftRaw) as DraftShape;
        setForm(draft.form);
        setCoverPreviewUrl(draft.coverPreviewUrl ?? "");
        hasRestoredDraftRef.current = true;
        setLoading(false);
      } catch {
        sessionStorage.removeItem(draftKey);
      }
    }

    if (!isEdit) {
      if (!draftRaw) originalFormRef.current = EMPTY_FORM;
      return;
    }

    if (!articleId) return;

    articleApi.show(articleId).then((a) => {
      const fetched: ArticleFormValues = {
        title: a.title,
        title_en: a.title_en ?? "",
        content: a.content ?? "",
        content_en: a.content_en ?? "",
        thumbnail: toStoragePath(a.thumbnail),
        status: a.status,
        article_category_id: a.article_category_id,
      };

      originalFormRef.current = fetched;

      if (!hasRestoredDraftRef.current) {
        setForm(fetched);
        setCoverPreviewUrl(a.thumbnail ?? "");
      }
      setLoading(false);
    });
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [articleId, isEdit, draftKey, invalidId]);

  useEffect(() => {
    if (loading) return;
    const draft: DraftShape = { form, coverPreviewUrl };
    sessionStorage.setItem(draftKey, JSON.stringify(draft));

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

  const deleteImageFile = useCallback(async (value: string) => {
    const path = toStoragePath(value) || value;
    if (!path) return;
    try {
      await uploadApi.deleteImage(path);
    } catch {
      // ไม่บล็อก flow แม้ลบไฟล์จริงไม่สำเร็จ
    }
  }, []);

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
      const oldPath = form.thumbnail;
      try {
        const file = new File([blob], `thumbnail-${Date.now()}.webp`, {
          type: "image/webp",
        });
        const { url, path } = await uploadApi.uploadImage(file, "articles");
        setForm((f) => ({ ...f, thumbnail: path }));
        setCoverPreviewUrl(url);

        if (oldPath && oldPath !== originalFormRef.current.thumbnail) {
          await deleteImageFile(oldPath);
        }
      } catch {
        toast.error("อัปโหลดรูปปกไม่สำเร็จ");
      } finally {
        setUploadingCover(false);
        setPendingFile(null);
      }
    },
    [form.thumbnail, deleteImageFile],
  );

  const handleCropCancel = useCallback(() => {
    setCropModalOpen(false);
    setPendingFile(null);
  }, []);

  const handleRemoveCover = useCallback(async () => {
    const currentPath = form.thumbnail;
    setRemovingCover(true);
    try {
      if (currentPath && currentPath !== originalFormRef.current.thumbnail) {
        await deleteImageFile(currentPath);
      }
      setForm((f) => ({ ...f, thumbnail: "" }));
      setCoverPreviewUrl("");
    } finally {
      setRemovingCover(false);
    }
  }, [form.thumbnail, deleteImageFile]);

  const handleSave = async () => {
  if (!form.title.trim()) return toast.error("กรุณากรอกชื่อบทความ");
  if (!form.article_category_id) return toast.error("กรุณาเลือกหมวดหมู่");

  setSaving(true);
  try {
    if (isEdit && articleId) {
      await articleApi.update(articleId, form);
      toast.success("บันทึกบทความสำเร็จ");
    } else {
      await articleApi.create(form);
      toast.success("เพิ่มบทความสำเร็จ");
    }

    const newUrls = getAllImageUrls(form);
    const removed = [...everSeenUrlsRef.current].filter(
      (u) => !newUrls.has(u),
    );
    await Promise.all(removed.map((u) => deleteImageFile(u)));

    originalFormRef.current = form;
    everSeenUrlsRef.current = new Set(newUrls);
    sessionStorage.removeItem(draftKey);
    justSavedRef.current = true;
    navigate("/articles");
  } catch (err) {
    toast.error(getErrorMessage(err));
  } finally {
    setSaving(false);
  }
};

  const handleBack = () => navigate("/articles");

  const categoryOptions = categories.map((c) => ({
    label: c.category_name,
    value: c.article_category_id,
  }));

  useBreadcrumb(
    loading
      ? null
      : isEdit
        ? ["แก้ไข", originalFormRef.current.title]
        : ["เพิ่มบทความใหม่"],
  );

  if (invalidId) {
    return <Spinner fullscreen label="กำลังนำทางกลับ..." />;
  }

  if (loading) {
    return <Spinner fullscreen label="กำลังโหลดข้อมูลบทความ..." />;
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
            {isEdit ? "แก้ไขบทความ" : "เพิ่มบทความใหม่"}
          </h1>
          <p className="mt-1 text-sm text-[var(--color-text-secondary)]">
            กรอกข้อมูลบทความให้ครบถ้วนเพื่อใช้ในระบบ
          </p>
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
            <div className="grid gap-4 sm:grid-cols-2">
              <div>
                <Label htmlFor="title">ชื่อบทความ (ภาษาไทย)</Label>
                <Input
                  id="title"
                  value={form.title}
                  onChange={(e) => setForm({ ...form, title: e.target.value })}
                  placeholder="กรุณากรอกชื่อบทความ (ภาษาไทย)"
                />
              </div>
              <div>
                <Label htmlFor="title_en">ชื่อบทความ (ภาษาอังกฤษ)</Label>
                <Input
                  id="title_en"
                  value={form.title_en}
                  onChange={(e) =>
                    setForm({ ...form, title_en: e.target.value })
                  }
                  placeholder="กรุณากรอกชื่อบทความ (ภาษาอังกฤษ)"
                />
              </div>
            </div>
          </Card>

          <Card>
            <SectionHeading
              icon={FileText}
              title="เนื้อหาบทความ"
              hint="เนื้อหาฉบับเต็มของบทความ"
            />
            <div className="[&_.ProseMirror]:min-h-[230px] [&_[contenteditable]]:min-h-[230px]">
              <RichTextEditor
                value={form.content}
                onChange={(html) => setForm({ ...form, content: html })}
                placeholder="เขียนเนื้อหาบทความ..."
              />
            </div>
          </Card>
        </div>

        <div className="order-1 space-y-6 lg:order-2 lg:sticky lg:top-6 lg:col-span-1 lg:self-start">
          <Card>
            <SectionHeading
              icon={ImageIcon}
              title="รูปปกบทความ"
              hint="แนะนำอัตราส่วน 16:9 เพื่อความคมชัดสม่ำเสมอทุกหน้าจอ"
            />
            <div>
              {coverPreviewUrl ? (
                <div className="group relative w-full">
                  <img
                    src={coverPreviewUrl}
                    alt="thumbnail"
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
                <Label>หมวดหมู่</Label>
                <SimpleSelect
                  value={form.article_category_id}
                  onChange={(v) => setForm({ ...form, article_category_id: v })}
                  options={categoryOptions}
                  placeholder="เลือกหมวดหมู่"
                />
              </div>
              <div>
                <Label>สถานะ</Label>
                <SimpleSelect
                  value={form.status}
                  onChange={(v) =>
                    setForm({ ...form, status: v as "1" | "2" | "3" })
                  }
                  options={[
                    { label: "เผยแพร่", value: "1" },
                    { label: "ฉบับร่าง", value: "2" },
                    { label: "เก็บถาวร", value: "3" },
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
        aspects={ARTICLE_COVER_ASPECT}
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
