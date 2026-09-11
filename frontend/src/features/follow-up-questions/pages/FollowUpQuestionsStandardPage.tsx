import { useCallback, useEffect, useMemo, useState } from "react";
import { GripVertical, Plus, Trash2 } from "lucide-react";
import { toast } from "sonner";
import {
  AlertDialog, AlertDialogAction, AlertDialogCancel, AlertDialogContent,
  AlertDialogDescription, AlertDialogFooter, AlertDialogHeader, AlertDialogTitle,
} from "@/components/ui/AlertDialog";
import { Button } from "@/components/ui/Button";
import { Card } from "@/components/ui/Card";
import { Checkbox } from "@/components/ui/Checkbox";
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from "@/components/ui/Dialog";
import { Input } from "@/components/ui/Input";
import { Label } from "@/components/ui/Label";
import { Pagination } from "@/components/ui/Pagination";
import { SimpleSelect } from "@/components/ui/SimpleSelect";
import { TableSkeleton } from "@/components/ui/TableSkeleton";
import { Textarea } from "@/components/ui/Textarea";
import { followUpQuestionApi } from "@/lib/api/followUpQuestion";
import { symptomApi } from "@/lib/api/symptom";
import { getErrorMessage } from "@/lib/getErrorMessage";
import type { Symptom } from "@/types/symptom";
import type { FollowUpAnswerType, FollowUpQuestionPayload, FollowUpQuestionTemplate } from "@/types/followUpQuestion";
import { FollowUpQuestionFilters } from "../components/FollowUpQuestionFilters";
import { FollowUpQuestionTable, type FollowUpQuestionRow } from "../components/FollowUpQuestionTable";
import { FOLLOW_UP_ANSWER_TYPES } from "../constants";
import { DataLoadError } from "@/components/ui/DataLoadError";

const emptyForm = (): FollowUpQuestionPayload => ({
  question_text: "", description: "", answer_type: "boolean", options: null,
  unit: null, is_required: false, applies_to_all_symptoms: true, status: "1", symptoms: [],
});

