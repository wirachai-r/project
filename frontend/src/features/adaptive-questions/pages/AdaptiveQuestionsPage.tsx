import { useEffect, useMemo, useState } from "react";
import {
  AlertTriangle,
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
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from "@/components/ui/AlertDialog";
import { adaptiveQuestionApi } from "@/lib/api/adaptiveQuestion";
import { symptomApi } from "@/lib/api/symptom";
import { fuzzyIncludes } from "@/lib/fuzzySearch";
import { getErrorMessage } from "@/lib/getErrorMessage";
import type {
  AdaptiveQuestion,
  AdaptiveQuestionPayload,
  AdaptiveQuestionStage,
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

interface GroupQuestionForm {
  adaptive_question_id: number;
  question_stage: AdaptiveQuestionStage;
  is_required: boolean;
}

export function AdaptiveQuestionsPage() {
  const [items, setItems] = useState<AdaptiveQuestion[]>([]);
  const [symptoms, setSymptoms] = useState<Symptom[]>([]);
  const [form, setForm] = useState<AdaptiveQuestionPayload>(emptyForm());
  const [editing, setEditing] = useState<AdaptiveQuestion | null>(null);
  const [open, setOpen] = useState(false);
  const [initialLoading, setInitialLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [groupOpen, setGroupOpen] = useState(false);
  const [groupSaving, setGroupSaving] = useState(false);
  const [groupSymptomId, setGroupSymptomId] = useState("");
  const [groupSymptomLocked, setGroupSymptomLocked] = useState(false);
  const [groupQuestionSearch, setGroupQuestionSearch] = useState("");
  const [groupQuestions, setGroupQuestions] = useState<GroupQuestionForm[]>([]);
  const [toggleTarget, setToggleTarget] = useState<AdaptiveQuestion | null>(null);
  const [toggling, setToggling] = useState(false);
  const [search, setSearch] = useState("");
  const [symptomIds, setSymptomIds] = useState<string[]>([]);
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
  useEffect(
    () => setPage(1),
    [search, symptomIds, status, pageSize],
  );

  const sortedSymptoms = [...symptoms].sort((a, b) =>
    a.symptom_name.localeCompare(b.symptom_name, "th"),
  );
  const symptomOptions = sortedSymptoms.map((item) => ({
    value: item.symptom_id,
    label: item.symptom_name,
  }));
  const selectedQuestionSymptomId = form.question_symptom_ids[0];
  const existingQuestionsForSelectedSymptom = selectedQuestionSymptomId
    ? items.filter((item) => {
        if (item.id === editing?.id) return false;
        const questionSymptoms =
          item.symptoms && item.symptoms.length > 0
            ? item.symptoms
            : item.symptom
              ? [item.symptom]
              : [];
        return questionSymptoms.some(
          (symptom) => symptom.symptom_id === selectedQuestionSymptomId,
        );
      })
    : [];
  const selectedQuestionSymptom = symptoms.find(
    (symptom) => symptom.symptom_id === selectedQuestionSymptomId,
  );
  const filteredItems = useMemo(() => {
    const filtered = items.filter(
      (item) =>
        fuzzyIncludes(
          `${item.question_text} ${item.explanation_text ?? ""} ${item.symptom?.symptom_name ?? ""}`,
          search,
        ) &&
        (symptomIds.length === 0 ||
          symptomIds.includes(item.symptom?.symptom_id ?? "")) &&
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
  }, [symptomIds, items, search, sortDirection, sortKey, status]);
  const lastPage = Math.max(1, Math.ceil(filteredItems.length / pageSize));
  const visibleRows: AdaptiveQuestionRow[] = filteredItems
    .slice((page - 1) * pageSize, page * pageSize)
    .map((item, index) => {
      const ownerSymptomId =
        item.symptom?.symptom_id ?? item.symptoms?.[0]?.symptom_id;
      const followUpSymptoms = ownerSymptomId
        ? items
            .flatMap((question) =>
              question.rules
                .filter((rule) => rule.initial_symptom_id === ownerSymptomId)
                .map((rule) => ({
                  symptomName:
                    question.symptom?.symptom_name ??
                    question.symptoms?.[0]?.symptom_name ??
                    question.question_text,
                  priority: rule.priority,
                })),
            )
            .sort((left, right) => left.priority - right.priority)
            .map(({ symptomName }) => symptomName)
        : [];

      return {
        ...item,
        rowNumber: (page - 1) * pageSize + index + 1,
        followUpSymptoms,
      };
    });
  const remove = async (item: AdaptiveQuestion) => {
    if (!confirm(`ยืนยันการลบคำถาม “${item.question_text}”?`)) return;
    try {
      await adaptiveQuestionApi.delete(item.id);
      toast.success("ลบคำถามประเมินตามอาการแล้ว");
      setItems((current) => current.filter((question) => question.id !== item.id));
    } catch (error) {
      toast.error(getErrorMessage(error));
    }
  };

  const toggleStatus = async () => {
    if (!toggleTarget) return;
    setToggling(true);
    try {
      const nextStatus =
        toggleTarget.status === "approved" ? "inactive" : "approved";
      const updatedQuestion = await adaptiveQuestionApi.update(toggleTarget.id, {
        question_symptom_ids:
          toggleTarget.symptoms?.slice(0, 1).map((symptom) => symptom.symptom_id) ??
          (toggleTarget.symptom ? [toggleTarget.symptom.symptom_id] : []),
        question_text: toggleTarget.question_text,
        explanation_text: toggleTarget.explanation_text ?? "",
        answer_type: toggleTarget.answer_type,
        status: nextStatus,
        evidence_source: toggleTarget.evidence_source ?? "",
        options: toggleTarget.options ?? [],
        rules: toggleTarget.rules ?? [],
      });
      toast.success(
        nextStatus === "approved"
          ? "เปิดใช้งานคำถามสำเร็จ"
          : "ปิดใช้งานคำถามสำเร็จ",
      );
      setItems((current) =>
        current.map((question) =>
          question.id === updatedQuestion.id ? updatedQuestion : question,
        ),
      );
      setToggleTarget(null);
    } catch (error) {
      toast.error(getErrorMessage(error));
    } finally {
      setToggling(false);
    }
  };

  const edit = (item?: AdaptiveQuestion) => {
    setEditing(item ?? null);
    setForm(
      item
        ? {
            question_symptom_ids:
              item.symptoms?.slice(0, 1).map((symptom) => symptom.symptom_id) ??
              (item.symptom ? [item.symptom.symptom_id] : []),
            question_text: item.question_text,
            explanation_text: item.explanation_text ?? "",
            answer_type: "yes_no_unsure",
            status: item.status === "reviewed" ? "draft" : item.status,
            evidence_source: item.evidence_source ?? "",
            options: [],
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
      form.question_symptom_ids.length === 0
    ) {
      toast.error("กรุณากรอกคำถามและอาการเจ้าของคำถามให้ครบ");
      return;
    }
    if (existingQuestionsForSelectedSymptom.length > 0) {
      toast.error("อาการนี้มีคำถามประเมินอยู่แล้ว กรุณาเลือกอาการอื่น");
      return;
    }
    setSaving(true);
    try {
      const payload = {
        ...form,
        answer_type: "yes_no_unsure" as const,
        options: [],
        rules: form.rules.map((rule, index) => ({
          ...rule,
          priority: index + 1,
        })),
      };
      const savedQuestion = editing
        ? await adaptiveQuestionApi.update(editing.id, payload)
        : await adaptiveQuestionApi.create(payload);
      setItems((current) =>
        editing
          ? current.map((question) =>
              question.id === savedQuestion.id ? savedQuestion : question,
            )
          : [savedQuestion, ...current],
      );
      toast.success("บันทึกคำถามประเมินตามอาการแล้ว");
      setOpen(false);
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
  const moveOption = (index: number, direction: -1 | 1) =>
    setForm((current) => {
      const target = index + direction;
      if (target < 0 || target >= current.options.length) return current;
      const options = [...current.options];
      [options[index], options[target]] = [options[target], options[index]];
      return {
        ...current,
        options: options.map((option, optionIndex) => ({
          ...option,
          display_order: optionIndex,
        })),
      };
    });
  const selectGroupSymptom = (symptomId: string) => {
    setGroupQuestionSearch("");
    setGroupSymptomId(symptomId);
    setGroupQuestions(
      items
        .flatMap((question) =>
          question.rules
            .filter((rule) => rule.initial_symptom_id === symptomId)
            .map((rule) => ({
              adaptive_question_id: question.id,
              question_stage: rule.question_stage,
              is_required: rule.is_required,
              priority: rule.priority,
            })),
        )
        .sort((left, right) => left.priority - right.priority)
        .map(({ adaptive_question_id, question_stage, is_required }) => ({
          adaptive_question_id,
          question_stage,
          is_required,
        })),
    );
  };
  const openGroupForQuestion = (question: AdaptiveQuestion) => {
    const symptomId =
      question.symptom?.symptom_id ?? question.symptoms?.[0]?.symptom_id ?? "";
    selectGroupSymptom(symptomId);
    setGroupSymptomLocked(true);
    setGroupOpen(true);
  };
  const setGroupQuestionIds = (questionIds: string[]) =>
    setGroupQuestions((current) =>
      questionIds.map((questionId) =>
        current.find(
          (item) => item.adaptive_question_id === Number(questionId),
        ) ?? {
          adaptive_question_id: Number(questionId),
          question_stage: "associated",
          is_required: false,
        },
      ),
    );
  const moveGroupQuestion = (index: number, direction: -1 | 1) =>
    setGroupQuestions((current) => {
      const target = index + direction;
      if (target < 0 || target >= current.length) return current;
      const questions = [...current];
      [questions[index], questions[target]] = [questions[target], questions[index]];
      return questions;
    });
  const saveGroup = async () => {
    if (!groupSymptomId) {
      toast.error("กรุณาเลือกอาการเริ่มต้น");
      return;
    }
    setGroupSaving(true);
    try {
      await adaptiveQuestionApi.syncGroup(groupSymptomId, groupQuestions);
      await load();
      toast.success("บันทึกกลุ่มคำถามแล้ว");
      setGroupOpen(false);
    } catch (error) {
      toast.error(getErrorMessage(error));
    } finally {
      setGroupSaving(false);
    }
  };
  return (
    <div>
      <div className="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <h1 className="text-xl font-semibold text-[var(--color-text-primary)]">
          จัดการคำถามประเมินตามอาการ
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
          value={{ search, symptomIds, status }}
          symptomOptions={sortedSymptoms.map((symptom) => ({
            value: symptom.symptom_id,
            label: symptom.symptom_name,
          }))}
          onChange={(filters: AdaptiveQuestionFilterValue) => {
            setSearch(filters.search);
            setSymptomIds(filters.symptomIds);
            setStatus(filters.status);
          }}
        />
      </FilterBar>
      <Card className="mt-4 p-0">
        {initialLoading ? (
          <TableSkeleton
            columns={5}
            rows={5}
            columnWidths={[
              "w-20",
              "w-64",
              "w-40",
              "w-28",
              "w-10",
            ]}
          />
        ) : (
          <AdaptiveQuestionTable
            data={visibleRows}
            onEdit={edit}
            onManageGroup={openGroupForQuestion}
            onToggleStatus={setToggleTarget}
            onDelete={(item) => void remove(item)}
            emptyMessage={
              items.length === 0
                ? "ยังไม่มีคำถามประเมินตามอาการ ระบบจะใช้วิธีเดิมจนกว่าจะมีคำถามที่อนุมัติ"
                : "ไม่พบคำถามประเมินตามอาการที่ตรงกับตัวกรอง"
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

      <AlertDialog
        open={!!toggleTarget}
        onOpenChange={(nextOpen) => !nextOpen && setToggleTarget(null)}
      >
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>
              {toggleTarget?.status === "approved"
                ? "ยืนยันการปิดใช้งานคำถาม"
                : "ยืนยันการเปิดใช้งานคำถาม"}
            </AlertDialogTitle>
            <AlertDialogDescription>
              {toggleTarget?.status === "approved"
                ? "คำถามนี้จะไม่ถูกใช้ในการประเมินตามอาการ"
                : "คำถามนี้จะถูกนำไปใช้ในการประเมินตามอาการ"}
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel disabled={toggling}>ยกเลิก</AlertDialogCancel>
            <AlertDialogAction onClick={() => void toggleStatus()} loading={toggling}>
              ยืนยัน
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>

      <Dialog open={groupOpen} onOpenChange={setGroupOpen}>
        <DialogContent maxWidth="2xl" className="max-h-[92vh] w-full">
          <DialogHeader>
            <DialogTitle>จัดกลุ่มคำถามตามอาการเริ่มต้น</DialogTitle>
          </DialogHeader>
          <div className="space-y-5">
            <section className="space-y-4 rounded-xl border border-[var(--color-border)] p-4">
              <div className="space-y-1">
                <h2 className="font-semibold">เลือกอาการเริ่มต้น</h2>
                <p className="text-sm text-[var(--color-text-secondary)]">
                  ข้อที่เลือก “บังคับถาม” จะถูกถามตามลำดับก่อน ส่วนข้ออื่นระบบจะเลือกจากกลุ่มและหมวดอาการตามความเหมาะสม
                </p>
              </div>
              <SimpleSelect
                label="อาการเริ่มต้น"
                value={groupSymptomId}
                onChange={selectGroupSymptom}
                options={symptomOptions}
                placeholder="เลือกอาการเริ่มต้น..."
                disabled={groupSymptomLocked}
              />
              {groupSymptomId && (
                <div className="space-y-2">
                  <Label>อาการที่ต้องการถามต่อ</Label>
                  <div className="relative">
                    <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-[var(--color-text-secondary)]" />
                    <Input
                      type="search"
                      value={groupQuestionSearch}
                      onChange={(event) => setGroupQuestionSearch(event.target.value)}
                      placeholder="ค้นหาชื่ออาการ..."
                      className="pl-9 pr-9"
                    />
                    {groupQuestionSearch && (
                      <button
                        type="button"
                        onClick={() => setGroupQuestionSearch("")}
                        aria-label="ล้างคำค้นหา"
                        className="absolute right-2 top-1/2 flex h-6 w-6 -translate-y-1/2 items-center justify-center rounded-full text-[var(--color-text-secondary)] hover:bg-[var(--color-surface-hover)]"
                      >
                        <X className="h-4 w-4" />
                      </button>
                    )}
                  </div>
                  <div className="max-h-64 overflow-y-auto rounded-lg border border-[var(--color-border)] p-2">
                    {items
                      .filter(
                        (question) =>
                          (question.symptom?.symptom_id ??
                            question.symptoms?.[0]?.symptom_id) !==
                            groupSymptomId &&
                          fuzzyIncludes(
                            `${question.symptom?.symptom_name ?? question.symptoms?.[0]?.symptom_name ?? ""} ${question.question_text}`,
                            groupQuestionSearch,
                          ),
                      )
                      .sort((left, right) => {
                        const leftName =
                          left.symptom?.symptom_name ??
                          left.symptoms?.[0]?.symptom_name ??
                          left.question_text;
                        const rightName =
                          right.symptom?.symptom_name ??
                          right.symptoms?.[0]?.symptom_name ??
                          right.question_text;

                        return leftName.localeCompare(rightName, "th");
                      })
                      .map((question) => {
                        const selectedIds = groupQuestions.map((item) =>
                          String(item.adaptive_question_id),
                        );
                        const questionId = String(question.id);
                        const checked = selectedIds.includes(questionId);
                        return (
                          <label
                            key={question.id}
                            className="flex cursor-pointer items-center gap-3 rounded-md px-3 py-2 hover:bg-[var(--color-surface)]"
                          >
                            <Checkbox
                              checked={checked}
                              onCheckedChange={() =>
                                setGroupQuestionIds(
                                  checked
                                    ? selectedIds.filter((id) => id !== questionId)
                                    : [...selectedIds, questionId],
                                )
                              }
                            />
                            <span className="text-sm font-medium">
                              {question.symptom?.symptom_name ??
                                question.symptoms?.[0]?.symptom_name ??
                                question.question_text}
                            </span>
                          </label>
                        );
                      })}
                  </div>
                </div>
              )}
            </section>
            {groupQuestions.length > 0 && (
              <section className="space-y-3 rounded-xl border border-[var(--color-border)] p-4">
                <div>
                  <h2 className="font-semibold">ลำดับคำถามภายในกลุ่ม</h2>
                  <p className="mt-1 text-sm text-[var(--color-text-secondary)]">
                    เรียงคำถามจากบนลงล่าง และกำหนดช่วงของคำถามแต่ละข้อ
                  </p>
                </div>
                {groupQuestions.map((groupQuestion, index) => {
                  const question = items.find(
                    (item) => item.id === groupQuestion.adaptive_question_id,
                  );
                  return (
                    <div
                      key={groupQuestion.adaptive_question_id}
                      className="grid items-center gap-3 rounded-lg bg-[var(--color-surface)] p-3 md:grid-cols-[minmax(260px,1fr)_180px_110px_132px]"
                    >
                      <div className="flex min-w-0 gap-3">
                        <span className="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-[var(--color-primary)]/10 text-xs font-semibold text-[var(--color-primary)]">
                          {index + 1}
                        </span>
                        <div className="min-w-0">
                          <p className="truncate text-sm font-medium">
                            {question?.symptom?.symptom_name ??
                              question?.symptoms?.[0]?.symptom_name ?? "-"}
                          </p>
                          <p className="line-clamp-1 text-xs text-[var(--color-text-secondary)]">
                            {question?.question_text}
                          </p>
                        </div>
                      </div>
                      <SimpleSelect
                        value={groupQuestion.question_stage}
                        onChange={(value) =>
                          setGroupQuestions((current) =>
                            current.map((item, itemIndex) =>
                              itemIndex === index
                                ? {
                                    ...item,
                                    question_stage: value as AdaptiveQuestionStage,
                                  }
                                : item,
                            ),
                          )
                        }
                        options={[
                          { value: "local", label: "เฉพาะจุด" },
                          { value: "associated", label: "อาการร่วม" },
                          { value: "safety", label: "สัญญาณสำคัญ" },
                        ]}
                      />
                      <label className="flex items-center gap-2 text-sm">
                        <Checkbox
                          checked={groupQuestion.is_required}
                          onCheckedChange={(checked) =>
                            setGroupQuestions((current) =>
                              current.map((item, itemIndex) =>
                                itemIndex === index
                                  ? { ...item, is_required: checked === true }
                                  : item,
                              ),
                            )
                          }
                        />
                        บังคับถาม
                      </label>
                      <div className="flex justify-end gap-1">
                        <Button
                          variant="ghost"
                          size="icon"
                          disabled={index === 0}
                          onClick={() => moveGroupQuestion(index, -1)}
                          aria-label="เลื่อนขึ้น"
                        >
                          <ChevronUp className="h-4 w-4" />
                        </Button>
                        <Button
                          variant="ghost"
                          size="icon"
                          disabled={index === groupQuestions.length - 1}
                          onClick={() => moveGroupQuestion(index, 1)}
                          aria-label="เลื่อนลง"
                        >
                          <ChevronDown className="h-4 w-4" />
                        </Button>
                        <Button
                          variant="ghost"
                          size="icon"
                          onClick={() =>
                            setGroupQuestions((current) =>
                              current.filter((_, itemIndex) => itemIndex !== index),
                            )
                          }
                          aria-label="นำคำถามออกจากกลุ่ม"
                          title="นำคำถามออกจากกลุ่ม"
                          className="text-[var(--color-danger)] hover:bg-red-50 hover:text-[var(--color-danger)]"
                        >
                          <Trash2 className="h-4 w-4" />
                        </Button>
                      </div>
                    </div>
                  );
                })}
              </section>
            )}
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setGroupOpen(false)}>
              ยกเลิก
            </Button>
            <Button loading={groupSaving} onClick={() => void saveGroup()}>
              บันทึกกลุ่ม
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      <Dialog open={open} onOpenChange={setOpen}>
        <DialogContent maxWidth="2xl" className="max-h-[92vh] w-full">
          <DialogHeader>
            <DialogTitle>
              {editing
                ? "แก้ไขคำถามประเมินตามอาการ"
                : "เพิ่มคำถามประเมินตามอาการ"}
            </DialogTitle>
          </DialogHeader>
          <div className="space-y-5">
            <section className="space-y-4 rounded-xl border border-[var(--color-border)] p-4">
              <div className="space-y-1">
                <h2 className="font-semibold">อาการและคำถามประจำอาการ</h2>
                <p className="text-sm text-[var(--color-text-secondary)]">
                  อาการหนึ่งรายการมีคำถามได้หนึ่งข้อ เมื่อผู้ใช้เริ่มจากอาการนี้ ระบบจะถามคำถามข้อนี้ก่อน
                  และอาจถามอาการอื่นที่เกี่ยวข้องต่อโดยอัตโนมัติ
                </p>
              </div>
              <div className="w-full">
                <MultiSelectFilter
                  label="อาการเจ้าของคำถาม"
                  values={form.question_symptom_ids}
                  onChange={(values) =>
                    setForm({
                      ...form,
                      question_symptom_ids: values.slice(0, 1),
                    })
                  }
                  options={sortedSymptoms.map((symptom) => ({
                    value: symptom.symptom_id,
                    label: symptom.symptom_name,
                    searchText: symptom.symptom_name_en ?? "",
                  }))}
                  emptyLabel="เลือกอาการ..."
                  searchable
                  searchPlaceholder="ค้นหาชื่ออาการ..."
                  maxSelections={1}
                />
              </div>
              {existingQuestionsForSelectedSymptom.length > 0 && (
                <div
                  role="alert"
                  className="flex gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900"
                >
                  <AlertTriangle className="mt-0.5 h-4 w-4 shrink-0" />
                  <div>
                    <p className="font-medium">
                      อาการ “{selectedQuestionSymptom?.symptom_name ?? selectedQuestionSymptomId}”
                      มีคำถามอยู่แล้ว {existingQuestionsForSelectedSymptom.length} ข้อ
                    </p>
                    <p className="mt-0.5 text-xs text-red-800">
                      อาการหนึ่งรายการมีคำถามประเมินได้เพียงหนึ่งข้อ กรุณาเลือกอาการอื่น
                    </p>
                  </div>
                </div>
              )}
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
            </section>
            {form.answer_type !== "yes_no_unsure" && (
              <section className="space-y-4 rounded-xl border border-[var(--color-border)] p-4 sm:p-5">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                  <div>
                    <h2 className="font-semibold">ตัวเลือกคำตอบ</h2>
                    <p className="mt-1 text-xs text-[var(--color-text-secondary)]">
                      กำหนดข้อความ อาการที่เป็นผล และความหมายของแต่ละตัวเลือก
                    </p>
                  </div>
                  <Button variant="outline" size="sm" onClick={addOption}>
                    <Plus className="h-4 w-4" />
                    เพิ่มตัวเลือก
                  </Button>
                </div>
                {form.options.length === 0 ? (
                  <div className="rounded-lg border border-dashed border-[var(--color-border)] px-4 py-8 text-center text-sm text-[var(--color-text-secondary)]">
                    ยังไม่มีตัวเลือก กด “เพิ่มตัวเลือก” เพื่อเริ่มกำหนดคำตอบ
                  </div>
                ) : (
                  <div className="max-h-[380px] space-y-3 overflow-y-auto pr-1">
                    {form.options.map((option, index) => (
                      <div
                        key={index}
                        className="rounded-xl border border-[var(--color-border)] bg-white p-3 shadow-sm sm:p-4"
                      >
                        <div className="grid gap-3 lg:grid-cols-[minmax(220px,1.2fr)_minmax(220px,1fr)_minmax(180px,0.8fr)_112px] lg:items-end">
                          <div className="min-w-0">
                            <Label>ข้อความตัวเลือก {index + 1}</Label>
                            <Input
                              placeholder="กรอกข้อความที่ผู้ใช้จะเห็น"
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
                          </div>
                          <SimpleSelect
                            label="อาการที่เป็นผล"
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
                            placeholder="เลือกอาการ"
                          />
                          <SimpleSelect
                            label="ผลของคำตอบ"
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
                          <div className="flex items-center justify-end gap-1 lg:pb-0.5">
                            <Button
                              variant="ghost"
                              size="icon"
                              disabled={index === 0}
                              onClick={() => moveOption(index, -1)}
                              aria-label={`เลื่อนตัวเลือก ${index + 1} ขึ้น`}
                            >
                              <ChevronUp className="h-4 w-4" />
                            </Button>
                            <Button
                              variant="ghost"
                              size="icon"
                              disabled={index === form.options.length - 1}
                              onClick={() => moveOption(index, 1)}
                              aria-label={`เลื่อนตัวเลือก ${index + 1} ลง`}
                            >
                              <ChevronDown className="h-4 w-4" />
                            </Button>
                            <Button
                              variant="ghost"
                              size="icon"
                              className="text-red-600"
                              onClick={() =>
                                setForm({
                                  ...form,
                                  options: form.options.filter(
                                    (_, i) => i !== index,
                                  ),
                                })
                              }
                              aria-label={`ลบตัวเลือก ${index + 1}`}
                            >
                              <Trash2 className="h-4 w-4" />
                            </Button>
                          </div>
                        </div>
                      </div>
                    ))}
                  </div>
                )}
              </section>
            )}
            <section className="space-y-4 rounded-xl border border-[var(--color-border)] p-4">
              <div className="space-y-1">
                <h2 className="font-semibold">สถานะการใช้งาน</h2>
              </div>
              <div className="w-full">
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
                    { value: "approved", label: "อนุมัติให้ระบบใช้" },
                    { value: "inactive", label: "ปิดใช้งาน" },
                  ]}
                />
              </div>
            </section>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setOpen(false)}>
              ยกเลิก
            </Button>
            <Button
              loading={saving}
              disabled={existingQuestionsForSelectedSymptom.length > 0}
              onClick={() => void save()}
            >
              บันทึก
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}
