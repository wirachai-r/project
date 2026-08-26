import { useEffect, useMemo, useState } from "react";
import { ImagePlus, Upload } from "lucide-react";
import { toast } from "sonner";
import { Button } from "@/components/ui/Button";
import { Checkbox } from "@/components/ui/Checkbox";
import {
  Dialog,
  DialogContent,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/Dialog";
import { Input } from "@/components/ui/Input";
import { Label } from "@/components/ui/Label";
import { SimpleSelect } from "@/components/ui/SimpleSelect";
import { Textarea } from "@/components/ui/Textarea";
import { ImageCropModal } from "@/components/ui/ImageCropModal";
import type { BodyAreaGroup, BodyAreaGroupForm } from "@/types/bodyAreaGroup";
import type { Symptom } from "@/types/symptom";

interface Props {
  open: boolean;
  editing: BodyAreaGroup | null;
  form: BodyAreaGroupForm;
  symptoms: Symptom[];
  saving: boolean;
  onOpenChange: (open: boolean) => void;
  onFormChange: (form: BodyAreaGroupForm) => void;
  onSave: () => void;
}

export function BodyAreaGroupFormDialog({
  open,
  editing,
  form,
  symptoms,
  saving,
  onOpenChange,
  onFormChange,
  onSave,
}: Props) {
  const [search, setSearch] = useState("");
  const [preview, setPreview] = useState<string | null>(
    editing?.image_url ?? null,
  );
  const [pendingFile, setPendingFile] = useState<File | null>(null);

  useEffect(
    () => () => {
      if (preview?.startsWith("blob:")) URL.revokeObjectURL(preview);
    },
    [preview],
  );

  const visibleSymptoms = useMemo(() => {
    const term = search.trim().toLocaleLowerCase("th");
    return term
      ? symptoms.filter((symptom) =>
          `${symptom.symptom_name} ${symptom.symptom_name_en ?? ""}`
            .toLocaleLowerCase("th")
            .includes(term),
        )
      : symptoms;
  }, [search, symptoms]);

  const toggleSymptom = (id: string) =>
    onFormChange({
      ...form,
      symptom_ids: form.symptom_ids.includes(id)
        ? form.symptom_ids.filter((item) => item !== id)
        : [...form.symptom_ids, id],
    });

  const selectImage = (file: File | null) => {
    if (!file) return;
    if (!["image/png", "image/jpeg", "image/webp"].includes(file.type)) {
      toast.error("รองรับเฉพาะไฟล์ PNG, JPG และ WEBP");
      return;
    }
    if (file.size > 5 * 1024 * 1024) {
      toast.error("ขนาดรูปภาพต้องไม่เกิน 5 MB");
      return;
    }
    setPendingFile(file);
  };

  return (
    <Dialog
      open={open}
      onOpenChange={(nextOpen) => !saving && onOpenChange(nextOpen)}
    >
      <DialogContent
        maxWidth="2xl"
        className="flex flex-col md:h-[90vh] md:max-h-[900px] md:overflow-hidden"
      >
        <DialogHeader>
          <DialogTitle>
            {editing ? "แก้ไขกลุ่มบริเวณ" : "เพิ่มกลุ่มบริเวณ"}
          </DialogTitle>
        </DialogHeader>
        <div className="grid min-h-0 flex-1 gap-5 overflow-y-auto pr-1 md:grid-cols-2 md:items-stretch">
          <div className="space-y-4 rounded-2xl border border-[var(--color-border)] bg-white p-5">
            <div>
              <Label>ชื่อกลุ่มบริเวณ</Label>
              <Input
                value={form.name}
                onChange={(event) =>
                  onFormChange({ ...form, name: event.target.value })
                }
              />
            </div>
            <div>
              <Label>คำอธิบายสั้น</Label>
              <Textarea
                rows={3}
                value={form.description}
                onChange={(event) =>
                  onFormChange({ ...form, description: event.target.value })
                }
              />
            </div>
            <div>
              <Label>สถานะ</Label>
              <SimpleSelect
                value={form.status}
                onChange={(status) =>
                  onFormChange({ ...form, status: status as "1" | "2" })
                }
                options={[
                  { label: "ใช้งาน", value: "1" },
                  { label: "ปิดใช้งาน", value: "2" },
                ]}
              />
            </div>
            <div>
              
              <label
                className="group relative mt-5 flex aspect-[16/9] cursor-pointer items-center justify-center overflow-hidden rounded-xl border-2 border-dashed border-[var(--color-border)] bg-[var(--color-surface)]/40 transition-colors hover:border-[var(--color-primary)] hover:bg-[var(--color-primary-light)]/30"
                onDragOver={(event) => event.preventDefault()}
                onDrop={(event) => {
                  event.preventDefault();
                  selectImage(event.dataTransfer.files[0] ?? null);
                }}
              >
                {preview ? (
                  <>
                    <img src={preview} alt="ตัวอย่างรูปกลุ่มบริเวณ" className="absolute inset-0 h-full w-full object-cover" />
                    <span className="absolute inset-0 flex items-center justify-center bg-black/0 transition-colors group-hover:bg-black/45">
                      <span className="flex translate-y-2 items-center gap-2 rounded-lg bg-white/95 px-4 py-2 text-sm font-medium text-[var(--color-text-primary)] opacity-0 shadow-sm transition-all group-hover:translate-y-0 group-hover:opacity-100">
                        <ImagePlus className="h-4 w-4 text-[var(--color-primary)]" />เปลี่ยนรูปภาพ
                      </span>
                    </span>
                  </>
                ) : (
                  <span className="flex flex-col items-center px-4 text-center">
                    <Upload className="h-8 w-8 text-[var(--color-text-secondary)] transition-colors group-hover:text-[var(--color-primary)]" />
                    <span className="mt-3 text-sm font-medium text-[var(--color-text-primary)]">อัปโหลดรูปภาพ</span>
                    <span className="mt-1 text-xs text-[var(--color-text-secondary)]">คลิกหรือลากไฟล์มาวาง · PNG, JPG หรือ WEBP · ไม่เกิน 5 MB</span>
                  </span>
                )}
                <input
                  className="hidden"
                  type="file"
                  accept="image/png,image/jpeg,image/webp"
                  onChange={(event) => {
                    const file = event.target.files?.[0] ?? null;
                    event.target.value = "";
                    selectImage(file);
                  }}
                />
              </label>
              {form.image && <p className="mt-2 truncate text-xs text-[var(--color-text-secondary)]">ไฟล์ใหม่: {form.image.name}</p>}
            </div>
          </div>
          <div className="flex min-h-[520px] flex-col rounded-2xl border border-[var(--color-border)] bg-white p-5 md:min-h-0">
            <div className="flex items-center justify-between gap-3">
              <Label>อาการในกลุ่ม ({form.symptom_ids.length})</Label>
              {form.symptom_ids.length > 0 && (
                <button
                  type="button"
                  onClick={() => onFormChange({ ...form, symptom_ids: [] })}
                  className="text-xs font-medium text-red-600 hover:underline"
                >
                  ล้างที่เลือก
                </button>
              )}
            </div>
            <Input
              className="mt-1"
              placeholder="ค้นหาอาการ"
              value={search}
              onChange={(event) => setSearch(event.target.value)}
            />
            <div className="mt-2 flex items-center justify-between text-xs text-[var(--color-text-secondary)]">
              <span>พบ {visibleSymptoms.length} อาการ</span>
              {visibleSymptoms.length > 0 && (
                <button
                  type="button"
                  onClick={() =>
                    onFormChange({
                      ...form,
                      symptom_ids: Array.from(
                        new Set([
                          ...form.symptom_ids,
                          ...visibleSymptoms.map(
                            (symptom) => symptom.symptom_id,
                          ),
                        ]),
                      ),
                    })
                  }
                  className="font-medium text-[var(--color-primary)] hover:underline"
                >
                  เลือกผลลัพธ์ทั้งหมด
                </button>
              )}
            </div>
            <div className="mt-2 min-h-60 flex-1 space-y-1 overflow-y-auto rounded-lg border p-2">
              {visibleSymptoms.length === 0 ? (
                <p className="p-6 text-center text-sm text-[var(--color-text-secondary)]">
                  ไม่พบอาการที่ค้นหา
                </p>
              ) : (
                visibleSymptoms.map((symptom) => (
                  <label
                    key={symptom.symptom_id}
                    className="flex cursor-pointer items-center gap-3 rounded-md px-3 py-2 hover:bg-[var(--color-surface)]"
                  >
                    <Checkbox
                      checked={form.symptom_ids.includes(symptom.symptom_id)}
                      onCheckedChange={() => toggleSymptom(symptom.symptom_id)}
                    />
                    <span className="min-w-0 text-sm">
                      <span className="block truncate">
                        {symptom.symptom_name}
                      </span>
                      {symptom.symptom_name_en && (
                        <span className="block truncate text-xs text-[var(--color-text-secondary)]">
                          {symptom.symptom_name_en}
                        </span>
                      )}
                    </span>
                  </label>
                ))
              )}
            </div>
          </div>
        </div>
        <DialogFooter className="shrink-0 border-t border-[var(--color-border)] pt-4">
          <Button
            variant="outline"
            disabled={saving}
            onClick={() => onOpenChange(false)}
          >
            ยกเลิก
          </Button>
          <Button loading={saving} onClick={onSave}>
            บันทึก
          </Button>
        </DialogFooter>
      </DialogContent>
      <ImageCropModal
        open={pendingFile !== null}
        file={pendingFile}
        aspects={[{ label: "16:9", value: 16 / 9 }]}
        outputWidth={1200}
        outputType="image/png"
        onCancel={() => setPendingFile(null)}
        onConfirm={(blob) => {
          const image = new File([blob], `body-area-${Date.now()}.png`, {
            type: "image/png",
          });
          onFormChange({ ...form, image });
          setPreview(URL.createObjectURL(blob));
          setPendingFile(null);
        }}
      />
    </Dialog>
  );
}
