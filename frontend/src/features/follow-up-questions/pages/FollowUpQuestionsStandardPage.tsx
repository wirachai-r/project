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
import type { FollowUpAnswerType, FollowUpQuestionPayload, FollowUpQuestionTemplate, FollowUpResponseAction, FollowUpResponseOperator } from "@/types/followUpQuestion";
import { FollowUpQuestionFilters } from "../components/FollowUpQuestionFilters";
import { FollowUpQuestionTable, type FollowUpQuestionRow } from "../components/FollowUpQuestionTable";
import { FOLLOW_UP_ANSWER_TYPES } from "../constants";
import { DataLoadError } from "@/components/ui/DataLoadError";

const emptyForm = (): FollowUpQuestionPayload => ({
  question_text: "", description: "", answer_type: "boolean", options: null,
  unit: null, response_rules: null, is_required: false, applies_to_all_symptoms: true, status: "1", symptoms: [],
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
  const [sortKey, setSortKey] = useState<string | null>("created_at");
  const [sortDirection, setSortDirection] = useState<"asc" | "desc" | null>("desc");
  const [formOpen, setFormOpen] = useState(false);
  const [editing, setEditing] = useState<FollowUpQuestionTemplate | null>(null);
  const [form, setForm] = useState<FollowUpQuestionPayload>(emptyForm());
  const [options, setOptions] = useState<string[]>([]);
  const [scaleMin, setScaleMin] = useState("0");
  const [scaleMax, setScaleMax] = useState("10");
  const [scaleStep, setScaleStep] = useState("1");
  const [draggedOption, setDraggedOption] = useState<number | null>(null);
  const [symptomSearch, setSymptomSearch] = useState("");
  const [saving, setSaving] = useState(false);
  const [toggleTarget, setToggleTarget] = useState<FollowUpQuestionTemplate | null>(null);
  const [toggling, setToggling] = useState(false);
  const [deleteTarget, setDeleteTarget] = useState<FollowUpQuestionTemplate | null>(null);
  const [deleting, setDeleting] = useState(false);
  const [rulesTarget, setRulesTarget] = useState<FollowUpQuestionTemplate | null>(null);
  const [rules, setRules] = useState<NonNullable<FollowUpQuestionPayload["response_rules"]>>([]);
  const [savingRules, setSavingRules] = useState(false);

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
      const leftValue = sortKey === "question"
        ? left.question_text
        : left.created_at ?? left.id;
      const rightValue = sortKey === "question"
        ? right.question_text
        : right.created_at ?? right.id;
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
    setOptions(["ใช่", "ไม่ใช่"]);
    setScaleMin("0");
    setScaleMax("10");
    setScaleStep("1");
    setSymptomSearch("");
    setFormOpen(true);
  };
  const openEdit = (item: FollowUpQuestionTemplate) => {
    setEditing(item);
    setOptions(item.options ?? []);
    const numericOptions = (item.options ?? []).map(Number).filter(Number.isFinite);
    setScaleMin(numericOptions.length > 0 ? String(numericOptions[0]) : "0");
    setScaleMax(numericOptions.length > 1 ? String(numericOptions[numericOptions.length - 1]) : "10");
    setScaleStep(numericOptions.length > 1 ? String(numericOptions[1] - numericOptions[0]) : "1");
    setSymptomSearch("");
    setForm({
      question_text: item.question_text, description: item.description ?? "",
      answer_type: item.answer_type, options: item.options, unit: item.unit,
      response_rules: item.response_rules,
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
    let normalizedOptions: string[] | null = null;
    if (["single_choice", "multiple_choice"].includes(source.answer_type)) normalizedOptions = choices;
    if (source.answer_type === "boolean") normalizedOptions = [options[0]?.trim() ?? "", options[1]?.trim() ?? ""];
    if (source.answer_type === "scale") {
      const min = Number(scaleMin);
      const max = Number(scaleMax);
      const step = Number(scaleStep);
      const count = Number.isFinite(min) && Number.isFinite(max) && Number.isFinite(step) && step > 0
        ? Math.floor((max - min) / step + 1e-9) + 1 : 0;
      const reachesMaximum = count >= 2 && Math.abs(min + (count - 1) * step - max) < 1e-8;
      normalizedOptions = reachesMaximum && count <= 101
        ? Array.from({ length: count }, (_, index) => Number((min + index * step).toFixed(10)).toString())
        : [];
    }
    return {
      ...source,
      options: normalizedOptions,
      symptoms: source.applies_to_all_symptoms ? [] : source.symptoms,
    };
  };
  const save = async () => {
    const payload = normalizedPayload(form);
    if (!payload.question_text.trim()) return toast.error("กรุณากรอกคำถาม");
    if (!payload.applies_to_all_symptoms && payload.symptoms.length === 0) return toast.error("กรุณาเลือกอาการอย่างน้อยหนึ่งรายการ");
    if (payload.answer_type === "boolean" && (payload.options?.some((item) => !item) || payload.options?.[0] === payload.options?.[1])) return toast.error("กรุณาระบุข้อความคำตอบใช่และไม่ใช่ให้ครบและไม่ซ้ำกัน");
    if (["single_choice", "multiple_choice"].includes(payload.answer_type) && (payload.options?.length ?? 0) < 2) return toast.error("กรุณาระบุตัวเลือกอย่างน้อย 2 ตัวเลือก");
    if (payload.answer_type === "scale" && (payload.options?.length ?? 0) < 2) return toast.error("กรุณากำหนดค่าต่ำสุด ค่าสูงสุด และขั้นเพิ่มให้ถูกต้อง");
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
        response_rules: toggleTarget.response_rules,
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

  const openRules = (item: FollowUpQuestionTemplate) => {
    setRulesTarget(item);
    setRules(item.response_rules ?? []);
  };

  const ruleChoices = useMemo(() => {
    if (!rulesTarget) return [];
    if (rulesTarget.answer_type === "boolean") {
      return [
        { value: "true", label: rulesTarget.options?.[0] ?? "ใช่" },
        { value: "false", label: rulesTarget.options?.[1] ?? "ไม่ใช่" },
      ];
    }
    if (["single_choice", "multiple_choice"].includes(rulesTarget.answer_type)) {
      return (rulesTarget.options ?? []).map((option) => ({ value: option, label: option }));
    }
    return [];
  }, [rulesTarget]);

  const operatorOptions = useMemo(() => {
    if (!rulesTarget) return [];
    if (["number", "scale"].includes(rulesTarget.answer_type)) return [
      { value: "equals", label: "เท่ากับ" }, { value: "not_equals", label: "ไม่เท่ากับ" },
      { value: "greater_than", label: "มากกว่า" }, { value: "greater_than_or_equal", label: "มากกว่าหรือเท่ากับ" },
      { value: "less_than", label: "น้อยกว่า" }, { value: "less_than_or_equal", label: "น้อยกว่าหรือเท่ากับ" },
      { value: "between", label: "อยู่ระหว่าง" },
    ];
    if (["date", "time"].includes(rulesTarget.answer_type)) return [
      { value: "equals", label: "ตรงกับ" }, { value: "greater_than", label: "หลังจาก" },
      { value: "less_than", label: "ก่อน" }, { value: "between", label: "อยู่ระหว่าง" },
      { value: "is_empty", label: "ไม่ได้กรอก" }, { value: "is_not_empty", label: "กรอกแล้ว" },
    ];
    return [
      { value: "equals", label: "ตรงกับ" }, { value: "not_equals", label: "ไม่ตรงกับ" },
      { value: "contains", label: "มีคำว่า" }, { value: "not_contains", label: "ไม่มีคำว่า" },
      { value: "is_empty", label: "ไม่ได้กรอก" }, { value: "is_not_empty", label: "กรอกแล้ว" },
    ];
  }, [rulesTarget]);

  const updateRule = (index: number, changes: Partial<(typeof rules)[number]>) =>
    setRules((current) => current.map((rule, ruleIndex) => ruleIndex === index ? { ...rule, ...changes } : rule));

  const addRule = () => setRules((current) => [...current, {
    operator: operatorOptions[0]?.value as FollowUpResponseOperator ?? "equals",
    value: "",
    action: "prompt_end_tracking",
  }]);

  const saveRules = async () => {
    if (!rulesTarget) return;
    const invalid = rules.some((rule) => !["is_empty", "is_not_empty"].includes(rule.operator ?? "equals")
      && (rule.value === null || String(rule.value).trim() === ""
        || ((rule.operator ?? "equals") === "between" && (rule.value_to === null || rule.value_to === undefined || String(rule.value_to).trim() === ""))));
    if (invalid) return toast.error("กรุณาระบุค่าที่ใช้เปรียบเทียบให้ครบ");
    if (rules.some((rule) => rule.action === "show_alert" && !rule.message?.trim())) {
      return toast.error("กรุณาระบุข้อความแจ้งเตือนให้ครบ");
    }
    const numericRules = ["number", "scale"].includes(rulesTarget.answer_type);
    const normalizedRules = rules.map((rule) => ({
      ...rule,
      value: numericRules && rule.value !== null && rule.value !== ""
        ? Number(rule.value)
        : rule.value,
      value_to: numericRules && rule.value_to !== null && rule.value_to !== undefined && rule.value_to !== ""
        ? Number(rule.value_to)
        : rule.value_to,
    }));
    setSavingRules(true);
    try {
      await followUpQuestionApi.update(rulesTarget.id, {
        question_text: rulesTarget.question_text,
        description: rulesTarget.description ?? "",
        answer_type: rulesTarget.answer_type,
        options: rulesTarget.options,
        unit: rulesTarget.unit,
        response_rules: normalizedRules,
        is_required: rulesTarget.is_required,
        applies_to_all_symptoms: rulesTarget.applies_to_all_symptoms,
        status: rulesTarget.status,
        symptoms: rulesTarget.symptoms.map((symptom, index) => ({ symptom_id: symptom.symptom_id, sequence: index + 1, is_required: null })),
      });
      toast.success("บันทึกเงื่อนไขหลังตอบแล้ว");
      setRulesTarget(null);
      await load();
    } catch (error) {
      toast.error(getErrorMessage(error));
    } finally {
      setSavingRules(false);
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
          onManageRules={openRules}
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
          if (nextType === "boolean" && options.length !== 2) setOptions(["ใช่", "ไม่ใช่"]);
          if (["single_choice", "multiple_choice"].includes(nextType) && !["single_choice", "multiple_choice"].includes(form.answer_type)) setOptions([]);
        }} options={FOLLOW_UP_ANSWER_TYPES} /></div>
        {form.answer_type === "boolean" && <div className="rounded-xl border border-[var(--color-border)] p-4">
          <Label>ข้อความตัวเลือก</Label>
          <p className="mb-3 text-xs text-[var(--color-text-secondary)]">กำหนดข้อความที่ผู้ใช้เห็นสำหรับคำตอบใช่และไม่ใช่</p>
          <div className="grid gap-3 sm:grid-cols-2">
            <div><Label htmlFor="boolean-yes">คำตอบใช่</Label><Input id="boolean-yes" value={options[0] ?? ""} placeholder="ใช่" onChange={(event) => setOptions([event.target.value, options[1] ?? "ไม่ใช่"])} /></div>
            <div><Label htmlFor="boolean-no">คำตอบไม่ใช่</Label><Input id="boolean-no" value={options[1] ?? ""} placeholder="ไม่ใช่" onChange={(event) => setOptions([options[0] ?? "ใช่", event.target.value])} /></div>
          </div>
        </div>}
        {["single_choice", "multiple_choice"].includes(form.answer_type) && <div>
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
        {form.answer_type === "scale" && <div className="rounded-xl border border-[var(--color-border)] p-4">
          <Label>กำหนดช่วงระดับ</Label>
          <p className="mb-3 text-xs text-[var(--color-text-secondary)]">ระบบจะสร้างค่าระดับในช่วงนี้ให้อัตโนมัติ</p>
          <div className="grid gap-3 sm:grid-cols-3">
            <div><Label htmlFor="scale-min">ค่าต่ำสุด</Label><Input id="scale-min" type="number" step="any" value={scaleMin} onChange={(event) => setScaleMin(event.target.value)} /></div>
            <div><Label htmlFor="scale-max">ค่าสูงสุด</Label><Input id="scale-max" type="number" step="any" value={scaleMax} onChange={(event) => setScaleMax(event.target.value)} /></div>
            <div><Label htmlFor="scale-step">เพิ่มครั้งละ</Label><Input id="scale-step" type="number" min="0.000001" step="any" value={scaleStep} onChange={(event) => setScaleStep(event.target.value)} /></div>
          </div>
          <p className="mt-3 rounded-lg bg-[var(--color-surface)] px-3 py-2 text-sm text-[var(--color-text-secondary)]">ตัวอย่างค่าที่ผู้ใช้เลือกได้: {normalizedPayload(form).options?.slice(0, 8).join(", ") || "ช่วงไม่ถูกต้อง"}{(normalizedPayload(form).options?.length ?? 0) > 8 ? " …" : ""}</p>
        </div>}
        {["number", "scale"].includes(form.answer_type) && <div><Label htmlFor="follow-up-unit">หน่วย</Label><Input id="follow-up-unit" value={form.unit ?? ""} placeholder={form.answer_type === "scale" ? "เช่น คะแนน (ไม่บังคับ)" : "เช่น °C, ครั้ง"} onChange={(event) => setForm({ ...form, unit: event.target.value || null })} /></div>}
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

    <Dialog open={rulesTarget !== null} onOpenChange={(open) => !open && !savingRules && setRulesTarget(null)}>
      <DialogContent maxWidth="xl" className="flex flex-col md:max-h-[85vh] md:overflow-hidden">
        <DialogHeader>
          <DialogTitle>จัดการเงื่อนไขหลังตอบ</DialogTitle>
          <p className="text-sm text-[var(--color-text-secondary)]">กำหนดสิ่งที่ระบบจะแสดงหลังผู้ใช้บันทึกคำตอบ</p>
        </DialogHeader>
        <div className="mb-4 rounded-xl bg-[var(--color-surface)] px-4 py-3">
          <p className="text-xs text-[var(--color-text-secondary)]">คำถาม</p>
          <p className="mt-0.5 font-medium text-[var(--color-text-primary)]">{rulesTarget?.question_text}</p>
        </div>
        {ruleChoices.length > 0 ? (
          <div className="min-h-0 flex-1 rounded-xl border border-[var(--color-border)]">
            <div className="grid grid-cols-[minmax(72px,0.65fr)_minmax(0,1.35fr)] gap-3 border-b border-[var(--color-border)] bg-[var(--color-surface)] px-4 py-2 text-xs font-medium text-[var(--color-text-secondary)]">
              <span>เมื่อคำตอบเป็น</span><span>ให้ระบบทำอะไร</span>
            </div>
            <div className="max-h-[44vh] divide-y divide-[var(--color-border)] overflow-y-auto overscroll-contain rounded-b-xl pb-2">
              {ruleChoices.map((choice) => {
                const storedValue = choice.value === "true" ? true : choice.value === "false" ? false : choice.value;
                const currentRule = rules.find((rule) => rule.value === storedValue);
                const current = currentRule?.action ?? "";
                return <div key={choice.value} className="grid grid-cols-[minmax(72px,0.65fr)_minmax(0,1.35fr)] items-center gap-3 px-4 py-2.5">
                  <div className="flex min-w-0 items-center gap-2">
                    <span className="flex h-7 min-w-7 items-center justify-center rounded-md bg-[var(--color-primary)]/10 px-2 text-sm font-semibold text-[var(--color-primary)]">{choice.label}</span>
                  </div>
                  <div className="space-y-2">
                    <SimpleSelect value={current || "none"} onChange={(action) => setRules((existing) => {
                      const withoutChoice = existing.filter((rule) => rule.value !== storedValue);
                      return action === "none" ? withoutChoice : [...withoutChoice, { ...currentRule, operator: "equals", value: storedValue, action: action as FollowUpResponseAction }];
                    })} options={[
                      { value: "none", label: "ไม่ต้องทำอะไร" },
                      { value: "prompt_end_tracking", label: "ถามว่าจะสิ้นสุดการติดตามไหม" },
                      { value: "prompt_add_symptom", label: "ถามว่าจะเพิ่มอาการใหม่ไหม" },
                      { value: "show_alert", label: "แสดงข้อความแจ้งเตือน" },
                    ]} />
                    {current === "show_alert" && <div className={`space-y-2 rounded-lg border border-[var(--color-border)] border-l-4 bg-white p-3 shadow-sm ${(currentRule?.alert_level ?? "warning") === "important" ? "border-l-red-500" : (currentRule?.alert_level ?? "warning") === "info" ? "border-l-blue-500" : "border-l-amber-500"}`}>
                      <SimpleSelect value={currentRule?.alert_level ?? "warning"} onChange={(value) => setRules((existing) => existing.map((rule) => rule.value === storedValue ? { ...rule, alert_level: value as "info" | "warning" | "important", requires_acknowledgement: value === "important" ? true : rule.requires_acknowledgement } : rule))} options={[
                        { value: "info", label: "ข้อมูลทั่วไป" },
                        { value: "warning", label: "ควรสังเกต" },
                        { value: "important", label: "สำคัญ" },
                      ]} />
                      <Input value={currentRule?.title ?? ""} maxLength={150} placeholder="หัวข้อแจ้งเตือน (ไม่บังคับ)" onChange={(event) => setRules((existing) => existing.map((rule) => rule.value === storedValue ? { ...rule, title: event.target.value } : rule))} />
                      <Textarea rows={2} value={currentRule?.message ?? ""} maxLength={500} placeholder="ข้อความที่ต้องการแจ้งผู้ใช้" onChange={(event) => setRules((existing) => existing.map((rule) => rule.value === storedValue ? { ...rule, message: event.target.value } : rule))} />
                      <label className="flex items-center gap-2 text-xs"><Checkbox disabled={(currentRule?.alert_level ?? "warning") === "important"} checked={(currentRule?.alert_level ?? "warning") === "important" || currentRule?.requires_acknowledgement === true} onCheckedChange={(checked) => setRules((existing) => existing.map((rule) => rule.value === storedValue ? { ...rule, requires_acknowledgement: checked === true } : rule))} />ต้องกดรับทราบ</label>
                    </div>}
                  </div>
                </div>;
              })}
            </div>
          </div>
        ) : (
          <div className="min-h-0 flex-1 space-y-3 overflow-y-auto pr-1">
            {rules.map((rule, index) => {
              const operator = rule.operator ?? "equals";
              const needsValue = !["is_empty", "is_not_empty"].includes(operator);
              const inputType = rulesTarget?.answer_type === "date" ? "date"
                : rulesTarget?.answer_type === "time" ? "time"
                : ["number", "scale"].includes(rulesTarget?.answer_type ?? "") ? "number" : "text";
              return <div key={index} className="rounded-xl border border-[var(--color-border)] p-4">
                <div className="mb-3 flex items-center justify-between">
                  <span className="text-sm font-semibold">เงื่อนไขที่ {index + 1}</span>
                  <Button variant="ghost" size="icon" onClick={() => setRules((current) => current.filter((_, itemIndex) => itemIndex !== index))} aria-label="ลบเงื่อนไข"><Trash2 className="h-4 w-4 text-red-500" /></Button>
                </div>
                <div className="grid gap-3 md:grid-cols-2">
                  <div><Label>ตัวดำเนินการ</Label><SimpleSelect value={operator} onChange={(value) => updateRule(index, {
                    operator: value as FollowUpResponseOperator,
                    value: ["is_empty", "is_not_empty"].includes(value) ? null : rule.value,
                    value_to: value === "between" ? rule.value_to ?? "" : null,
                  })} options={operatorOptions} /></div>
                  {needsValue && <div><Label>ค่าที่ใช้เปรียบเทียบ</Label><Input type={inputType} step={inputType === "number" ? "any" : undefined} value={rule.value?.toString() ?? ""} onChange={(event) => updateRule(index, { value: event.target.value })} /></div>}
                  {operator === "between" && <div><Label>ถึงค่า</Label><Input type={inputType} step={inputType === "number" ? "any" : undefined} value={rule.value_to?.toString() ?? ""} onChange={(event) => updateRule(index, { value_to: event.target.value })} /></div>}
                  <div className={operator === "between" ? "" : "md:col-span-2"}><Label>เมื่อเงื่อนไขตรง</Label><SimpleSelect value={rule.action} onChange={(value) => updateRule(index, { action: value as FollowUpResponseAction })} options={[
                    { value: "prompt_end_tracking", label: "ถามว่าจะสิ้นสุดการติดตามไหม" },
                    { value: "prompt_add_symptom", label: "ถามว่าจะเพิ่มอาการใหม่ไหม" },
                    { value: "show_alert", label: "แสดงข้อความแจ้งเตือน" },
                  ]} /></div>
                  {rule.action === "show_alert" && <div className={`space-y-3 rounded-lg border border-[var(--color-border)] border-l-4 bg-white p-4 shadow-sm md:col-span-2 ${(rule.alert_level ?? "warning") === "important" ? "border-l-red-500" : (rule.alert_level ?? "warning") === "info" ? "border-l-blue-500" : "border-l-amber-500"}`}>
                    <div><Label>ระดับการแจ้งเตือน</Label><SimpleSelect value={rule.alert_level ?? "warning"} onChange={(value) => updateRule(index, { alert_level: value as "info" | "warning" | "important", requires_acknowledgement: value === "important" ? true : rule.requires_acknowledgement })} options={[
                      { value: "info", label: "ข้อมูลทั่วไป" },
                      { value: "warning", label: "ควรสังเกต" },
                      { value: "important", label: "สำคัญ" },
                    ]} /></div>
                    <div><Label>หัวข้อ</Label><Input maxLength={150} value={rule.title ?? ""} placeholder="หัวข้อแจ้งเตือน (ไม่บังคับ)" onChange={(event) => updateRule(index, { title: event.target.value })} /></div>
                    <div><Label>ข้อความแจ้งเตือน *</Label><Textarea rows={2} maxLength={500} value={rule.message ?? ""} placeholder="เช่น ค่าที่บันทึกเกินช่วงที่กำหนด กรุณาตรวจสอบข้อมูล" onChange={(event) => updateRule(index, { message: event.target.value })} /></div>
                    <label className="flex items-center gap-2 text-sm"><Checkbox disabled={(rule.alert_level ?? "warning") === "important"} checked={(rule.alert_level ?? "warning") === "important" || rule.requires_acknowledgement === true} onCheckedChange={(checked) => updateRule(index, { requires_acknowledgement: checked === true })} />ต้องให้ผู้ใช้กดรับทราบ</label>
                  </div>}
                </div>
              </div>;
            })}
            {rules.length === 0 && <div className="rounded-xl border border-dashed border-[var(--color-border)] p-6 text-center text-sm text-[var(--color-text-secondary)]">ยังไม่มีเงื่อนไขสำหรับคำถามนี้</div>}
            <Button variant="outline" className="w-full" onClick={addRule}><Plus className="h-4 w-4" />เพิ่มเงื่อนไข</Button>
          </div>
        )}
        <div className="mt-3 flex items-center justify-between gap-3 text-xs text-[var(--color-text-secondary)]">
          <span>{ruleChoices.length > 0 ? `ตั้งค่าแล้ว ${rules.length} จาก ${ruleChoices.length} คำตอบ` : `ตั้งค่าแล้ว ${rules.length} เงื่อนไข`}</span>
          {rules.length > 0 && <button type="button" className="font-medium text-red-600 hover:underline" onClick={() => setRules([])}>ล้างเงื่อนไขทั้งหมด</button>}
        </div>
        <DialogFooter className="mt-4 shrink-0 border-t border-[var(--color-border)] pt-4"><Button variant="outline" onClick={() => setRulesTarget(null)}>ยกเลิก</Button><Button loading={savingRules} onClick={() => void saveRules()}>บันทึกเงื่อนไข</Button></DialogFooter>
      </DialogContent>
    </Dialog>

    <AlertDialog open={toggleTarget !== null} onOpenChange={(next) => !next && !toggling && setToggleTarget(null)}><AlertDialogContent><AlertDialogHeader><AlertDialogTitle>{toggleTarget?.status === "1" ? "ยืนยันการปิดใช้งานคำถาม" : "ยืนยันการเปิดใช้งานคำถาม"}</AlertDialogTitle><AlertDialogDescription>ต้องการเปลี่ยนสถานะคำถาม “{toggleTarget?.question_text}” หรือไม่?</AlertDialogDescription></AlertDialogHeader><AlertDialogFooter><AlertDialogCancel disabled={toggling}>ยกเลิก</AlertDialogCancel><AlertDialogAction loading={toggling} onClick={() => void toggleStatus()}>ยืนยัน</AlertDialogAction></AlertDialogFooter></AlertDialogContent></AlertDialog>
    <AlertDialog open={deleteTarget !== null} onOpenChange={(next) => !next && !deleting && setDeleteTarget(null)}><AlertDialogContent><AlertDialogHeader><AlertDialogTitle>ยืนยันการลบคำถาม</AlertDialogTitle><AlertDialogDescription>ต้องการลบคำถาม “{deleteTarget?.question_text}” หรือไม่? คำถามจะถูกนำออกจากอาการทั้งหมด แต่คำตอบที่เคยบันทึกไว้จะยังคงข้อความเดิมในประวัติ</AlertDialogDescription></AlertDialogHeader><AlertDialogFooter><AlertDialogCancel disabled={deleting}>ยกเลิก</AlertDialogCancel><AlertDialogAction loading={deleting} onClick={() => void remove()}>ลบคำถาม</AlertDialogAction></AlertDialogFooter></AlertDialogContent></AlertDialog>
  </div>;
}
