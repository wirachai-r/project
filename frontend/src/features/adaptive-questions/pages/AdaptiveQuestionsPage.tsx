import { useEffect, useState } from "react";
import { Pencil, Plus, Trash2 } from "lucide-react";
import { toast } from "sonner";
import { Badge } from "@/components/ui/Badge";
import { Button } from "@/components/ui/Button";
import { Card } from "@/components/ui/Card";
import { Checkbox } from "@/components/ui/Checkbox";
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from "@/components/ui/Dialog";
import { Input } from "@/components/ui/Input";
import { Label } from "@/components/ui/Label";
import { SimpleSelect } from "@/components/ui/SimpleSelect";
import { Textarea } from "@/components/ui/Textarea";
import { adaptiveQuestionApi } from "@/lib/api/adaptiveQuestion";
import { symptomApi } from "@/lib/api/symptom";
import { getErrorMessage } from "@/lib/getErrorMessage";
import type { AdaptiveQuestion, AdaptiveQuestionPayload } from "@/types/adaptiveQuestion";
import type { Symptom } from "@/types/symptom";

const emptyForm = (): AdaptiveQuestionPayload => ({
  question_symptom_id: "", question_text: "", explanation_text: "",
  answer_type: "yes_no_unsure", status: "draft", evidence_source: "",
  options: [], rules: [{ initial_symptom_id: "", question_stage: "local", priority: 100, is_required: false, status: "1" }],
});

