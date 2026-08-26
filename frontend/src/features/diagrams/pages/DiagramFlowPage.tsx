import { useCallback, useEffect, useMemo, useState } from "react";
import { useParams, useNavigate } from "react-router-dom";
import axios from "axios";
import { toast } from "sonner";
import { ArrowLeft, RefreshCw, Plus } from "lucide-react";
import { diagramApi } from "@/lib/api/diagram";
import { diagnosisRuleApi } from "@/lib/api/diagnosisRule";
import { questionBoxApi } from "@/lib/api/questionBox";
import { decodeId } from "@/lib/idCodec";
import type { Diagram } from "@/types/diagram";
import type { QuestionBox } from "@/types/questionBox";
import type { AnswerChoice } from "@/types/answerChoice";
import type { DiagnosisRule } from "@/types/diagnosisRule";
import { Button } from "@/components/ui/Button";
import { Card } from "@/components/ui/Card";
import { Spinner } from "@/components/ui/Spinner";
import { isNegativeChoice, type PathCondition } from "../components/flow/flowTree";
import { buildGuidebookNumbers, FlowCanvas } from "../components/flow/FlowCanvas";
import { QuestionBoxEditorDialog } from "../components/flow/QuestionBoxEditorDialog";
import { RuleEditorDialog } from "../components/flow/RuleEditorDialog";
import { answerChoiceApi } from "@/lib/api/answerChoice";
import { useBreadcrumb } from "@/hooks/useBreadcrumb";

function getApiErrorMessage(error: unknown, fallback: string) {
  if (!axios.isAxiosError(error)) return error instanceof Error ? error.message : fallback;
  const data = error.response?.data as { message?: string; errors?: Record<string, string[]> } | undefined;
  const firstValidationError = data?.errors
    ? Object.values(data.errors).flat().find(Boolean)
    : undefined;
  return firstValidationError ?? data?.message ?? fallback;
}

