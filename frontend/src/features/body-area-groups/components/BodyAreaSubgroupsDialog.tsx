import { useState } from "react";
import { ChevronDown, ChevronRight, GripVertical, ImagePlus, Plus, Trash2 } from "lucide-react";
import { Button } from "@/components/ui/Button";
import { Checkbox } from "@/components/ui/Checkbox";
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from "@/components/ui/Dialog";
import { Input } from "@/components/ui/Input";
import { Label } from "@/components/ui/Label";
import { fuzzyIncludes } from "@/lib/fuzzySearch";
import type { BodyAreaSubgroup } from "@/types/bodyAreaGroup";
import type { Symptom } from "@/types/symptom";

interface Props {
  open: boolean;
  items: BodyAreaSubgroup[];
  availableSymptomIds: string[];
  symptoms: Symptom[];
  onChange: (items: BodyAreaSubgroup[]) => void;
  onClose: () => void;
  onSave: () => void;
  saving: boolean;
}

export function BodyAreaSubgroupsDialog({ open, items, availableSymptomIds, symptoms, onChange, onClose, onSave, saving }: Props) {
  const [searches, setSearches] = useState<Record<number, string>>({});
  const [expanded, setExpanded] = useState<Set<number>>(() => new Set());
  const [draggingIndex, setDraggingIndex] = useState<number | null>(null);
  const [dropTarget, setDropTarget] = useState<{ index: number; position: "before" | "after" } | null>(null);
  const toggleExpanded = (index: number) => setExpanded((current) => {
    const next = new Set(current);
    if (next.has(index)) next.delete(index);
    else next.add(index);
    return next;
  });
  const update = (index: number, value: Partial<BodyAreaSubgroup>) =>
    onChange(items.map((item, itemIndex) => itemIndex === index ? { ...item, ...value } : item));
  const moveTo = (target: number, position: "before" | "after") => {
    if (draggingIndex === null) return;
    if (draggingIndex === target) {
      setDraggingIndex(null);
      setDropTarget(null);
      return;
    }
    const reordered = [...items];
    const [moved] = reordered.splice(draggingIndex, 1);
    let insertionIndex = draggingIndex < target ? target - 1 : target;
    if (position === "after") insertionIndex += 1;
    reordered.splice(insertionIndex, 0, moved);
    setExpanded(new Set());
    setDraggingIndex(null);
    setDropTarget(null);
    onChange(reordered.map((item, order) => ({ ...item, display_order: order })));
  };
  const addSubgroup = () => {
    const newIndex = items.length;
    onChange([...items, {
      name: "",
      name_en: "",
      description: "",
      status: "1",
      symptom_ids: [],
      image: null,
      display_order: newIndex,
    }]);
    setExpanded((current) => new Set(current).add(newIndex));
  };

  return (
    <Dialog open={open} onOpenChange={(next) => !next && !saving && onClose()}>
      <DialogContent maxWidth="2xl" className="flex max-h-[92vh] flex-col overflow-hidden">
        <DialogHeader>
          <DialogTitle>จัดการบริเวณย่อย</DialogTitle>
          <p className="text-sm text-[var(--color-text-secondary)]">เพิ่มรูป เลือกอาการ และใช้ลูกศรเพื่อจัดลำดับการแสดง</p>
        </DialogHeader>
        <div className="min-h-0 flex-1 space-y-4 overflow-y-auto pr-1">
          {items.length === 0 && (
            <div className="rounded-xl border border-dashed border-[var(--color-border)] p-10 text-center text-sm text-[var(--color-text-secondary)]">
              ยังไม่มีบริเวณย่อย กลุ่มนี้จะเปิดรายการอาการทันที
            </div>
          )}
          {items.map((item, index) => {
            const term = searches[index] ?? "";
            const available = symptoms.filter((symptom) =>
              availableSymptomIds.includes(symptom.symptom_id) &&
              fuzzyIncludes(`${symptom.symptom_name} ${symptom.symptom_name_en ?? ""}`, term),
            );
            const preview = item.image ? URL.createObjectURL(item.image) : item.image_url;
            return (
              <section
                key={item.id ?? `new-${index}`}
                onDragOver={(event) => {
                  if (draggingIndex === null) return;
                  event.preventDefault();
                  event.dataTransfer.dropEffect = "move";
                  const rect = event.currentTarget.getBoundingClientRect();
                  const position = event.clientY < rect.top + rect.height / 2 ? "before" : "after";
                  setDropTarget((current) =>
                    current?.index === index && current.position === position
                      ? current
                      : { index, position },
                  );
                }}
                onDrop={(event) => {
                  event.preventDefault();
                  if (dropTarget?.index === index) moveTo(index, dropTarget.position);
                }}
                className={`relative rounded-xl border p-4 transition ${
                  draggingIndex === index
                    ? "border-[var(--color-border)] bg-white opacity-35"
                    : dropTarget?.index === index
                      ? "border-[var(--color-primary)] bg-[var(--color-primary-light)]/25 shadow-[0_0_0_1px_var(--color-primary)]"
                      : "border-[var(--color-border)] bg-white"
                }`}
              >
                <div className={expanded.has(index) ? "mb-3 flex items-center gap-2" : "flex items-center gap-2"}>
                  <span
                    draggable
                    onDragStart={(event) => {
                      setDraggingIndex(index);
                      event.dataTransfer.effectAllowed = "move";
                      const card = event.currentTarget.closest("section");
                      if (card instanceof HTMLElement) {
                        event.dataTransfer.setDragImage(card, 28, 28);
                      }
                    }}
                    onDragEnd={() => {
                      setDraggingIndex(null);
                      setDropTarget(null);
                    }}
                    title="ลากเพื่อจัดลำดับ"
                    className="inline-flex cursor-grab items-center gap-1 rounded-md px-1 py-2 text-[var(--color-text-secondary)] active:cursor-grabbing"
                  >
                    <GripVertical className="h-4 w-4" />
                    <span className="min-w-4 text-center text-sm">{index + 1}</span>
                  </span>
                  {preview ? (
                    <img src={preview} alt="" className="h-10 w-14 rounded-lg object-cover" />
                  ) : (
                    <div className="flex h-10 w-14 items-center justify-center rounded-lg bg-[var(--color-surface)] text-[var(--color-text-secondary)]">
                      <ImagePlus className="h-4 w-4" />
                    </div>
                  )}
                  <button type="button" onClick={() => toggleExpanded(index)} className="min-w-0 flex-1 text-left">
                    <span className="block truncate font-medium">{item.name || `บริเวณย่อย ${index + 1}`}</span>
                    <span className="block text-xs text-[var(--color-text-secondary)]">{item.symptom_ids.length} อาการ</span>
                  </button>
                  <div className="ml-auto flex items-center gap-1">
                    <button type="button" aria-label={expanded.has(index) ? "ย่อ" : "ขยาย"} aria-expanded={expanded.has(index)} onClick={() => toggleExpanded(index)} className="rounded-md p-2 text-[var(--color-text-secondary)] hover:bg-[var(--color-surface)]">
                      {expanded.has(index) ? <ChevronDown className="h-4 w-4" /> : <ChevronRight className="h-4 w-4" />}
                    </button>
                    <button type="button" aria-label="ลบบริเวณย่อย" onClick={() => onChange(items.filter((_, itemIndex) => itemIndex !== index))} className="rounded-md p-2 text-red-600 hover:bg-red-50"><Trash2 className="h-4 w-4" /></button>
                  </div>
                </div>
                {expanded.has(index) && <>
                <div className="grid gap-4 md:grid-cols-[180px_1fr]">
                  <label className="relative flex min-h-36 cursor-pointer items-center justify-center overflow-hidden rounded-xl border-2 border-dashed border-[var(--color-border)] bg-[var(--color-surface)]/40">
                    {preview ? <img src={preview} className="absolute inset-0 h-full w-full object-cover" alt="ตัวอย่างบริเวณย่อย" /> : <span className="flex flex-col items-center gap-2 text-sm text-[var(--color-text-secondary)]"><ImagePlus className="h-7 w-7" />เพิ่มรูปภาพ</span>}
                    <input type="file" accept="image/png,image/jpeg,image/webp" className="hidden" onChange={(event) => {
                      const image = event.target.files?.[0] ?? null;
                      if (image) update(index, { image, remove_image: false });
                      event.target.value = "";
                    }} />
                  </label>
                  <div className="grid content-start gap-3">
                    <div><Label>ชื่อบริเวณย่อย</Label><Input value={item.name} onChange={(event) => update(index, { name: event.target.value })} /></div>
                    <div><Label>คำอธิบายสั้น</Label><Input value={item.description} onChange={(event) => update(index, { description: event.target.value })} /></div>
                  </div>
                </div>
                <div className="mt-4 flex items-center justify-between"><Label>อาการ ({item.symptom_ids.length})</Label>{item.symptom_ids.length > 0 && <button type="button" onClick={() => update(index, { symptom_ids: [] })} className="text-xs font-medium text-red-600 hover:underline">ล้างที่เลือก</button>}</div>
                <Input className="mt-1" placeholder="ค้นหาอาการ" value={term} onChange={(event) => setSearches((current) => ({ ...current, [index]: event.target.value }))} />
                <div className="mt-2 grid max-h-52 overflow-y-auto rounded-lg border border-[var(--color-border)] p-2 md:grid-cols-2">
                  {available.map((symptom) => <label key={symptom.symptom_id} className="flex cursor-pointer items-center gap-3 rounded-md px-3 py-2 hover:bg-[var(--color-surface)]"><Checkbox checked={item.symptom_ids.includes(symptom.symptom_id)} onCheckedChange={() => update(index, { symptom_ids: item.symptom_ids.includes(symptom.symptom_id) ? item.symptom_ids.filter((id) => id !== symptom.symptom_id) : [...item.symptom_ids, symptom.symptom_id] })} /><span className="truncate text-sm">{symptom.symptom_name}</span></label>)}
                  {availableSymptomIds.length === 0 && <p className="col-span-full p-5 text-center text-sm text-[var(--color-text-secondary)]">กรุณาเลือกอาการในกลุ่มหลักก่อน</p>}
                </div>
                </>}
              </section>
            );
          })}
        </div>
        <DialogFooter className="shrink-0 border-t border-[var(--color-border)] bg-white pt-4">
          <Button variant="outline" disabled={saving} onClick={addSubgroup}><Plus className="h-4 w-4" />เพิ่มบริเวณย่อย</Button>
          <div className="flex-1" />
          <Button variant="outline" disabled={saving} onClick={onClose}>ยกเลิก</Button>
          <Button loading={saving} onClick={onSave}>บันทึก</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
