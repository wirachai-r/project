import { useEffect, useMemo, useState } from "react";
import {
  ChevronDown,
  ChevronUp,
  Plus,
  Search,
  Trash2,
  X,
} from "lucide-react";
import { toast } from "sonner";
import { Button } from "@/components/ui/Button";
import { Card } from "@/components/ui/Card";
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
import { FilterBar } from "@/components/ui/FilterBar";
import { MultiSelectFilter } from "@/components/ui/MultiSelectFilter";
import { Pagination } from "@/components/ui/Pagination";
import { SimpleSelect } from "@/components/ui/SimpleSelect";
import { TableSkeleton } from "@/components/ui/TableSkeleton";
import { Textarea } from "@/components/ui/Textarea";
import { adaptiveQuestionApi } from "@/lib/api/adaptiveQuestion";
import { symptomApi } from "@/lib/api/symptom";
import { fuzzyIncludes } from "@/lib/fuzzySearch";
import { getErrorMessage } from "@/lib/getErrorMessage";
import type {
  AdaptiveQuestion,
  AdaptiveQuestionPayload,
} from "@/types/adaptiveQuestion";
import type { Symptom } from "@/types/symptom";
import {
  AdaptiveQuestionFilters,
  type AdaptiveQuestionFilterValue,
} from "../components/AdaptiveQuestionFilters";
import {
  AdaptiveQuestionTable,
  type AdaptiveQuestionRow,
} from "../components/AdaptiveQuestionTable";

const emptyForm = (): AdaptiveQuestionPayload => ({
  question_symptom_ids: [],
  question_text: "",
  explanation_text: "",
  answer_type: "yes_no_unsure",
  status: "draft",
  evidence_source: "",
  options: [],
  rules: [],
});