export function AdaptiveQuestionsPage() {
  const [items, setItems] = useState<AdaptiveQuestion[]>([]);
  const [symptoms, setSymptoms] = useState<Symptom[]>([]);
  const [form, setForm] = useState<AdaptiveQuestionPayload>(emptyForm());
  const [editing, setEditing] = useState<AdaptiveQuestion | null>(null);
  const [open, setOpen] = useState(false);
  const [saving, setSaving] = useState(false);

  const load = async () => {
    try {
      const [questions, symptomItems] = await Promise.all([adaptiveQuestionApi.list(), symptomApi.listAll({ status: "1" })]);
      setItems(questions); setSymptoms(symptomItems);
    } catch (error) { toast.error(getErrorMessage(error)); }
  };
  useEffect(() => { void load(); }, []);

  const symptomOptions = symptoms.map((item) => ({ value: item.symptom_id, label: item.symptom_name }));
  const edit = (item?: AdaptiveQuestion) => {
    setEditing(item ?? null);
    setForm(item ? {
      question_symptom_id: item.question_symptom_id, question_text: item.question_text,
      explanation_text: item.explanation_text ?? "", answer_type: item.answer_type,
      status: item.status, evidence_source: item.evidence_source ?? "",
      options: item.options ?? [], rules: item.rules ?? [],
    } : emptyForm());
    setOpen(true);
  };

  const save = async () => {
    if (!form.question_text.trim() || !form.question_symptom_id || form.rules.some((rule) => !rule.initial_symptom_id)) {
      toast.error("กรุณากรอกคำถาม อาการที่ตรวจ และอาการเริ่มต้นให้ครบ"); return;
    }
    setSaving(true);
    try {
      if (editing) await adaptiveQuestionApi.update(editing.id, form); else await adaptiveQuestionApi.create(form);
      toast.success("บันทึกคำถาม Adaptive แล้ว"); setOpen(false); await load();
    } catch (error) { toast.error(getErrorMessage(error)); } finally { setSaving(false); }
  };

  const addOption = () => setForm((current) => ({ ...current, options: [...current.options, { option_text: "", option_value: `option_${current.options.length + 1}`, target_symptom_id: current.question_symptom_id || null, answer_effect: "present", display_order: current.options.length }] }));
  const addRule = () => setForm((current) => ({ ...current, rules: [...current.rules, { initial_symptom_id: "", question_stage: "local", priority: 100, is_required: false, status: "1" }] }));

  return <div className="space-y-6">
    <div className="flex items-start justify-between gap-4"><div><h1 className="text-2xl font-bold">คำถามประเมินแบบ Adaptive</h1><p className="mt-1 text-sm text-[var(--color-text-secondary)]">กำหนดคำถามที่อนุญาต รูปแบบคำตอบ และอาการเริ่มต้นที่ใช้คำถาม</p></div><Button onClick={() => edit()}><Plus className="h-4 w-4" />เพิ่มคำถาม</Button></div>
    <Card className="overflow-hidden p-0"><div className="divide-y divide-[var(--color-border)]">
      {items.map((item) => <div key={item.id} className="flex items-center gap-4 p-4"><div className="min-w-0 flex-1"><div className="flex flex-wrap items-center gap-2"><p className="font-medium">{item.question_text}</p><Badge>{item.answer_type === "yes_no_unsure" ? "ใช่ / ไม่ใช่ / ไม่แน่ใจ" : item.answer_type === "single_choice" ? "เลือกหนึ่งข้อ" : "เลือกหลายข้อ"}</Badge><Badge>{item.status === "approved" ? "อนุมัติแล้ว" : item.status === "draft" ? "ฉบับร่าง" : "ปิดใช้งาน"}</Badge></div><p className="mt-1 text-xs text-[var(--color-text-secondary)]">ตรวจอาการ: {item.symptom?.symptom_name ?? item.question_symptom_id} · ใช้กับ {item.rules.length} อาการเริ่มต้น</p></div><Button variant="ghost" size="icon" onClick={() => edit(item)} aria-label="แก้ไข"><Pencil className="h-4 w-4" /></Button><Button variant="ghost" size="icon" onClick={async () => { if (!confirm("ยืนยันการลบคำถามนี้?")) return; try { await adaptiveQuestionApi.delete(item.id); await load(); } catch (error) { toast.error(getErrorMessage(error)); } }} aria-label="ลบ"><Trash2 className="h-4 w-4 text-red-500" /></Button></div>)}
      {items.length === 0 && <p className="p-10 text-center text-sm text-[var(--color-text-secondary)]">ยังไม่มีคำถาม Adaptive ระบบจะใช้วิธีเดิมจนกว่าจะมีคำถามที่อนุมัติ</p>}
    </div></Card>

    <Dialog open={open} onOpenChange={setOpen}><DialogContent maxWidth="2xl" className="max-h-[92vh]"><DialogHeader><DialogTitle>{editing ? "แก้ไขคำถาม Adaptive" : "เพิ่มคำถาม Adaptive"}</DialogTitle></DialogHeader><div className="space-y-5">
      <div className="grid gap-4 md:grid-cols-2"><SimpleSelect label="อาการที่คำถามตรวจ" value={form.question_symptom_id} onChange={(value) => setForm({ ...form, question_symptom_id: value })} options={symptomOptions} /><SimpleSelect label="รูปแบบคำตอบ" value={form.answer_type} onChange={(value) => setForm({ ...form, answer_type: value as AdaptiveQuestionPayload["answer_type"], options: value === "yes_no_unsure" ? [] : form.options })} options={[{ value: "yes_no_unsure", label: "ใช่ / ไม่ใช่ / ไม่แน่ใจ" }, { value: "single_choice", label: "เลือกหนึ่งข้อ" }, { value: "multiple_choice", label: "เลือกหลายข้อ" }]} /></div>
      <div><Label>ข้อความคำถาม</Label><Textarea rows={2} value={form.question_text} onChange={(event) => setForm({ ...form, question_text: event.target.value })} /></div>
      <div><Label>คำอธิบายสำหรับผู้ใช้</Label><Textarea rows={2} value={form.explanation_text} onChange={(event) => setForm({ ...form, explanation_text: event.target.value })} /></div>
      {form.answer_type !== "yes_no_unsure" && <section className="space-y-3 rounded-xl border border-[var(--color-border)] p-4"><div className="flex items-center justify-between"><h2 className="font-semibold">ตัวเลือกคำตอบ</h2><Button variant="outline" size="sm" onClick={addOption}><Plus className="h-4 w-4" />เพิ่มตัวเลือก</Button></div>{form.options.map((option, index) => <div key={index} className="grid gap-2 rounded-lg bg-[var(--color-surface)] p-3 md:grid-cols-4"><Input placeholder="ข้อความตัวเลือก" value={option.option_text} onChange={(event) => setForm({ ...form, options: form.options.map((item, i) => i === index ? { ...item, option_text: event.target.value } : item) })} /><SimpleSelect value={option.target_symptom_id ?? ""} onChange={(value) => setForm({ ...form, options: form.options.map((item, i) => i === index ? { ...item, target_symptom_id: value } : item) })} options={symptomOptions} placeholder="อาการที่เป็นผล" /><SimpleSelect value={option.answer_effect} onChange={(value) => setForm({ ...form, options: form.options.map((item, i) => i === index ? { ...item, answer_effect: value as typeof option.answer_effect } : item) })} options={[{ value: "present", label: "พบอาการ" }, { value: "absent", label: "ไม่พบอาการ" }, { value: "unknown", label: "ไม่แน่ใจ" }]} /><Button variant="ghost" onClick={() => setForm({ ...form, options: form.options.filter((_, i) => i !== index) })}><Trash2 className="h-4 w-4" />ลบ</Button></div>)}</section>}
      <section className="space-y-3 rounded-xl border border-[var(--color-border)] p-4"><div className="flex items-center justify-between"><h2 className="font-semibold">ใช้คำถามเมื่อเริ่มจากอาการ</h2><Button variant="outline" size="sm" onClick={addRule}><Plus className="h-4 w-4" />เพิ่มอาการ</Button></div>{form.rules.map((rule, index) => <div key={index} className="grid gap-2 rounded-lg bg-[var(--color-surface)] p-3 md:grid-cols-5"><SimpleSelect value={rule.initial_symptom_id} onChange={(value) => setForm({ ...form, rules: form.rules.map((item, i) => i === index ? { ...item, initial_symptom_id: value } : item) })} options={symptomOptions} placeholder="อาการเริ่มต้น" /><SimpleSelect value={rule.question_stage} onChange={(value) => setForm({ ...form, rules: form.rules.map((item, i) => i === index ? { ...item, question_stage: value as typeof rule.question_stage } : item) })} options={[{ value: "local", label: "เฉพาะจุด" }, { value: "associated", label: "อาการร่วม" }, { value: "safety", label: "สัญญาณสำคัญ" }]} /><Input type="number" min={1} max={999} value={rule.priority} onChange={(event) => setForm({ ...form, rules: form.rules.map((item, i) => i === index ? { ...item, priority: Number(event.target.value) } : item) })} /><label className="flex items-center gap-2 text-sm"><Checkbox checked={rule.is_required} onCheckedChange={(checked) => setForm({ ...form, rules: form.rules.map((item, i) => i === index ? { ...item, is_required: checked === true } : item) })} />ต้องถาม</label><Button variant="ghost" onClick={() => setForm({ ...form, rules: form.rules.filter((_, i) => i !== index) })}><Trash2 className="h-4 w-4" />ลบ</Button></div>)}</section>
      <div><Label>แหล่งอ้างอิงหรือผู้ตรวจสอบ</Label><Textarea rows={2} value={form.evidence_source} onChange={(event) => setForm({ ...form, evidence_source: event.target.value })} /></div>
      <SimpleSelect label="สถานะ" value={form.status} onChange={(value) => setForm({ ...form, status: value as AdaptiveQuestionPayload["status"] })} options={[{ value: "draft", label: "ฉบับร่าง" }, { value: "approved", label: "อนุมัติให้ระบบใช้" }, { value: "inactive", label: "ปิดใช้งาน" }]} />
    </div><DialogFooter><Button variant="outline" onClick={() => setOpen(false)}>ยกเลิก</Button><Button loading={saving} onClick={() => void save()}>บันทึก</Button></DialogFooter></DialogContent></Dialog>
  </div>;
}