export function DiagramFlowPage() {
  const { diagramId: encodedId } = useParams();
  const navigate = useNavigate();
  const diagramId = encodedId ? decodeId(encodedId, 5) : null;

  const [diagram, setDiagram] = useState<Diagram | null>(null);
  const [boxes, setBoxes] = useState<QuestionBox[]>([]);
  const [rules, setRules] = useState<DiagnosisRule[]>([]);
  const [loading, setLoading] = useState(true);

  const [editorOpen, setEditorOpen] = useState(false);
  const [editorBox, setEditorBox] = useState<QuestionBox | null>(null);

  const [ruleDialogOpen, setRuleDialogOpen] = useState(false);
  const [rulePathConditions, setRulePathConditions] = useState<PathCondition[]>(
    [],
  );
  const [ruleExisting, setRuleExisting] = useState<DiagnosisRule[]>([]);
  const [ruleThresholdOutcome, setRuleThresholdOutcome] = useState<"yes" | "no" | null>(null);

  const load = useCallback(
    async (signal?: AbortSignal) => {
      if (!diagramId) return;
      const [diagramRes, boxesRes, rulesRes] = await Promise.all([
        diagramApi.show(diagramId, signal),
        questionBoxApi.list(diagramId, { per_page: 200 }, signal),
        diagnosisRuleApi.list({ diagram_id: diagramId, per_page: 200 }, signal),
      ]);
      setDiagram(diagramRes);
      setBoxes(boxesRes.data);
      setRules(rulesRes.data);
    },
    [diagramId],
  );

  useEffect(() => {
    if (!diagramId) {
      toast.error("ไม่พบรหัสแผนภูมิที่ระบุ (URL ไม่ถูกต้อง)");
      setLoading(false);
      return;
    }
    const controller = new AbortController();
    setLoading(true);
    load(controller.signal)
      .catch(() => {
        if (!controller.signal.aborted) toast.error("ไม่สามารถโหลดผังงานได้");
      })
      .finally(() => {
        if (!controller.signal.aborted) setLoading(false);
      });
    return () => controller.abort();
  }, [diagramId, load]);

  const refetch = useCallback(async () => {
    setLoading(true);
    try {
      await load();
    } catch {
      toast.error("ไม่สามารถโหลดผังงานได้");
    } finally {
      setLoading(false);
    }
  }, [load]);

  // ใช้หลังบันทึกข้อมูล: อัปเดตผังโดยไม่ถอด Canvas และ Dialog ออกจาก DOM
  const refreshFlowData = useCallback(async () => {
    try {
      await load();
    } catch {
      toast.error("ไม่สามารถอัปเดตข้อมูลผังงานได้");
    }
  }, [load]);

  const boxMap = useMemo(() => {
    const map = new Map<string, QuestionBox>();
    boxes.forEach((b) => map.set(b.box_id, b));
    return map;
  }, [boxes]);

  const ruleMap = useMemo(() => {
    const map = new Map<string, DiagnosisRule[]>();
    rules.forEach((rule) => {
      if (rule.threshold_outcome) return;
      rule.conditions?.forEach((cond) => {
        const key = `${cond.box_id}__${cond.choice_id}`;
        if (!map.has(key)) map.set(key, []);
        map.get(key)!.push(rule);
      });
    });
    return map;
  }, [rules]);

  const thresholdRuleMap = useMemo(() => {
    const map = new Map<string, DiagnosisRule[]>();
    rules.forEach((rule) => {
      if (!rule.threshold_outcome || !rule.threshold_box_id) return;
      const key = `${rule.threshold_box_id}__${rule.threshold_outcome}`;
      map.set(key, [...(map.get(key) ?? []), rule]);
    });
    return map;
  }, [rules]);

  const guidebookFrameNumbers = useMemo(
    () => buildGuidebookNumbers(boxes, diagram?.entry_box_id ?? null),
    [boxes, diagram?.entry_box_id],
  );

  useEffect(() => {
    if (!diagramId || !diagram?.entry_box_id) return;
    const updates = boxes.filter((box) => {
      const computed = guidebookFrameNumbers.get(box.box_id);
      return computed && /^\d+(?:\.\d+)*$/.test(computed) && box.frame_number !== computed;
    });
    if (updates.length === 0) return;

    void Promise.allSettled(
      updates.map((box) => questionBoxApi.update(diagramId, box.box_id, {
        frame_number: guidebookFrameNumbers.get(box.box_id)!,
      })),
    );
  }, [boxes, diagram?.entry_box_id, diagramId, guidebookFrameNumbers]);

  async function handleQuickAddChoice(boxId: string, choiceText: string) {
    const box = boxes.find((item) => item.box_id === boxId);
    const nextOrder = Math.max(0, ...(box?.choices ?? []).map((choice) => choice.order)) + 1;
    try {
      await answerChoiceApi.create(boxId, {
        choice_text: choiceText,
        order: nextOrder,
        status: "1",
      });
      toast.success("เพิ่มตัวเลือกสำเร็จ");
      await load();
    } catch {
      toast.error("เพิ่มตัวเลือกไม่สำเร็จ กรุณาลองใหม่");
    }
  }

  const updateNavigation = useCallback(async (
    boxId: string,
    handleId: string,
    targetBoxId: string | null,
  ) => {
    if (!diagramId) return;
    const box = boxes.find((item) => item.box_id === boxId);
    if (!box) throw new Error("ไม่พบกล่องคำถามต้นทาง");

    if (box.question_type === "M") {
      if (handleId !== "yes" && handleId !== "no") {
        throw new Error("คำถามแบบหลายตัวเลือกเชื่อมได้เฉพาะผลถึงเกณฑ์/ไม่ถึงเกณฑ์");
      }
      await questionBoxApi.update(diagramId, boxId, {
        [handleId === "yes" ? "yes_next_box_id" : "no_next_box_id"]: targetBoxId,
      });
      return;
    }

    if (!handleId.startsWith("choice:")) throw new Error("ไม่พบตัวเลือกต้นทาง");
    await answerChoiceApi.update(boxId, handleId.slice(7), {
      next_box_id: targetBoxId,
      next_diagram_id: null,
    });
  }, [boxes, diagramId]);

  const handleConnect = useCallback(async (boxId: string, handleId: string, targetBoxId: string) => {
    try {
      await updateNavigation(boxId, handleId, targetBoxId);
      toast.success("เชื่อมเส้นทางสำเร็จ");
      await load();
    } catch (error) {
      toast.error(getApiErrorMessage(error, "เชื่อมเส้นทางไม่สำเร็จ"));
    }
  }, [load, updateNavigation]);

  const handleDisconnect = useCallback(async (boxId: string, handleId: string) => {
    try {
      await updateNavigation(boxId, handleId, null);
      toast.success("ยกเลิกการเชื่อมเส้นทางสำเร็จ");
      await load();
    } catch (error) {
      toast.error(getApiErrorMessage(error, "ยกเลิกเส้นทางไม่สำเร็จ"));
      await load();
    }
  }, [load, updateNavigation]);

  async function handleQuickAddNextBox(
    boxId: string,
    handleId: string,
    questionText: string,
  ) {
    if (!diagramId) return;
    try {
      const parentBox = boxes.find((item) => item.box_id === boxId);
      const parentFrame = guidebookFrameNumbers.get(boxId);
      let nextFrameNumber: string | undefined;
      if (parentBox && parentFrame) {
        const parts = parentFrame.split(".").map(Number);
        const isNegative = parentBox.question_type === "M"
          ? handleId === "no"
          : parentBox.choices?.some(
              (choice) => `choice:${choice.choice_id}` === handleId && isNegativeChoice(choice),
            ) ?? false;

        if (isNegative) {
          const nextSibling = [...parts];
          nextSibling[nextSibling.length - 1] += 1;
          nextFrameNumber = nextSibling.join(".");
        } else {
          nextFrameNumber = `${parentFrame}.1`;
        }
      }

      const newBox = await questionBoxApi.create(diagramId, {
        question_text: questionText,
        frame_number: nextFrameNumber,
      });
      await updateNavigation(boxId, handleId, newBox.box_id);
      toast.success("สร้างคำถามใหม่และเชื่อมลูกศรสำเร็จ");
      await load();
    } catch (error) {
      toast.error(getApiErrorMessage(error, "สร้างคำถามถัดไปไม่สำเร็จ กรุณาลองใหม่"));
    }
  }

  function openEditor(box: QuestionBox) {
    setEditorBox(box);
    setEditorOpen(true);
  }

  function openCreateEntry() {
    setEditorBox(null);
    setEditorOpen(true);
  }

  function openRuleEditor(
    _choice: AnswerChoice,
    path: PathCondition[],
    existingRules: DiagnosisRule[],
  ) {
    setRuleThresholdOutcome(null);
    setRulePathConditions(path);
    setRuleExisting(existingRules);
    setRuleDialogOpen(true);
  }

  async function handleSaved(savedBox?: QuestionBox) {
    if (!diagramId) return;
    if (!editorBox && !diagram?.entry_box_id && savedBox) {
      try {
        await diagramApi.update(diagramId, { entry_box_id: savedBox.box_id });
      } catch {
        toast.error("บันทึกไม่สำเร็จ กรุณาลองใหม่");
      }
    }
    await refreshFlowData();
  }

  function openThresholdRuleEditor(
    box: QuestionBox,
    outcome: "yes" | "no",
    existingRules: DiagnosisRule[],
  ) {
    const markerChoice = box.choices?.find((choice) => choice.status === "1");
    if (!markerChoice) {
      toast.error("กรุณาเพิ่มรายการอาการอย่างน้อย 1 รายการก่อนตั้งผลลัพธ์");
      return;
    }
    setRulePathConditions([{ box_id: box.box_id, choice_id: markerChoice.choice_id }]);
    setRuleExisting(existingRules);
    setRuleThresholdOutcome(outcome);
    setRuleDialogOpen(true);
  }

  useBreadcrumb(
    loading || !diagram ? null : ["ผังงาน", diagram.diagram_name],
  );

  if (loading) {
    return <Spinner fullscreen label="กำลังโหลดผังงาน..." />;
  }

  if (!diagram) {
    return (
      <Card className="p-8 text-center text-[var(--color-text-secondary)]">
        ไม่พบแผนภูมิที่ระบุ
      </Card>
    );
  }

  return (
    <div>
      <div className="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div className="flex items-center gap-3">
          <Button variant="ghost" size="icon" onClick={() => navigate(-1)}>
            <ArrowLeft className="h-4 w-4" />
          </Button>
          <div>
            <h1 className="text-xl font-semibold text-[var(--color-text-primary)]">
              ผังงาน: {diagram.diagram_name}
            </h1>
            {/* <p className="mt-1 text-sm text-[var(--color-text-secondary)]">
              คลิกกล่องคำถามเพื่อแก้ไข และกำหนดผลลัพธ์
              ที่การ์ดผลลัพธ์เพื่อกำหนดโรค
            </p> */}
          </div>
        </div>

        <div className="flex items-center gap-2">
          <Button variant="outline" onClick={refetch}>
            <RefreshCw className="h-4 w-4" />
            รีเฟรช
          </Button>
        </div>
      </div>

      {!diagram.entry_box_id ? (
        <Card className="flex flex-col items-center gap-3 p-10 text-center">
          <p className="text-sm text-[var(--color-text-secondary)]">
            แผนภูมินี้ยังไม่มีกล่องคำถามเริ่มต้น
          </p>
          <Button onClick={openCreateEntry}>
            <Plus className="h-4 w-4" />
            สร้างกล่องคำถามแรก
          </Button>
        </Card>
      ) : (
        <Card className="overflow-hidden p-0">
          <FlowCanvas
            diagramId={diagramId ?? ""}
            boxes={boxes}
            entryBoxId={diagram.entry_box_id}
            ruleMap={ruleMap}
            thresholdRuleMap={thresholdRuleMap}
            onNodeClick={openEditor}
            onTerminalConfigure={openRuleEditor}
            onThresholdConfigure={openThresholdRuleEditor}
            onQuickAddChoice={handleQuickAddChoice}
            onConnect={handleConnect}
            onDisconnect={handleDisconnect}
            onQuickAddNextBox={handleQuickAddNextBox}
          />
        </Card>
      )}

      <QuestionBoxEditorDialog
        open={editorOpen}
        onOpenChange={setEditorOpen}
        diagramId={diagramId ?? ""}
        box={editorBox}
        allBoxes={boxes}
        onSaved={handleSaved}
      />

      <RuleEditorDialog
        open={ruleDialogOpen}
        onOpenChange={setRuleDialogOpen}
        diagramId={diagramId ?? ""}
        pathConditions={rulePathConditions}
        existingRules={ruleExisting}
        boxMap={boxMap}
        thresholdOutcome={ruleThresholdOutcome}
        onSaved={refreshFlowData}
      />
    </div>
  );
}