export function AdaptiveQuestionsPage() {
  const [items, setItems] = useState<AdaptiveQuestion[]>([]);
  const [symptoms, setSymptoms] = useState<Symptom[]>([]);
  const [form, setForm] = useState<AdaptiveQuestionPayload>(emptyForm());
  const [editing, setEditing] = useState<AdaptiveQuestion | null>(null);
  const [open, setOpen] = useState(false);
  const [initialLoading, setInitialLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [symptomSearch, setSymptomSearch] = useState("");
  const [search, setSearch] = useState("");
  const [answerTypes, setAnswerTypes] = useState<string[]>([]);
  const [status, setStatus] = useState("");
  const [sortKey, setSortKey] = useState<string | null>("created_at");
  const [sortDirection, setSortDirection] = useState<"asc" | "desc" | null>(
    "desc",
  );
  const [page, setPage] = useState(1);
  const [pageSize, setPageSize] = useState(10);

  const load = async () => {
    try {
      const [questions, symptomItems] = await Promise.all([
        adaptiveQuestionApi.list(),
        symptomApi.listAll({ status: "1" }),
      ]);
      setItems(questions);
      setSymptoms(symptomItems);
    } catch (error) {
      toast.error(getErrorMessage(error));
    } finally {
      setInitialLoading(false);
    }
  };
  useEffect(() => {
    void load();
  }, []);
  useEffect(() => setPage(1), [search, answerTypes, status, pageSize]);

  const sortedSymptoms = [...symptoms].sort((a, b) =>
    a.symptom_name.localeCompare(b.symptom_name, "th"),
  );
  const symptomOptions = sortedSymptoms.map((item) => ({
    value: item.symptom_id,
    label: item.symptom_name,
  }));
  const visibleInitialSymptoms = sortedSymptoms.filter((item) =>
    fuzzyIncludes(
      `${item.symptom_id} ${item.symptom_name} ${item.symptom_name_en ?? ""}`,
      symptomSearch,
    ),
  );
  const filteredItems = useMemo(() => {
    const filtered = items.filter(
      (item) =>
        fuzzyIncludes(
          `${item.question_text} ${item.explanation_text ?? ""} ${item.evidence_source ?? ""} ${item.rules.map((rule) => rule.initial_symptom?.symptom_name ?? "").join(" ")}`,
          search,
        ) &&
        (answerTypes.length === 0 || answerTypes.includes(item.answer_type)) &&
        (!status || item.status === status),
    );

    if (!sortKey || !sortDirection) return filtered;
    return [...filtered].sort((left, right) => {
      const leftValue = sortKey === "question" ? left.question_text : left.id;
      const rightValue = sortKey === "question" ? right.question_text : right.id;
      return (
        String(leftValue).localeCompare(String(rightValue), "th", {
          numeric: true,
        }) * (sortDirection === "asc" ? 1 : -1)
      );
    });
  }, [answerTypes, items, search, sortDirection, sortKey, status]);
  const lastPage = Math.max(1, Math.ceil(filteredItems.length / pageSize));
  const visibleRows: AdaptiveQuestionRow[] = filteredItems
    .slice((page - 1) * pageSize, page * pageSize)
    .map((item, index) => ({
      ...item,
      rowNumber: (page - 1) * pageSize + index + 1,
    }));
  const remove = async (item: AdaptiveQuestion) => {
    if (!confirm(`ยืนยันการลบคำถาม “${item.question_text}”?`)) return;
    try {
      await adaptiveQuestionApi.delete(item.id);
      toast.success("ลบคำถามแบบตามคำตอบแล้ว");
      await load();
    } catch (error) {
      toast.error(getErrorMessage(error));
    }
  };

  const edit = (item?: AdaptiveQuestion) => {
    setSymptomSearch("");
    setEditing(item ?? null);
    setForm(
      item
        ? {
            question_symptom_ids:
              item.symptoms?.map((symptom) => symptom.symptom_id) ??
              (item.symptom ? [item.symptom.symptom_id] : []),
            question_text: item.question_text,
            explanation_text: item.explanation_text ?? "",
            answer_type: item.answer_type,
            status: item.status,
            evidence_source: item.evidence_source ?? "",
            options: item.options ?? [],
            rules: [...(item.rules ?? [])]
              .sort((a, b) => a.priority - b.priority)
              .map((rule, index) => ({ ...rule, priority: index + 1 })),
          }
        : emptyForm(),
    );
    setOpen(true);
  };

  const save = async () => {
    if (
      !form.question_text.trim() ||
      form.question_symptom_ids.length === 0 ||
      form.rules.some((rule) => !rule.initial_symptom_id)
    ) {
      toast.error("กรุณากรอกคำถาม อาการที่ตรวจ และอาการเริ่มต้นให้ครบ");
      return;
    }
    setSaving(true);
    try {
      const payload = {
        ...form,
        rules: form.rules.map((rule, index) => ({
          ...rule,
          priority: index + 1,
        })),
      };
      if (editing) await adaptiveQuestionApi.update(editing.id, payload);
      else await adaptiveQuestionApi.create(payload);
      toast.success("บันทึกคำถามแบบตามคำตอบแล้ว");
      setOpen(false);
      await load();
    } catch (error) {
      toast.error(getErrorMessage(error));
    } finally {
      setSaving(false);
    }
  };

  const addOption = () =>
    setForm((current) => ({
      ...current,
      options: [
        ...current.options,
        {
          option_text: "",
          option_value: `option_${current.options.length + 1}`,
          target_symptom_id: current.question_symptom_ids[0] ?? null,
          answer_effect: "present",
          display_order: current.options.length,
        },
      ],
    }));
  const toggleInitialSymptom = (symptomId: string) =>
    setForm((current) => {
      const exists = current.rules.some(
        (rule) => rule.initial_symptom_id === symptomId,
      );
      const rules = exists
        ? current.rules.filter((rule) => rule.initial_symptom_id !== symptomId)
        : [
            ...current.rules.filter((rule) => rule.initial_symptom_id),
            {
              initial_symptom_id: symptomId,
              question_stage: "local" as const,
              priority: current.rules.length + 1,
              is_required: false,
              status: "1" as const,
            },
          ];
      return {
        ...current,
        rules: rules.map((rule, index) => ({ ...rule, priority: index + 1 })),
      };
    });
  const moveRule = (index: number, direction: -1 | 1) =>
    setForm((current) => {
      const target = index + direction;
      if (target < 0 || target >= current.rules.length) return current;
      const rules = [...current.rules];
      [rules[index], rules[target]] = [rules[target], rules[index]];
      return {
        ...current,
        rules: rules.map((rule, ruleIndex) => ({
          ...rule,
          priority: ruleIndex + 1,
        })),
      };
    });

  return (
    <div>
      <div className="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <h1 className="text-xl font-semibold text-[var(--color-text-primary)]">
          จัดการคำถามประเมินแบบตามคำตอบ
        </h1>
        <Button onClick={() => edit()}>
          <Plus className="h-4 w-4" />
          เพิ่มคำถาม
        </Button>
      </div>
      <FilterBar
        sortKey={sortKey}
        direction={sortDirection}
        dateSortKey="created_at"
        nameSortKey="question"
        onSortChange={(key, direction) => {
          setSortKey(key);
          setSortDirection(direction);
          setPage(1);
        }}
      >
        <AdaptiveQuestionFilters
          value={{ search, answerTypes, status }}
          onChange={(filters: AdaptiveQuestionFilterValue) => {
            setSearch(filters.search);
            setAnswerTypes(filters.answerTypes);
            setStatus(filters.status);
          }}
        />
      </FilterBar>
      <Card className="mt-4 p-0">
        {initialLoading ? (
          <TableSkeleton
            columns={8}
            columnWidths={[
              "w-20",
              "w-64",
              "w-40",
              "w-28",
              "w-28",
              "w-28",
              "w-40",
              "w-10",
            ]}
          />
        ) : (
          <AdaptiveQuestionTable
            data={visibleRows}
            onEdit={edit}
            onDelete={(item) => void remove(item)}
            emptyMessage={
              items.length === 0
                ? "ยังไม่มีคำถามแบบตามคำตอบ ระบบจะใช้วิธีเดิมจนกว่าจะมีคำถามที่อนุมัติ"
                : "ไม่พบคำถามแบบตามคำตอบที่ตรงกับตัวกรอง"
            }
          />
        )}
      </Card>
      {!initialLoading && filteredItems.length > 0 && (
        <Pagination
          className="mt-4"
          current={Math.min(page, lastPage)}
          total={lastPage}
          onChange={setPage}
          pageSize={pageSize}
          onPageSizeChange={setPageSize}
          totalItems={filteredItems.length}
          itemLabel="คำถาม"
        />
      )}

      <Dialog open={open} onOpenChange={setOpen}>
        <DialogContent maxWidth="2xl" className="max-h-[92vh]">
          <DialogHeader>
            <DialogTitle>
              {editing
                ? "แก้ไขคำถามแบบตามคำตอบ"
                : "เพิ่มคำถามแบบตามคำตอบ"}
            </DialogTitle>
          </DialogHeader>
          <div className="space-y-5">
            <div className="grid gap-4 md:grid-cols-2">
              <MultiSelectFilter
                label="อาการที่คำถามตรวจ"
                values={form.question_symptom_ids}
                onChange={(values) =>
                  setForm({ ...form, question_symptom_ids: values })
                }
                options={sortedSymptoms.map((symptom) => ({
                  value: symptom.symptom_id,
                  label: symptom.symptom_name,
                  searchText: symptom.symptom_name_en ?? "",
                }))}
                emptyLabel="เลือกอาการ..."
                searchable
                searchPlaceholder="ค้นหาชื่ออาการ..."
              />
              <SimpleSelect
                label="รูปแบบคำตอบ"
                value={form.answer_type}
                onChange={(value) =>
                  setForm({
                    ...form,
                    answer_type:
                      value as AdaptiveQuestionPayload["answer_type"],
                    options: value === "yes_no_unsure" ? [] : form.options,
                  })
                }
                options={[
                  { value: "yes_no_unsure", label: "ใช่ / ไม่ใช่ / ไม่แน่ใจ" },
                  { value: "single_choice", label: "เลือกหนึ่งข้อ" },
                  { value: "multiple_choice", label: "เลือกหลายข้อ" },
                ]}
              />
            </div>
            <div>
              <Label>ข้อความคำถาม</Label>
              <Textarea
                rows={2}
                value={form.question_text}
                onChange={(event) =>
                  setForm({ ...form, question_text: event.target.value })
                }
              />
            </div>
            <div>
              <Label>คำอธิบายสำหรับผู้ใช้</Label>
              <Textarea
                rows={2}
                value={form.explanation_text}
                onChange={(event) =>
                  setForm({ ...form, explanation_text: event.target.value })
                }
              />
            </div>
            {form.answer_type !== "yes_no_unsure" && (
              <section className="space-y-3 rounded-xl border border-[var(--color-border)] p-4">
                <div className="flex items-center justify-between">
                  <h2 className="font-semibold">ตัวเลือกคำตอบ</h2>
                  <Button variant="outline" size="sm" onClick={addOption}>
                    <Plus className="h-4 w-4" />
                    เพิ่มตัวเลือก
                  </Button>
                </div>
                {form.options.map((option, index) => (
                  <div
                    key={index}
                    className="grid gap-2 rounded-lg bg-[var(--color-surface)] p-3 md:grid-cols-4"
                  >
                    <Input
                      placeholder="ข้อความตัวเลือก"
                      value={option.option_text}
                      onChange={(event) =>
                        setForm({
                          ...form,
                          options: form.options.map((item, i) =>
                            i === index
                              ? { ...item, option_text: event.target.value }
                              : item,
                          ),
                        })
                      }
                    />
                    <SimpleSelect
                      value={option.target_symptom_id ?? ""}
                      onChange={(value) =>
                        setForm({
                          ...form,
                          options: form.options.map((item, i) =>
                            i === index
                              ? { ...item, target_symptom_id: value }
                              : item,
                          ),
                        })
                      }
                      options={symptomOptions}
                      placeholder="อาการที่เป็นผล"
                    />
                    <SimpleSelect
                      value={option.answer_effect}
                      onChange={(value) =>
                        setForm({
                          ...form,
                          options: form.options.map((item, i) =>
                            i === index
                              ? {
                                  ...item,
                                  answer_effect:
                                    value as typeof option.answer_effect,
                                }
                              : item,
                          ),
                        })
                      }
                      options={[
                        { value: "present", label: "พบอาการ" },
                        { value: "absent", label: "ไม่พบอาการ" },
                        { value: "unknown", label: "ไม่แน่ใจ" },
                      ]}
                    />
                    <Button
                      variant="ghost"
                      onClick={() =>
                        setForm({
                          ...form,
                          options: form.options.filter((_, i) => i !== index),
                        })
                      }
                    >
                      <Trash2 className="h-4 w-4" />
                      ลบ
                    </Button>
                  </div>
                ))}
              </section>
            )}
            <section className="space-y-4 rounded-xl border border-[var(--color-border)] p-4">
              <h2 className="font-semibold">ใช้คำถามเมื่อเริ่มจากอาการ</h2>
              <div className="relative">
                <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-[var(--color-text-secondary)]" />
                <Input
                  type="search"
                  value={symptomSearch}
                  onChange={(event) => setSymptomSearch(event.target.value)}
                  placeholder="ค้นหาชื่ออาการ..."
                  className="pl-9 pr-9"
                />
                {symptomSearch && (
                  <button
                    type="button"
                    onClick={() => setSymptomSearch("")}
                    aria-label="ล้างคำค้นหา"
                    className="absolute right-2 top-1/2 flex h-6 w-6 -translate-y-1/2 items-center justify-center rounded-full text-[var(--color-text-secondary)] hover:bg-[var(--color-surface-hover)]"
                  >
                    <X className="h-4 w-4" />
                  </button>
                )}
              </div>
              <div className="max-h-64 overflow-y-auto rounded-lg border border-[var(--color-border)] p-2">
                {visibleInitialSymptoms.map((symptom) => (
                  <label
                    key={symptom.symptom_id}
                    className="flex cursor-pointer items-center gap-3 rounded-md px-3 py-2 hover:bg-[var(--color-surface)]"
                  >
                    <Checkbox
                      checked={form.rules.some(
                        (rule) =>
                          rule.initial_symptom_id === symptom.symptom_id,
                      )}
                      onCheckedChange={() =>
                        toggleInitialSymptom(symptom.symptom_id)
                      }
                    />
                    <span className="min-w-0 text-sm">
                      <span className="block">{symptom.symptom_name}</span>
                      {symptom.symptom_name_en && (
                        <span className="block text-xs text-[var(--color-text-secondary)]">
                          {symptom.symptom_name_en}
                        </span>
                      )}
                    </span>
                  </label>
                ))}
                {visibleInitialSymptoms.length === 0 && (
                  <p className="p-5 text-center text-sm text-[var(--color-text-secondary)]">
                    ไม่พบอาการที่ค้นหา
                  </p>
                )}
              </div>
              {form.rules.length > 0 && (
                <div className="space-y-2">
                  <p className="text-sm font-medium">ลำดับอาการที่เลือก</p>
                  {form.rules.map((rule, index) => {
                    const symptom = symptoms.find(
                      (item) => item.symptom_id === rule.initial_symptom_id,
                    );
                    return (
                      <div
                        key={rule.initial_symptom_id}
                        className="grid items-center gap-2 rounded-lg bg-[var(--color-surface)] p-3 md:grid-cols-[minmax(160px,1fr)_180px_110px_132px]"
                      >
                        <div className="flex min-w-0 items-center gap-2">
                          <span className="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-[var(--color-primary)]/10 text-xs font-semibold text-[var(--color-primary)]">
                            {index + 1}
                          </span>
                          <span className="truncate text-sm font-medium">
                            {symptom?.symptom_name ?? rule.initial_symptom_id}
                          </span>
                        </div>
                        <SimpleSelect
                          value={rule.question_stage}
                          onChange={(value) =>
                            setForm({
                              ...form,
                              rules: form.rules.map((item, i) =>
                                i === index
                                  ? {
                                      ...item,
                                      question_stage:
                                        value as typeof rule.question_stage,
                                    }
                                  : item,
                              ),
                            })
                          }
                          options={[
                            { value: "local", label: "เฉพาะจุด" },
                            { value: "associated", label: "อาการร่วม" },
                            { value: "safety", label: "สัญญาณสำคัญ" },
                          ]}
                        />
                        <label className="flex items-center gap-2 text-sm">
                          <Checkbox
                            checked={rule.is_required}
                            onCheckedChange={(checked) =>
                              setForm({
                                ...form,
                                rules: form.rules.map((item, i) =>
                                  i === index
                                    ? { ...item, is_required: checked === true }
                                    : item,
                                ),
                              })
                            }
                          />
                          ต้องถาม
                        </label>
                        <div className="flex justify-end gap-1">
                          <Button
                            variant="ghost"
                            size="icon"
                            disabled={index === 0}
                            onClick={() => moveRule(index, -1)}
                            aria-label="เลื่อนขึ้น"
                          >
                            <ChevronUp className="h-4 w-4" />
                          </Button>
                          <Button
                            variant="ghost"
                            size="icon"
                            disabled={index === form.rules.length - 1}
                            onClick={() => moveRule(index, 1)}
                            aria-label="เลื่อนลง"
                          >
                            <ChevronDown className="h-4 w-4" />
                          </Button>
                          <Button
                            variant="ghost"
                            size="icon"
                            onClick={() =>
                              toggleInitialSymptom(rule.initial_symptom_id)
                            }
                            aria-label="ลบ"
                          >
                            <Trash2 className="h-4 w-4 text-red-500" />
                          </Button>
                        </div>
                      </div>
                    );
                  })}
                </div>
              )}
            </section>
            <div>
              <Label>แหล่งอ้างอิงหรือผู้ตรวจสอบ</Label>
              <Textarea
                rows={2}
                value={form.evidence_source}
                onChange={(event) =>
                  setForm({ ...form, evidence_source: event.target.value })
                }
              />
            </div>
            <SimpleSelect
              label="สถานะ"
              value={form.status}
              onChange={(value) =>
                setForm({
                  ...form,
                  status: value as AdaptiveQuestionPayload["status"],
                })
              }
              options={[
                { value: "draft", label: "ฉบับร่าง" },
                { value: "reviewed", label: "ตรวจแหล่งอ้างอิงแล้ว — รอผู้เชี่ยวชาญ" },
                { value: "approved", label: "ผู้เชี่ยวชาญอนุมัติให้ระบบใช้" },
                { value: "inactive", label: "ปิดใช้งาน" },
              ]}
            />
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setOpen(false)}>
              ยกเลิก
            </Button>
            <Button loading={saving} onClick={() => void save()}>
              บันทึก
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}