export function FollowUpQuestionsPage() {
  const [items, setItems] = useState<FollowUpQuestionTemplate[]>([]);
  const [symptoms, setSymptoms] = useState<Symptom[]>([]);
  const [loading, setLoading] = useState(true);
  const [loadError, setLoadError] = useState<string | null>(null);
  const [search, setSearch] = useState("");
  const [status, setStatus] = useState("");
  const [answerTypes, setAnswerTypes] = useState<string[]>([]);
  const [page, setPage] = useState(1);
  const [pageSize, setPageSize] = useState(10);
  const [sortKey, setSortKey] = useState<string | null>(null);
  const [sortDirection, setSortDirection] = useState<"asc" | "desc" | null>(null);
  const [formOpen, setFormOpen] = useState(false);
  const [editing, setEditing] = useState<FollowUpQuestionTemplate | null>(null);
  const [form, setForm] = useState<FollowUpQuestionPayload>(emptyForm());
  const [options, setOptions] = useState<string[]>([]);
  const [draggedOption, setDraggedOption] = useState<number | null>(null);
  const [symptomSearch, setSymptomSearch] = useState("");
  const [saving, setSaving] = useState(false);
  const [toggleTarget, setToggleTarget] = useState<FollowUpQuestionTemplate | null>(null);
  const [toggling, setToggling] = useState(false);
  const [deleteTarget, setDeleteTarget] = useState<FollowUpQuestionTemplate | null>(null);
  const [deleting, setDeleting] = useState(false);

  const load = useCallback(async () => {
    setLoading(true);
    setLoadError(null);
    try {
      const [questions, symptomResponse] = await Promise.all([
        followUpQuestionApi.list(), symptomApi.listAll({ status: "1" }),
      ]);
      setItems(questions);
      setSymptoms(symptomResponse);
    } catch (error) {
      const message = getErrorMessage(error);
      setLoadError(message);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => void load(), [load]);
  useEffect(() => setPage(1), [search, status, answerTypes, pageSize]);

  const filtered = useMemo(() => {
    const term = search.trim().toLowerCase();
    const result = items.filter((item) => (!term || `${item.question_text} ${item.description ?? ""}`.toLowerCase().includes(term))
      && (!status || item.status === status)
      && (answerTypes.length === 0 || answerTypes.includes(item.answer_type)));
    if (!sortKey || !sortDirection) return result;
    return [...result].sort((left, right) => {
      const leftValue = sortKey === "question" ? left.question_text : left.id;
      const rightValue = sortKey === "question" ? right.question_text : right.id;
      return String(leftValue).localeCompare(String(rightValue), "th", { numeric: true })
        * (sortDirection === "asc" ? 1 : -1);
    });
  }, [answerTypes, items, search, sortDirection, sortKey, status]);
  const lastPage = Math.max(1, Math.ceil(filtered.length / pageSize));
  const visibleItems = filtered.slice((page - 1) * pageSize, page * pageSize);
  const visibleRows: FollowUpQuestionRow[] = visibleItems.map((item, index) => ({
    ...item,
    rowNumber: (page - 1) * pageSize + index + 1,
  }));

  const openCreate = () => {
    setEditing(null);
    setForm(emptyForm());
    setOptions([]);
    setSymptomSearch("");
    setFormOpen(true);
  };
  const openEdit = (item: FollowUpQuestionTemplate) => {
    setEditing(item);
    setOptions(item.options ?? []);
    setSymptomSearch("");
    setForm({
      question_text: item.question_text, description: item.description ?? "",
      answer_type: item.answer_type, options: item.options, unit: item.unit,
      is_required: item.is_required, applies_to_all_symptoms: item.applies_to_all_symptoms,
      status: item.status,
      symptoms: item.symptoms.map((symptom, index) => ({
        symptom_id: symptom.symptom_id, sequence: index + 1, is_required: null,
      })),
    });
    setFormOpen(true);
  };
  const toggleSymptom = (symptomId: string) => setForm((current) => {
    const selected = current.symptoms.some((item) => item.symptom_id === symptomId);
    const next = selected
      ? current.symptoms.filter((item) => item.symptom_id !== symptomId)
      : [...current.symptoms, { symptom_id: symptomId, sequence: current.symptoms.length + 1, is_required: null }];
    return { ...current, symptoms: next.map((item, index) => ({ ...item, sequence: index + 1 })) };
  });
  const updateOption = (index: number, value: string) =>
    setOptions((current) => current.map((item, itemIndex) => itemIndex === index ? value : item));
  const removeOption = (index: number) =>
    setOptions((current) => current.filter((_, itemIndex) => itemIndex !== index));
  const dropOption = (targetIndex: number) => {
    if (draggedOption === null || draggedOption === targetIndex) return setDraggedOption(null);
    setOptions((current) => {
      const next = [...current];
      const [moved] = next.splice(draggedOption, 1);
      next.splice(targetIndex, 0, moved);
      return next;
    });
    setDraggedOption(null);
  };
  const visibleSymptoms = symptoms
    .filter((symptom) =>
      `${symptom.symptom_name} ${symptom.symptom_name_en ?? ""}`
        .toLowerCase()
        .includes(symptomSearch.trim().toLowerCase()),
    )
    .sort((left, right) => left.symptom_name.localeCompare(right.symptom_name, "th", {
      sensitivity: "base",
      numeric: true,
    }));
  const visibleSymptomIds = new Set(visibleSymptoms.map((symptom) => symptom.symptom_id));
  const allVisibleSymptomsSelected = visibleSymptoms.length > 0
    && visibleSymptoms.every((symptom) =>
      form.symptoms.some((item) => item.symptom_id === symptom.symptom_id),
    );
  const toggleVisibleSymptoms = () => setForm((current) => {
    const remaining = allVisibleSymptomsSelected
      ? current.symptoms.filter((item) => !visibleSymptomIds.has(item.symptom_id))
      : Array.from(new Map([
          ...current.symptoms,
          ...visibleSymptoms.map((symptom) => ({
            symptom_id: symptom.symptom_id,
            sequence: 0,
            is_required: null,
          })),
        ].map((item) => [item.symptom_id, item])).values());
    return {
      ...current,
      symptoms: remaining.map((item, index) => ({ ...item, sequence: index + 1 })),
    };
  });
  const normalizedPayload = (source: FollowUpQuestionPayload): FollowUpQuestionPayload => {
    const choices = options.map((value) => value.trim()).filter(Boolean);
    const needsChoices = ["single_choice", "multiple_choice", "scale"].includes(source.answer_type);
    return {
      ...source,
      options: needsChoices ? choices : source.answer_type === "boolean" ? ["ใช่", "ไม่ใช่"] : null,
      symptoms: source.applies_to_all_symptoms ? [] : source.symptoms,
    };
  };
  const save = async () => {
    const payload = normalizedPayload(form);
    if (!payload.question_text.trim()) return toast.error("กรุณากรอกคำถาม");
    if (!payload.applies_to_all_symptoms && payload.symptoms.length === 0) return toast.error("กรุณาเลือกอาการอย่างน้อยหนึ่งรายการ");
    if (["single_choice", "multiple_choice", "scale"].includes(payload.answer_type) && (payload.options?.length ?? 0) < 2) return toast.error("กรุณาระบุตัวเลือกอย่างน้อย 2 ตัวเลือก");
    setSaving(true);
    try {
      if (editing) await followUpQuestionApi.update(editing.id, payload);
      else await followUpQuestionApi.create(payload);
      toast.success(editing ? "บันทึกคำถามสำเร็จ" : "เพิ่มคำถามสำเร็จ");
      setFormOpen(false);
      await load();
    } catch (error) {
      toast.error(getErrorMessage(error));
    } finally {
      setSaving(false);
    }
  };
  const toggleStatus = async () => {
    if (!toggleTarget) return;
    setToggling(true);
    try {
      const payload: FollowUpQuestionPayload = {
        question_text: toggleTarget.question_text, description: toggleTarget.description ?? "",
        answer_type: toggleTarget.answer_type, options: toggleTarget.options, unit: toggleTarget.unit,
        is_required: toggleTarget.is_required, applies_to_all_symptoms: toggleTarget.applies_to_all_symptoms,
        status: toggleTarget.status === "1" ? "0" : "1",
        symptoms: toggleTarget.symptoms.map((symptom, index) => ({ symptom_id: symptom.symptom_id, sequence: index + 1, is_required: null })),
      };
      await followUpQuestionApi.update(toggleTarget.id, payload);
      toast.success(payload.status === "1" ? "เปิดใช้งานคำถามแล้ว" : "ปิดใช้งานคำถามแล้ว");
      setToggleTarget(null);
      await load();
    } catch (error) {
      toast.error(getErrorMessage(error));
    } finally {
      setToggling(false);
    }
  };
  const remove = async () => {
    if (!deleteTarget) return;
    setDeleting(true);
    try {
      await followUpQuestionApi.delete(deleteTarget.id);
      toast.success("ลบคำถามติดตามอาการแล้ว");
      setDeleteTarget(null);
      await load();
    } catch (error) {
      toast.error(getErrorMessage(error));
    } finally {
      setDeleting(false);
    }
  };

  return <div>
    <div className="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <h1 className="text-xl font-semibold text-[var(--color-text-primary)]">จัดการคำถามติดตามอาการ</h1>
      <Button onClick={openCreate}><Plus className="h-4 w-4" />เพิ่มคำถาม</Button>
    </div>
    <FollowUpQuestionFilters
      search={search}
      status={status}
      answerTypes={answerTypes}
      sortKey={sortKey}
      sortDirection={sortDirection}
      onSearchChange={setSearch}
      onStatusChange={setStatus}
      onAnswerTypesChange={setAnswerTypes}
      onSortChange={(key, direction) => {
        setSortKey(key);
        setSortDirection(direction);
        setPage(1);
      }}
      onClear={() => {
        setSearch("");
        setAnswerTypes([]);
        setStatus("");
      }}
    />
    <Card className="mt-4 p-0">
      {loading ? (
        <TableSkeleton columns={7} />
      ) : loadError && items.length === 0 ? (
        <DataLoadError description={loadError} onRetry={() => void load()} />
      ) : (
        <FollowUpQuestionTable
          rows={visibleRows}
          onEdit={openEdit}
          onToggleStatus={setToggleTarget}
          onDelete={setDeleteTarget}
        />
      )}
    </Card>
    {filtered.length > 0 && <Pagination className="mt-4" current={Math.min(page, lastPage)} total={lastPage} onChange={setPage} pageSize={pageSize} onPageSizeChange={setPageSize} totalItems={filtered.length} itemLabel="คำถาม" />}

    <Dialog open={formOpen} onOpenChange={setFormOpen}><DialogContent maxWidth="lg" className="flex flex-col md:h-[90vh] md:max-h-[900px] md:overflow-hidden"><DialogHeader><DialogTitle>{editing ? "แก้ไขคำถามติดตามอาการ" : "เพิ่มคำถามติดตามอาการ"}</DialogTitle></DialogHeader>
      <div className="min-h-0 flex-1 space-y-4 overflow-y-auto pr-1">
        <div><Label htmlFor="follow-up-question">คำถาม *</Label><Input id="follow-up-question" value={form.question_text} onChange={(event) => setForm({ ...form, question_text: event.target.value })} /></div>
        <div><Label htmlFor="follow-up-description">คำอธิบาย</Label><Textarea id="follow-up-description" rows={3} value={form.description} onChange={(event) => setForm({ ...form, description: event.target.value })} /></div>
        <div><Label>รูปแบบคำตอบ *</Label><SimpleSelect value={form.answer_type} onChange={(value) => {
          const nextType = value as FollowUpAnswerType;
          setForm({ ...form, answer_type: nextType });
          if (nextType === "scale" && options.length === 0) setOptions(["1", "2", "3", "4", "5"]);
        }} options={FOLLOW_UP_ANSWER_TYPES} /></div>
        {["single_choice", "multiple_choice", "scale"].includes(form.answer_type) && <div>
          <div className="mb-2 flex items-center justify-between">
            <div><Label>จัดการตัวเลือก *</Label><p className="text-xs text-[var(--color-text-secondary)]">ลากเพื่อจัดลำดับ และแก้ไข label ที่แสดงกับผู้ใช้</p></div>
            <Button type="button" variant="outline" size="sm" onClick={() => setOptions((current) => [...current, ""])}><Plus className="h-4 w-4" />เพิ่มตัวเลือก</Button>
          </div>
          <div className="max-h-64 space-y-2 overflow-y-auto rounded-lg border border-[var(--color-border)] p-2">
            {options.map((option, index) => <div
              key={index}
              draggable
              onDragStart={() => setDraggedOption(index)}
              onDragOver={(event) => event.preventDefault()}
              onDrop={() => dropOption(index)}
              className={`grid grid-cols-[32px_40px_1fr_36px] items-center gap-2 rounded-md border border-[var(--color-border)] bg-white p-2 ${draggedOption === index ? "opacity-40" : ""}`}
            >
              <button type="button" className="cursor-grab text-[var(--color-text-secondary)]" aria-label="ลากเพื่อเปลี่ยนลำดับ"><GripVertical className="h-4 w-4" /></button>
              <span className="text-center text-xs text-[var(--color-text-secondary)]">{index + 1}</span>
              <Input value={option} onChange={(event) => updateOption(index, event.target.value)} placeholder={`label ตัวเลือก ${index + 1}`} />
              <Button type="button" variant="ghost" size="icon" onClick={() => removeOption(index)} aria-label="ลบตัวเลือก"><Trash2 className="h-4 w-4 text-red-500" /></Button>
            </div>)}
            {options.length === 0 && <p className="py-5 text-center text-sm text-[var(--color-text-secondary)]">ยังไม่มีตัวเลือก</p>}
          </div>
        </div>}
        {form.answer_type === "number" && <div><Label htmlFor="follow-up-unit">หน่วย</Label><Input id="follow-up-unit" value={form.unit ?? ""} onChange={(event) => setForm({ ...form, unit: event.target.value || null })} /></div>}
        <label className="flex items-center gap-2 text-sm"><Checkbox checked={form.is_required} onCheckedChange={(checked) => setForm({ ...form, is_required: checked === true })} />บังคับตอบ</label>
        <label className="flex items-start gap-2 text-sm"><Checkbox className="mt-0.5" checked={form.applies_to_all_symptoms} onCheckedChange={(checked) => setForm({ ...form, applies_to_all_symptoms: checked === true })} /><span><span className="block">ใช้กับทุกอาการ</span><span className="block text-xs text-[var(--color-text-secondary)]">รวมถึงอาการใหม่ที่จะเพิ่มในระบบภายหลัง</span></span></label>
        {!form.applies_to_all_symptoms && <div>
          <div className="flex items-center justify-between gap-3"><Label>อาการที่ใช้คำถามนี้ ({form.symptoms.length})</Label>{form.symptoms.length > 0 && <button type="button" onClick={() => setForm({ ...form, symptoms: [] })} className="text-xs font-medium text-red-600 hover:underline">ล้างที่เลือก</button>}</div>
          <Input className="mt-1" placeholder="ค้นหาอาการ" value={symptomSearch} onChange={(event) => setSymptomSearch(event.target.value)} />
          <div className="mt-2 flex items-center justify-between text-xs text-[var(--color-text-secondary)]"><span>พบ {visibleSymptoms.length} อาการ · เลือกเฉพาะผลลัพธ์ที่แสดงอยู่</span>{visibleSymptoms.length > 0 && <button type="button" onClick={toggleVisibleSymptoms} className="font-medium text-[var(--color-primary)] hover:underline">{allVisibleSymptomsSelected ? "ยกเลิกเลือกผลลัพธ์ทั้งหมด" : "เลือกผลลัพธ์ทั้งหมด"}</button>}</div>
          <div className="mt-2 max-h-64 space-y-1 overflow-y-auto rounded-lg border border-[var(--color-border)] p-2">{visibleSymptoms.length === 0 ? <p className="p-6 text-center text-sm text-[var(--color-text-secondary)]">ไม่พบอาการที่ค้นหา</p> : visibleSymptoms.map((symptom) => <label key={symptom.symptom_id} className="flex cursor-pointer items-center gap-3 rounded-md px-3 py-2 hover:bg-[var(--color-surface)]"><Checkbox checked={form.symptoms.some((item) => item.symptom_id === symptom.symptom_id)} onCheckedChange={() => toggleSymptom(symptom.symptom_id)} /><span className="min-w-0 text-sm"><span className="block truncate">{symptom.symptom_name}</span>{symptom.symptom_name_en && <span className="block truncate text-xs text-[var(--color-text-secondary)]">{symptom.symptom_name_en}</span>}</span></label>)}</div>
        </div>}
        <div><Label>สถานะ</Label><SimpleSelect value={form.status} onChange={(value) => setForm({ ...form, status: value as "0" | "1" })} options={[{ value: "1", label: "ใช้งาน" }, { value: "0", label: "ปิดใช้งาน" }]} /></div>
      </div>
      <DialogFooter className="shrink-0 border-t border-[var(--color-border)] pt-4"><Button variant="outline" onClick={() => setFormOpen(false)}>ยกเลิก</Button><Button loading={saving} onClick={() => void save()}>บันทึก</Button></DialogFooter>
    </DialogContent></Dialog>

    <AlertDialog open={toggleTarget !== null} onOpenChange={(next) => !next && !toggling && setToggleTarget(null)}><AlertDialogContent><AlertDialogHeader><AlertDialogTitle>{toggleTarget?.status === "1" ? "ยืนยันการปิดใช้งานคำถาม" : "ยืนยันการเปิดใช้งานคำถาม"}</AlertDialogTitle><AlertDialogDescription>ต้องการเปลี่ยนสถานะคำถาม “{toggleTarget?.question_text}” หรือไม่?</AlertDialogDescription></AlertDialogHeader><AlertDialogFooter><AlertDialogCancel disabled={toggling}>ยกเลิก</AlertDialogCancel><AlertDialogAction loading={toggling} onClick={() => void toggleStatus()}>ยืนยัน</AlertDialogAction></AlertDialogFooter></AlertDialogContent></AlertDialog>
    <AlertDialog open={deleteTarget !== null} onOpenChange={(next) => !next && !deleting && setDeleteTarget(null)}><AlertDialogContent><AlertDialogHeader><AlertDialogTitle>ยืนยันการลบคำถาม</AlertDialogTitle><AlertDialogDescription>ต้องการลบคำถาม “{deleteTarget?.question_text}” หรือไม่? คำถามจะถูกนำออกจากอาการทั้งหมด แต่คำตอบที่เคยบันทึกไว้จะยังคงข้อความเดิมในประวัติ</AlertDialogDescription></AlertDialogHeader><AlertDialogFooter><AlertDialogCancel disabled={deleting}>ยกเลิก</AlertDialogCancel><AlertDialogAction loading={deleting} onClick={() => void remove()}>ลบคำถาม</AlertDialogAction></AlertDialogFooter></AlertDialogContent></AlertDialog>
  </div>;
}
