import { useEffect, useMemo, useState } from "react";
import { ImagePlus, Plus, Trash2, Upload } from "lucide-react";
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
import { fuzzyIncludes } from "@/lib/fuzzySearch";

interface Props {
  open: boolean;
  editing: BodyAreaGroup | null;
  form: BodyAreaGroupForm;
  symptoms: Symptom[];
  saving: boolean;
  saveError?: string | null;
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
  saveError,
  onOpenChange,
  onFormChange,
  onSave,
}: Props) {
  const [search, setSearch] = useState("");
  const [preview, setPreview] = useState<string | null>(
    editing?.image_url ?? null,
  );
  const [pendingFile, setPendingFile] = useState<File | null>(null);
  const [subgroupSearches, setSubgroupSearches] = useState<Record<number, string>>({});

  useEffect(
    () => () => {
      if (preview?.startsWith("blob:")) URL.revokeObjectURL(preview);
    },
    [preview],
  );

  const visibleSymptoms = useMemo(() => {
    const term = search.trim();
    const filtered = term
      ? symptoms.filter((symptom) =>
          fuzzyIncludes(
            `${symptom.symptom_name} ${symptom.symptom_name_en ?? ""}`,
            term,
          ),
        )
      : symptoms;

    return [...filtered].sort((left, right) =>
      left.symptom_name.localeCompare(right.symptom_name, "th", {
        sensitivity: "base",
        numeric: true,
      }),
    );
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
        {saveError && (
          <div role="alert" className="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {saveError}
          </div>
        )}
        <div className="grid min-h-0 flex-1 gap-x-6 gap-y-10 overflow-y-auto pr-1 md:grid-cols-2 md:auto-rows-fr md:items-stretch md:gap-y-6">
          <div className="flex h-full min-h-[520px] flex-col gap-4 md:min-h-0">
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
            <div className="flex min-h-64 flex-1 flex-col">
              <label
                className="group relative mt-2 flex min-h-64 flex-1 cursor-pointer items-center justify-center overflow-hidden rounded-xl border-2 border-dashed border-[var(--color-border)] bg-[var(--color-surface)]/40 transition-colors hover:border-[var(--color-primary)] hover:bg-[var(--color-primary-light)]/30"
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
                    <span className="mt-3 text-sm font-medium text-[var(--color-text-primary)]">อัปโหลดรูปภาพ (ไม่บังคับ)</span>
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
            </div>
          </div>
          <div className="flex h-full min-h-[520px] flex-col md:min-h-0">
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
            <div className="mt-2 min-h-60 flex-1 space-y-1 overflow-y-auto rounded-lg border border-[var(--color-border)] p-2">
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
        <section className="hidden">
          <div>
            <Label>บริเวณย่อย ({form.subgroups.length})</Label>
            <p className="mt-1 text-xs text-[var(--color-text-secondary)]">เพิ่มรูป เลือกอาการ และจัดลำดับในหน้าต่างแยก</p>
          </div>
          <Button type="button" variant="outline">
            จัดการบริเวณย่อย
          </Button>
        </section>
        <section className="hidden">
          <div className="flex items-center justify-between gap-3">
            <div>
              <Label>บริเวณย่อย ({form.subgroups.length})</Label>
              <p className="mt-1 text-xs text-[var(--color-text-secondary)]">
                ถ้าไม่เพิ่ม ระบบจะเปิดรายการอาการของกลุ่มทันที
              </p>
            </div>
            <Button
              type="button"
              variant="outline"
              onClick={() => onFormChange({
                ...form,
                subgroups: [...form.subgroups, {
                  name: "",
                  name_en: "",
                  description: "",
                  status: "1",
                  symptom_ids: [],
                }],
              })}
            >
              <Plus className="h-4 w-4" /> เพิ่มบริเวณย่อย
            </Button>
          </div>
          {form.subgroups.length > 0 && (
            <div className="mt-4 grid gap-4">
              {form.subgroups.map((subgroup, index) => (
                <div key={subgroup.id ?? `new-${index}`} className="rounded-xl border border-[var(--color-border)] bg-white p-4">
                  <div className="flex items-start gap-2">
                    <div className="grid min-w-0 flex-1 gap-2">
                      <Input
                        aria-label={`ชื่อบริเวณย่อย ${index + 1}`}
                        placeholder="ชื่อบริเวณย่อย"
                        value={subgroup.name}
                        onChange={(event) => onFormChange({
                          ...form,
                          subgroups: form.subgroups.map((item, itemIndex) =>
                            itemIndex === index ? { ...item, name: event.target.value } : item),
                        })}
                      />
                      <Input
                        aria-label={`คำอธิบายบริเวณย่อย ${index + 1}`}
                        placeholder="คำอธิบายสั้น (ถ้ามี)"
                        value={subgroup.description}
                        onChange={(event) => onFormChange({
                          ...form,
                          subgroups: form.subgroups.map((item, itemIndex) =>
                            itemIndex === index ? { ...item, description: event.target.value } : item),
                        })}
                      />
                    </div>
                    <button
                      type="button"
                      aria-label={`ลบบริเวณย่อย ${subgroup.name || index + 1}`}
                      className="rounded-md p-2 text-red-600 hover:bg-red-50"
                      onClick={() => onFormChange({
                        ...form,
                        subgroups: form.subgroups.filter((_, itemIndex) => itemIndex !== index),
                      })}
                    >
                      <Trash2 className="h-4 w-4" />
                    </button>
                  </div>
                  <div className="mt-4 flex items-center justify-between gap-3">
                    <Label>อาการในบริเวณย่อย ({subgroup.symptom_ids.length})</Label>
                    {subgroup.symptom_ids.length > 0 && (
                      <button
                        type="button"
                        className="text-xs font-medium text-red-600 hover:underline"
                        onClick={() => onFormChange({
                          ...form,
                          subgroups: form.subgroups.map((item, itemIndex) =>
                            itemIndex === index ? { ...item, symptom_ids: [] } : item),
                        })}
                      >
                        ล้างที่เลือก
                      </button>
                    )}
                  </div>
                  <Input
                    className="mt-1"
                    placeholder="ค้นหาอาการในกลุ่ม"
                    value={subgroupSearches[index] ?? ""}
                    onChange={(event) => setSubgroupSearches((current) => ({
                      ...current,
                      [index]: event.target.value,
                    }))}
                  />
                  <div className="mt-2 max-h-56 overflow-y-auto rounded-lg border border-[var(--color-border)] p-2">
                    {symptoms
                      .filter((symptom) => form.symptom_ids.includes(symptom.symptom_id))
                      .filter((symptom) => fuzzyIncludes(
                        `${symptom.symptom_name} ${symptom.symptom_name_en ?? ""}`,
                        subgroupSearches[index] ?? "",
                      ))
                      .map((symptom) => (
                        <label
                          key={symptom.symptom_id}
                          className="flex cursor-pointer items-center gap-3 rounded-md px-3 py-2 hover:bg-[var(--color-surface)]"
                        >
                          <Checkbox
                            checked={subgroup.symptom_ids.includes(symptom.symptom_id)}
                            onCheckedChange={() => onFormChange({
                              ...form,
                              subgroups: form.subgroups.map((item, itemIndex) =>
                                itemIndex === index
                                  ? {
                                      ...item,
                                      symptom_ids: item.symptom_ids.includes(symptom.symptom_id)
                                        ? item.symptom_ids.filter((id) => id !== symptom.symptom_id)
                                        : [...item.symptom_ids, symptom.symptom_id],
                                    }
                                  : item,
                              ),
                            })}
                          />
                          <span className="min-w-0 text-sm">
                            <span className="block truncate">{symptom.symptom_name}</span>
                            {symptom.symptom_name_en && (
                              <span className="block truncate text-xs text-[var(--color-text-secondary)]">
                                {symptom.symptom_name_en}
                              </span>
                            )}
                          </span>
                        </label>
                      ))}
                    {form.symptom_ids.length === 0 && (
                      <p className="p-5 text-center text-sm text-[var(--color-text-secondary)]">
                        กรุณาเลือกอาการในกลุ่มด้านบนก่อน
                      </p>
                    )}
                  </div>
                </div>
              ))}
            </div>
          )}
        </section>
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
        outputType="image/webp"
        onCancel={() => setPendingFile(null)}
        onConfirm={(blob) => {
          const image = new File([blob], `body-area-${Date.now()}.webp`, {
            type: "image/webp",
          });
          onFormChange({ ...form, image });
          setPreview(URL.createObjectURL(blob));
          setPendingFile(null);
        }}
      />
    </Dialog>
  );
}
