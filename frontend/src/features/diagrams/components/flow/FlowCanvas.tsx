import { useCallback, useEffect, useMemo, useRef, useState } from "react";
import {
  Background,
  BackgroundVariant,
  ConnectionLineType,
  Controls,
  MarkerType,
  MiniMap,
  Panel,
  ReactFlow,
  type Connection,
  type Edge,
  type NodeChange,
  type ReactFlowInstance,
  applyNodeChanges,
} from "@xyflow/react";
import { LayoutTemplate, Map as MapIcon, Lock, LockOpen } from "lucide-react";
import { Tooltip, TooltipContent, TooltipTrigger } from "@/components/ui/Tooltip";
import "@xyflow/react/dist/style.css";
import type { DiagnosisRule } from "@/types/diagnosisRule";
import type { QuestionBox } from "@/types/questionBox";
import type { AnswerChoice } from "@/types/answerChoice";
import { isNegativeChoice, type PathCondition } from "./flowTree";
import { FlowNode, ResultFlowNode, type QuestionFlowNode, type ResultFlowNode as ResultNodeType } from "./FlowNode";
import { QuickAddPopover } from "./QuickAddPopover";

const nodeTypes = { question: FlowNode, result: ResultFlowNode };
type DiagramCanvasNode = QuestionFlowNode | ResultNodeType;
// เปลี่ยนเวอร์ชันเมื่อกติกาจัดวางเปลี่ยน เพื่อไม่ดึงตำแหน่งเก่าที่ทำให้กรอบซ้อนกันกลับมาใช้
const POSITION_STORAGE_PREFIX = "diagram-flow-positions:v18-subtree-clearance:";
const COLUMN_GAP = 540;
const ROW_GAP = 520;
const COLLISION_PADDING = 160;

interface LayoutRect {
  x: number;
  y: number;
  width: number;
  height: number;
}

function overlapsWithPadding(a: LayoutRect, b: LayoutRect) {
  return !(
    a.x + a.width + COLLISION_PADDING <= b.x ||
    b.x + b.width + COLLISION_PADDING <= a.x ||
    a.y + a.height + COLLISION_PADDING <= b.y ||
    b.y + b.height + COLLISION_PADDING <= a.y
  );
}

function estimatedQuestionHeight(box: QuestionBox) {
  return 125 + Math.max(2, box.choices?.length ?? 0) * 42;
}

// จำลองความสูงของ ResultFlowNode ให้ใกล้เคียงจริง แยกนับทีละส่วน
// (หัวข้อ / เวลา / หมายเหตุที่มี \n จริง / รายการแผนภูมิถัดไป) แทนการเหมาการนับ
// ตัวอักษรรวมแบบเดิม ซึ่งประเมินต่ำกว่าความจริงเมื่อเนื้อหามีบรรทัดย่อยเยอะ
// ทำให้พื้นที่ที่กันชนไว้ไม่พอ การ์ดเลยไปทับกล่องข้างเคียง
function estimatedResultHeight(rules: DiagnosisRule[]) {
  const HEADER_HEIGHT = 40; // "ผลลัพธ์ / คำแนะนำ" + margin
  const BUTTON_HEIGHT = 44; // ปุ่ม "แก้ไขผลลัพธ์" + margin
  const CARD_PADDING = 34; // p-2.5 บน-ล่างของแต่ละการ์ด rule
  const CARD_GAP = 8; // space-y-2 ระหว่างการ์ด rule
  const LINE_HEIGHT = 20;

  const linesFor = (text: string, charsPerLine = 38) =>
    text
      .split("\n")
      .reduce((sum, line) => sum + Math.max(1, Math.ceil(line.length / charsPerLine)), 0);

  let total = HEADER_HEIGHT + BUTTON_HEIGHT;

  for (const rule of rules) {
    let cardHeight = CARD_PADDING;

    const titleText = (rule.diseases?.length ?? 0) > 0
      ? rule.diseases!.map((d) => `${d.disease_name}${d.reference ? ` (${d.reference})` : ""}`).join(" / ")
      : "คำแนะนำ";
    const titleFull = titleText + (rule.medical_reference ? ` (${rule.medical_reference})` : "");
    cardHeight += linesFor(titleFull, 34) * LINE_HEIGHT;

    if (rule.time_frame) {
      cardHeight += linesFor(rule.time_frame, 34) * LINE_HEIGHT + 4;
    }

    if (rule.note) {
      cardHeight += linesFor(rule.note) * LINE_HEIGHT + 6;
    }

    if ((rule.next_diagrams?.length ?? 0) > 0) {
      cardHeight += 24; // หัวข้อ "แผนภูมิที่แนะนำ:"
      cardHeight += rule.next_diagrams!.reduce(
        (sum, diagram) => sum + linesFor(diagram.diagram_name, 40) * LINE_HEIGHT,
        0,
      );
    }

    total += cardHeight + CARD_GAP;
  }

  return Math.max(190, total);
}

function findFreePosition(
  initial: { x: number; y: number },
  width: number,
  height: number,
  occupied: LayoutRect[],
  direction: "right" | "down",
) {
  let position = initial;
  let attempts = 0;
  while (
    occupied.some((rect) =>
      overlapsWithPadding({ ...position, width, height }, rect),
    ) &&
    attempts < 50
  ) {
    // ทั้งสองทิศใช้การขยับ "ลง" เพื่อกันชน แทนการขยับ X ไปทางขวาต่อเนื่อง
    // (เดิม direction "right" เลื่อนแค่ X ทำให้ Y เท่ากับต้นทางเสมอ เส้นเชื่อมไปกล่องที่ถูกเลื่อนไกลออกไป
    // เลยลากผ่านทับกล่องที่ถูกจองไว้ก่อนหน้าซึ่งอยู่ระดับ Y เดียวกันพอดี)
    position = { ...position, y: position.y + ROW_GAP };
    void direction;
    attempts++;
  }
  return position;
}

function thresholdChoice(box: QuestionBox, outcome: "yes" | "no"): AnswerChoice {
  return {
    choice_id: `threshold:${box.box_id}:${outcome}`,
    choice_text: outcome === "yes" ? "ถึงเกณฑ์" : "ไม่ถึงเกณฑ์",
    choice_text_en: null,
    choice_image: null,
    order: outcome === "yes" ? 1 : 2,
    status: "1",
    box_id: box.box_id,
    next_box_id: null,
    next_diagram_id: null,
    created_by: null,
    updated_by: null,
    created_at: "",
    updated_at: "",
  };
}

interface PendingAdd {
  boxId: string;
  handleId: string;
  left: number;
  top: number;
}

interface FlowCanvasProps {
  diagramId: string;
  boxes: QuestionBox[];
  entryBoxId: string | null;
  ruleMap: Map<string, DiagnosisRule[]>;
  thresholdRuleMap: Map<string, DiagnosisRule[]>;
  onNodeClick: (box: QuestionBox) => void;
  onTerminalConfigure: (
    choice: AnswerChoice,
    path: PathCondition[],
    existingRules: DiagnosisRule[],
  ) => void;
  onThresholdConfigure: (box: QuestionBox, outcome: "yes" | "no", existingRules: DiagnosisRule[]) => void;
  onQuickAddChoice: (boxId: string, choiceText: string) => void;
  onConnect: (boxId: string, handleId: string, targetBoxId: string) => Promise<void>;
  onDisconnect: (boxId: string, handleId: string) => Promise<void>;
  onQuickAddNextBox: (boxId: string, handleId: string, questionText: string) => void;
}

function readPositions(diagramId: string): Record<string, { x: number; y: number }> {
  try {
    return JSON.parse(localStorage.getItem(`${POSITION_STORAGE_PREFIX}${diagramId}`) ?? "{}") as Record<
      string,
      { x: number; y: number }
    >;
  } catch {
    return {};
  }
}

function buildGuidebookLayout(
  boxes: QuestionBox[],
  entryBoxId: string | null,
  ruleMap?: Map<string, DiagnosisRule[]>,
  thresholdRuleMap?: Map<string, DiagnosisRule[]>,
) {
  const boxMap = new Map(boxes.map((box) => [box.box_id, box]));
  const guidebookNumbers = buildGuidebookNumbers(boxes, entryBoxId);
  const positions: Record<string, { x: number; y: number }> = {};
  const occupied = new Set<string>();

  function reserve(x: number, y: number) {
    let nextY = y;
    while (occupied.has(`${x}:${nextY}`)) nextY += ROW_GAP;
    occupied.add(`${x}:${nextY}`);
    return { x, y: nextY };
  }

  function visit(boxId: string | null, x: number, y: number): number {
    if (!boxId || !boxMap.has(boxId)) return y;
    if (positions[boxId]) return positions[boxId].y;
    const position = reserve(x, y);
    positions[boxId] = position;
    const box = boxMap.get(boxId)!;
    const childIds = box.question_type === "M"
      ? [box.yes_next_box_id, box.no_next_box_id]
      : [...(box.choices ?? [])]
          .filter((choice) => choice.status === "1")
          .sort((a, b) => a.order - b.order)
          .map((choice) => choice.next_box_id);
    const children = [...new Set(childIds.filter((id): id is string => Boolean(id)))];
    const label = guidebookNumbers.get(boxId) ?? "";
    const nextRowY = position.y + estimatedQuestionHeight(box) + COLLISION_PADDING;

    // columnBottom: ใช้กำหนดตำแหน่ง "ทางลง" ในคอลัมน์เดียวกันเท่านั้น
    let columnBottom = position.y + estimatedQuestionHeight(box);
    // subtreeMaxY: พื้นที่รวมทั้ง subtree (รวมทางขวาด้วย) ส่งกลับให้ parent ใช้เว้นระยะกับ sibling เท่านั้น
    // ไม่เอาไปกำหนดตำแหน่งทางลงของตัวเอง เพราะทางขวาอยู่คนละคอลัมน์ ไม่ชนกัน
    let subtreeMaxY = columnBottom;

    const sideBranches = children.filter((id) => guidebookNumbers.get(id)?.startsWith(`${label}.`));
    const downwardBranches = children.filter((id) => !sideBranches.includes(id));

    sideBranches.forEach((id, index) => {
      const branchMaxY = visit(id, position.x + COLUMN_GAP * (index + 1), position.y);
      subtreeMaxY = Math.max(subtreeMaxY, branchMaxY);
    });
    const accountResult = (rules: DiagnosisRule[], direction: "right" | "down") => {
      if (rules.length === 0) return;
      const resultHeight = estimatedResultHeight(rules);
      if (direction === "down") {
        const bottom = nextRowY + resultHeight + COLLISION_PADDING;
        columnBottom = Math.max(columnBottom, bottom);
        subtreeMaxY = Math.max(subtreeMaxY, bottom);
      } else {
        // ทางขวา: กระทบแค่ subtreeMaxY (สำหรับเว้นระยะ sibling) ไม่กระทบ columnBottom
        const bottom = position.y + resultHeight + COLLISION_PADDING;
        subtreeMaxY = Math.max(subtreeMaxY, bottom);
      }
    };

    if (box.question_type === "M") {
      if (!box.yes_next_box_id) {
        accountResult(thresholdRuleMap?.get(`${box.box_id}__yes`) ?? [], "right");
      }
      if (!box.no_next_box_id) {
        accountResult(thresholdRuleMap?.get(`${box.box_id}__no`) ?? [], "down");
      }
    } else {
      for (const choice of box.choices ?? []) {
        if (choice.next_box_id) continue;
        const rules = ruleMap?.get(`${box.box_id}__${choice.choice_id}`) ?? [];
        const direction = getChoiceDirection(box, choice, guidebookNumbers);
        accountResult(rules, direction);
      }
    }

    // The main/downward path must start after every side subtree and terminal
    // result belonging to this node. Otherwise the next main step is placed
    // beside an unfinished side branch and the two flows visually collide.
    downwardBranches.forEach((id) => {
      const branchStartY = Math.max(
        nextRowY,
        columnBottom + COLLISION_PADDING,
        subtreeMaxY + COLLISION_PADDING,
      );
      const branchMaxY = visit(id, position.x, branchStartY);
      columnBottom = Math.max(columnBottom, branchMaxY);
      subtreeMaxY = Math.max(subtreeMaxY, branchMaxY);
    });

    return subtreeMaxY;
  }

  visit(entryBoxId, 80, 60);
  let fallbackY = Math.max(60, ...Object.values(positions).map((position) => position.y + ROW_GAP));
  boxes.forEach((box, index) => {
    if (!positions[box.box_id]) {
      positions[box.box_id] = reserve(80 + (index % 3) * COLUMN_GAP, fallbackY);
      if (index % 3 === 2) fallbackY += ROW_GAP;
    }
  });

  // Keep question cards out of the horizontal lane between a question and its
  // right-hand branch. Node collision checks alone are not enough here: a card
  // can sit between two non-overlapping nodes and make their edge run through it.
  const horizontalLinks = boxes.flatMap((box) => {
    if (box.question_type === "M") {
      return box.yes_next_box_id
        ? [{ sourceId: box.box_id, targetId: box.yes_next_box_id }]
        : [];
    }
    return (box.choices ?? []).flatMap((choice) =>
      choice.next_box_id && getChoiceDirection(box, choice, guidebookNumbers) === "right"
        ? [{ sourceId: box.box_id, targetId: choice.next_box_id }]
        : [],
    );
  });

  for (let pass = 0; pass < boxes.length; pass++) {
    let changed = false;
    for (const link of horizontalLinks) {
      const source = boxMap.get(link.sourceId);
      const sourcePosition = positions[link.sourceId];
      const targetPosition = positions[link.targetId];
      if (!source || !sourcePosition || !targetPosition) continue;
      const laneLeft = Math.min(sourcePosition.x, targetPosition.x) + 290;
      const laneRight = Math.max(sourcePosition.x, targetPosition.x);
      if (laneRight <= laneLeft) continue;

      for (const blocker of boxes) {
        if (blocker.box_id === link.sourceId || blocker.box_id === link.targetId) continue;
        const blockerPosition = positions[blocker.box_id];
        if (!blockerPosition) continue;
        const blocksHorizontally = blockerPosition.x < laneRight && blockerPosition.x + 290 > laneLeft;
        const blocksVertically =
          blockerPosition.y < sourcePosition.y + estimatedQuestionHeight(source) + COLLISION_PADDING / 2 &&
          blockerPosition.y + estimatedQuestionHeight(blocker) > sourcePosition.y - COLLISION_PADDING / 2;
        if (!blocksHorizontally || !blocksVertically) continue;

        let nextY = sourcePosition.y + estimatedQuestionHeight(source) + COLLISION_PADDING;
        while (boxes.some((other) => {
          if (other.box_id === blocker.box_id) return false;
          const otherPosition = positions[other.box_id];
          return otherPosition && overlapsWithPadding(
            { x: blockerPosition.x, y: nextY, width: 290, height: estimatedQuestionHeight(blocker) },
            { x: otherPosition.x, y: otherPosition.y, width: 290, height: estimatedQuestionHeight(other) },
          );
        })) nextY += ROW_GAP;
        positions[blocker.box_id] = { ...blockerPosition, y: nextY };
        changed = true;
      }
    }
    if (!changed) break;
  }
  return positions;
}

/**
 * สร้างเลขกรอบตามรูปแบบหนังสือ:
 * ทาง "ไม่" ของกรอบหลักไปเลขหลักถัดไป และทาง "ใช่" เปิดกรอบย่อย .1
 * เมื่ออยู่ในกรอบย่อย กล่องคำถามที่เชื่อมต่อถัดไปจะเป็น .2, .3 ...
 */
// eslint-disable-next-line react-refresh/only-export-components
export function buildGuidebookNumbers(boxes: QuestionBox[], entryBoxId: string | null) {
  const boxMap = new Map(boxes.map((box) => [box.box_id, box]));
  const numbers = new Map<string, string>();
  const visiting = new Set<string>();

  function childrenOf(box: QuestionBox) {
    if (box.question_type === "M") {
      return { positive: box.yes_next_box_id, negative: box.no_next_box_id };
    }
    const choices = [...(box.choices ?? [])]
      .filter((choice) => choice.status === "1" && choice.next_box_id)
      .sort((a, b) => a.order - b.order);
    return {
      positive: choices.find((choice) => !isNegativeChoice(choice))?.next_box_id ?? null,
      negative: choices.find(isNegativeChoice)?.next_box_id ?? null,
    };
  }

  function visit(boxId: string | null, label: string) {
    if (!boxId || !boxMap.has(boxId) || numbers.has(boxId) || visiting.has(boxId)) return;
    numbers.set(boxId, label);
    visiting.add(boxId);
    const box = boxMap.get(boxId)!;
    const { positive, negative } = childrenOf(box);
    const parts = label.split(".").map(Number);

    const nextSibling = [...parts];
    nextSibling[nextSibling.length - 1] += 1;
    visit(positive, `${label}.1`);
    visit(negative, nextSibling.join("."));
    visiting.delete(boxId);
  }

  visit(entryBoxId, "1");
  let fallback = 1;
  boxes.forEach((box) => {
    if (!numbers.has(box.box_id)) numbers.set(box.box_id, `อื่นๆ ${fallback++}`);
  });
  return numbers;
}

function getChoiceDirection(
  box: QuestionBox,
  choice: AnswerChoice,
  numbers: Map<string, string>,
): "right" | "down" {
  const sourceLabel = numbers.get(box.box_id) ?? "";
  if (choice.next_box_id) {
    const targetLabel = numbers.get(choice.next_box_id) ?? "";
    return targetLabel.startsWith(`${sourceLabel}.`) ? "right" : "down";
  }

  // ถ้าอีกคำตอบหนึ่งพาไปคำถามถัดไปด้านล่าง ผลลัพธ์ของคำตอบนี้ต้องออกขวา
  const linkedChoices = (box.choices ?? []).filter((item) => item.next_box_id);
  const hasDownwardContinuation = linkedChoices.some(
    (item) => getChoiceDirection(box, item, numbers) === "down",
  );
  if (hasDownwardContinuation) return "right";
  // ปลายทางตามรูปหนังสือ: ใช่ไปขวา / ไม่ลงล่าง
  return isNegativeChoice(choice) ? "down" : "right";
}

export function FlowCanvas({
  diagramId,
  boxes,
  entryBoxId,
  ruleMap,
  thresholdRuleMap,
  onNodeClick,
  onTerminalConfigure,
  onThresholdConfigure,
  onQuickAddChoice,
  onConnect,
  onDisconnect,
  onQuickAddNextBox,
}: FlowCanvasProps) {
  const wrapperRef = useRef<HTMLDivElement>(null);
  const flowInstanceRef = useRef<ReactFlowInstance<DiagramCanvasNode, Edge> | null>(null);
  const focusedEntryRef = useRef<string | null>(null);
  const [pendingAdd, setPendingAdd] = useState<PendingAdd | null>(null);
  const [nodes, setNodes] = useState<DiagramCanvasNode[]>([]);
  const [connecting, setConnecting] = useState(false);
  const [interactive, setInteractive] = useState(true);
  const [minimapOpen, setMinimapOpen] = useState(true);
  const locked = !interactive;

  const focusEntryNode = useCallback(() => {
    const instance = flowInstanceRef.current;
    const focusKey = `${diagramId}:${entryBoxId ?? ""}`;
    if (!instance || !entryBoxId || focusedEntryRef.current === focusKey) return;
    requestAnimationFrame(() => {
      const entryNode = instance.getNode(entryBoxId);
      if (!entryNode) return;
      const width = entryNode.measured?.width ?? entryNode.width ?? 290;
      const height = entryNode.measured?.height ?? entryNode.height ?? 190;
      focusedEntryRef.current = focusKey;
      void instance.setCenter(
        entryNode.position.x + width / 2,
        entryNode.position.y + height / 2,
        { zoom: 0.85, duration: 450 },
      );
    });
  }, [diagramId, entryBoxId]);

  const edges = useMemo<Edge[]>(() => {
    const result: Edge[] = [];
    const guidebookNumbers = buildGuidebookNumbers(boxes, entryBoxId);
    for (const box of boxes) {
      if (box.question_type === "M") {
        if (box.yes_next_box_id) {
          result.push({
            id: `${box.box_id}::yes`, source: box.box_id, sourceHandle: "yes",
            target: box.yes_next_box_id, targetHandle: "target-left",
            type: "smoothstep",
            label: "ใช่",
            labelBgPadding: [8, 5], labelBgBorderRadius: 6, labelBgStyle: { fill: "white", fillOpacity: 0.92 },
            style: { stroke: "#10b981" }, markerEnd: { type: MarkerType.ArrowClosed, color: "#10b981" },
          });
        }
        if (box.no_next_box_id) {
          result.push({
            id: `${box.box_id}::no`, source: box.box_id, sourceHandle: "no",
            target: box.no_next_box_id, targetHandle: "target-top",
            type: "smoothstep",
            label: "ไม่ใช่",
            labelBgPadding: [8, 5], labelBgBorderRadius: 6, labelBgStyle: { fill: "white", fillOpacity: 0.92 },
            style: { stroke: "#f43f5e" }, markerEnd: { type: MarkerType.ArrowClosed, color: "#f43f5e" },
          });
        }
        for (const outcome of ["yes", "no"] as const) {
          const rules = thresholdRuleMap.get(`${box.box_id}__${outcome}`) ?? [];
          const hasNextBox = outcome === "yes" ? box.yes_next_box_id : box.no_next_box_id;
          if (hasNextBox || rules.length === 0) continue;
          const isDown = outcome === "no";
          result.push({
            id: `${box.box_id}::threshold:${outcome}`,
            source: box.box_id,
            sourceHandle: outcome,
            target: `result:threshold:${box.box_id}:${outcome}`,
            targetHandle: isDown ? "target-top" : "target-left",
            type: "smoothstep",
            label: outcome === "yes" ? "ใช่" : "ไม่ใช่",
            labelBgPadding: [8, 5],
            labelBgBorderRadius: 6,
            labelBgStyle: { fill: "white", fillOpacity: 0.92 },
            style: { stroke: outcome === "yes" ? "#10b981" : "#f43f5e" },
            markerEnd: { type: MarkerType.ArrowClosed, color: outcome === "yes" ? "#10b981" : "#f43f5e" },
          });
        }
        continue;
      }
      for (const choice of box.choices ?? []) {
        const direction = getChoiceDirection(box, choice, guidebookNumbers);
        const target = choice.next_box_id ?? (ruleMap.get(`${box.box_id}__${choice.choice_id}`)?.length ? `result:${choice.choice_id}` : null);
        if (target) {
          result.push({
            id: `${box.box_id}::choice:${choice.choice_id}`,
            source: box.box_id,
            sourceHandle: `choice:${choice.choice_id}`,
            target,
            targetHandle: direction === "down" ? "target-top" : "target-left",
            type: "smoothstep",
            label: choice.choice_text,
            labelBgPadding: [8, 5],
            labelBgBorderRadius: 6,
            labelBgStyle: { fill: "white", fillOpacity: 0.92 },
            markerEnd: { type: MarkerType.ArrowClosed },
          });
        }
      }
    }
    return result;
  }, [boxes, entryBoxId, ruleMap, thresholdRuleMap]);

  useEffect(() => {
    const saved = readPositions(diagramId);
    const automatic = buildGuidebookLayout(boxes, entryBoxId, ruleMap, thresholdRuleMap);
    const guidebookNumbers = buildGuidebookNumbers(boxes, entryBoxId);
    setNodes((previous) => {
      const previousPositions = new Map(previous.map((node) => [node.id, node.position]));
      const questionNodes: QuestionFlowNode[] = boxes.map((box, index) => ({
        id: box.box_id,
        type: "question" as const,
        deletable: false,
        zIndex: 2,
        position: saved[box.box_id] ?? automatic[box.box_id] ?? previousPositions.get(box.box_id) ?? { x: 80, y: index * 280 },
        data: {
          box,
          stepNumber: guidebookNumbers.get(box.box_id) ?? String(index + 1),
          isEntry: box.box_id === entryBoxId,
          locked,
          onEdit: onNodeClick,
          onCreateChoice: onQuickAddChoice,
          onCreateNext: onQuickAddNextBox,
          choiceDirections: Object.fromEntries(
            (box.choices ?? []).map((choice) => [choice.choice_id, getChoiceDirection(box, choice, guidebookNumbers)]),
          ),
          choicesWithResults: Object.fromEntries(
            (box.choices ?? []).map((choice) => [
              choice.choice_id,
              (ruleMap.get(`${box.box_id}__${choice.choice_id}`)?.length ?? 0) > 0,
            ]),
          ),
          checklistResults: {
            yes: (thresholdRuleMap.get(`${box.box_id}__yes`)?.length ?? 0) > 0,
            no: (thresholdRuleMap.get(`${box.box_id}__no`)?.length ?? 0) > 0,
          },
          onConfigureChecklistRule: (outcome) => onThresholdConfigure(
            box,
            outcome,
            thresholdRuleMap.get(`${box.box_id}__${outcome}`) ?? [],
          ),
          onConfigureRule: (choice) => {
            const key = `${box.box_id}__${choice.choice_id}`;
            onTerminalConfigure(
              choice,
              [{ box_id: box.box_id, choice_id: choice.choice_id }],
              ruleMap.get(key) ?? [],
            );
          },
        },
      }));
      const resultNodes: ResultNodeType[] = [];
      const occupied: LayoutRect[] = questionNodes.map((node) => ({
        x: node.position.x,
        y: node.position.y,
        width: 290,
        height: estimatedQuestionHeight(node.data.box),
      }));
      for (const box of boxes) {
        const sourcePosition = automatic[box.box_id] ?? { x: 80, y: 60 };
        if (box.question_type === "M") {
          for (const outcome of ["yes", "no"] as const) {
            const terminalRules = thresholdRuleMap.get(`${box.box_id}__${outcome}`) ?? [];
            const hasNextBox = outcome === "yes" ? box.yes_next_box_id : box.no_next_box_id;
            if (hasNextBox || terminalRules.length === 0) continue;
            const id = `result:threshold:${box.box_id}:${outcome}`;
            const isDown = outcome === "no";
            const initialPosition = saved[id] ?? {
              x: sourcePosition.x + (isDown ? 0 : COLUMN_GAP),
              y: sourcePosition.y + (isDown ? estimatedQuestionHeight(box) + COLLISION_PADDING : 0),
            };
            const resultHeight = estimatedResultHeight(terminalRules);
            const position = findFreePosition(initialPosition, 330, resultHeight, occupied, isDown ? "down" : "right");
            occupied.push({ x: position.x, y: position.y, width: 330, height: resultHeight });
            resultNodes.push({
              id,
              type: "result",
              zIndex: 3,
              position,
              data: {
                rules: terminalRules,
                choice: thresholdChoice(box, outcome),
                locked,
                onConfigure: () => onThresholdConfigure(box, outcome, terminalRules),
              },
            });
          }
          continue;
        }
        for (const choice of box.choices ?? []) {
          if (choice.next_box_id) continue;
          const terminalRules = ruleMap.get(`${box.box_id}__${choice.choice_id}`) ?? [];
          if (terminalRules.length === 0) continue;
          const id = `result:${choice.choice_id}`;
          const isDown = getChoiceDirection(box, choice, guidebookNumbers) === "down";
          let position = saved[id] ?? {
            x: sourcePosition.x + (isDown ? 0 : COLUMN_GAP),
            y: sourcePosition.y + (isDown ? estimatedQuestionHeight(box) + COLLISION_PADDING : 0),
          };
          const resultHeight = estimatedResultHeight(terminalRules);
          let attempts = 0;
          while (occupied.some((rect) => overlapsWithPadding(
            { x: position.x, y: position.y, width: 330, height: resultHeight }, rect,
          )) && attempts < 50) {
            position = isDown ? { ...position, y: position.y + ROW_GAP } : { ...position, x: position.x + COLUMN_GAP };
            attempts++;
          }
          occupied.push({ x: position.x, y: position.y, width: 330, height: resultHeight });
          resultNodes.push({
            id,
            type: "result",
            zIndex: 3,
            position,
            data: {
              rules: terminalRules,
              choice,
              locked,
              onConfigure: (selectedChoice) => onTerminalConfigure(
                selectedChoice,
                [{ box_id: box.box_id, choice_id: selectedChoice.choice_id }],
                terminalRules,
              ),
            },
          });
        }
      }
      return [...questionNodes, ...resultNodes];
    });
  }, [boxes, diagramId, entryBoxId, onNodeClick, onQuickAddChoice, onQuickAddNextBox, onTerminalConfigure, onThresholdConfigure, ruleMap, thresholdRuleMap, locked]);

  const handleNodesChange = useCallback(
    (changes: NodeChange<DiagramCanvasNode>[]) => {
      setNodes((current) => {
        const next = applyNodeChanges(changes, current);
        if (changes.some((change) => change.type === "position" && !change.dragging)) {
          localStorage.setItem(
            `${POSITION_STORAGE_PREFIX}${diagramId}`,
            JSON.stringify(Object.fromEntries(next.map((node) => [node.id, node.position]))),
          );
        }
        return next;
      });
      if (changes.some((change) => change.type === "dimensions")) {
        requestAnimationFrame(focusEntryNode);
      }
    },
    [diagramId, focusEntryNode],
  );

  const handleConnect = useCallback(
    async (connection: Connection) => {
      if (!connection.source || !connection.sourceHandle || !connection.target) return;
      // Result edges are generated from saved rules; they are not question-box links.
      if (connection.target.startsWith("result:")) return;
      setConnecting(true);
      try {
        await onConnect(connection.source, connection.sourceHandle, connection.target);
      } finally {
        setConnecting(false);
      }
    },
    [onConnect],
  );

  const applyAutomaticLayout = useCallback(() => {
    const positions = buildGuidebookLayout(boxes, entryBoxId, ruleMap, thresholdRuleMap);
    const guidebookNumbers = buildGuidebookNumbers(boxes, entryBoxId);
    setNodes((current) => {
      const choiceSources = new Map<string, { boxId: string; choice: AnswerChoice }>();
      boxes.forEach((box) => box.choices?.forEach((choice) => choiceSources.set(choice.choice_id, { boxId: box.box_id, choice })));
      const occupied: LayoutRect[] = boxes.flatMap((box) => {
        const position = positions[box.box_id];
        return position ? [{ x: position.x, y: position.y, width: 290, height: estimatedQuestionHeight(box) }] : [];
      });
      const next = current.map((node) => {
        if (node.type === "question") return { ...node, position: positions[node.id] ?? node.position };
        if (node.id.startsWith("result:threshold:")) {
          const [, , boxId, outcome] = node.id.split(":");
          const sourceBox = boxes.find((box) => box.box_id === boxId);
          const sourcePosition = positions[boxId];
          if (!sourceBox || !sourcePosition) return node;
          const isDown = outcome === "no";
          const resultHeight = estimatedResultHeight(node.data.rules);
          const resultPosition = findFreePosition(
            {
              x: sourcePosition.x + (isDown ? 0 : COLUMN_GAP),
              y: sourcePosition.y + (isDown ? estimatedQuestionHeight(sourceBox) + COLLISION_PADDING : 0),
            },
            330,
            resultHeight,
            occupied,
            isDown ? "down" : "right",
          );
          occupied.push({ ...resultPosition, width: 330, height: resultHeight });
          return {
            ...node,
            position: resultPosition,
          };
        }
        const choiceId = node.id.slice("result:".length);
        const source = choiceSources.get(choiceId);
        if (!source) return node;
        const sourcePosition = positions[source.boxId];
        if (!sourcePosition) return node;
        const sourceBox = boxes.find((box) => box.box_id === source.boxId);
        const isDown = sourceBox
          ? getChoiceDirection(sourceBox, source.choice, guidebookNumbers) === "down"
          : isNegativeChoice(source.choice);
        let resultPosition = {
          x: sourcePosition.x + (isDown ? 0 : COLUMN_GAP),
          y: sourcePosition.y + (isDown && sourceBox ? estimatedQuestionHeight(sourceBox) + COLLISION_PADDING : 0),
        };
        const resultHeight = estimatedResultHeight(node.data.rules);
        let attempts = 0;
        while (occupied.some((rect) => overlapsWithPadding(
          { x: resultPosition.x, y: resultPosition.y, width: 330, height: resultHeight }, rect,
        )) && attempts < 50) {
          resultPosition = isDown
            ? { ...resultPosition, y: resultPosition.y + ROW_GAP }
            : { ...resultPosition, x: resultPosition.x + COLUMN_GAP };
          attempts++;
        }
        occupied.push({ x: resultPosition.x, y: resultPosition.y, width: 330, height: resultHeight });
        return {
          ...node,
          position: resultPosition,
        };
      });
      localStorage.setItem(
        `${POSITION_STORAGE_PREFIX}${diagramId}`,
        JSON.stringify(Object.fromEntries(next.map((node) => [node.id, node.position]))),
      );
      return next;
    });
  }, [boxes, diagramId, entryBoxId, ruleMap, thresholdRuleMap]);

  return (
    <div ref={wrapperRef} className="relative h-[72vh] min-h-[560px] bg-slate-50">
      <ReactFlow<DiagramCanvasNode, Edge>
        nodes={nodes}
        edges={edges}
        defaultEdgeOptions={{ type: "smoothstep", zIndex: 0 }}
        elevateEdgesOnSelect={false}
        connectionLineType={ConnectionLineType.SmoothStep}
        nodeTypes={nodeTypes}
        onInit={(instance) => {
          flowInstanceRef.current = instance;
          requestAnimationFrame(() => requestAnimationFrame(focusEntryNode));
        }}
        onNodesChange={handleNodesChange}
        onConnect={handleConnect}
        onReconnect={(oldEdge, connection) => {
          if (locked) return;
          if (!connection.source || !connection.sourceHandle || !connection.target) return;
          if (oldEdge.source !== connection.source || oldEdge.sourceHandle !== connection.sourceHandle) {
            if (oldEdge.sourceHandle) void onDisconnect(oldEdge.source, oldEdge.sourceHandle);
          }
          void handleConnect(connection);
        }}
        edgesReconnectable={!locked}
        onEdgesDelete={(deleted) => {
          if (locked) return;
          deleted.forEach((edge) => {
            if (edge.sourceHandle) void onDisconnect(edge.source, edge.sourceHandle);
          });
        }}
        onConnectEnd={(event, state) => {
          if (locked || state.isValid || !state.fromNode || !state.fromHandle) return;
          const point = "changedTouches" in event ? event.changedTouches[0] : event;
          const bounds = wrapperRef.current?.getBoundingClientRect();
          if (!bounds) return;
          setPendingAdd({
            boxId: state.fromNode.id,
            handleId: state.fromHandle.id ?? "",
            left: Math.max(12, Math.min(point.clientX - bounds.left, bounds.width - 330)),
            top: Math.max(12, Math.min(point.clientY - bounds.top, bounds.height - 150)),
          });
        }}
        minZoom={0.25}
        deleteKeyCode={locked ? [] : ["Backspace", "Delete"]}
        nodesDraggable={interactive}
        nodesConnectable={interactive && !connecting}
        elementsSelectable={interactive}
        className="diagram-flow-editor"
      >
        <Background variant={BackgroundVariant.Dots} gap={18} size={1.2} />

        <Panel position="top-left" className="flex items-center gap-1 rounded-lg border border-slate-200 bg-white/95 p-1.5 shadow-sm">
          <Tooltip>
            <TooltipTrigger asChild>
              <button
                type="button"
                onClick={applyAutomaticLayout}
                disabled={locked}
                className="nodrag flex h-8 w-8 items-center justify-center rounded-md bg-[var(--color-primary)] text-white hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-40"
              >
                <LayoutTemplate className="h-4 w-4" />
              </button>
            </TooltipTrigger>
            <TooltipContent>จัดเรียงบน → ล่าง (แขนงไปขวา)</TooltipContent>
          </Tooltip>

          <Tooltip>
            <TooltipTrigger asChild>
              <button
                type="button"
                onClick={() => setMinimapOpen((v) => !v)}
                className={`nodrag flex h-8 w-8 items-center justify-center rounded-md border hover:bg-slate-50 ${
                  minimapOpen ? "border-[var(--color-primary)] text-[var(--color-primary)]" : "border-slate-200 text-slate-500"
                }`}
              >
                <MapIcon className="h-4 w-4" />
              </button>
            </TooltipTrigger>
            <TooltipContent>{minimapOpen ? "ซ่อนแผนที่ย่อ" : "แสดงแผนที่ย่อ"}</TooltipContent>
          </Tooltip>

          <Tooltip>
            <TooltipTrigger asChild>
              <button
                type="button"
                onClick={() => setInteractive((v) => !v)}
                className={`nodrag flex h-8 w-8 items-center justify-center rounded-md border hover:bg-slate-50 ${
                  locked ? "border-amber-400 text-amber-600" : "border-slate-200 text-slate-500"
                }`}
              >
                {locked ? <Lock className="h-4 w-4" /> : <LockOpen className="h-4 w-4" />}
              </button>
            </TooltipTrigger>
            <TooltipContent>{locked ? "ปลดล็อกเพื่อแก้ไข" : "ล็อกไม่ให้แก้ไข"}</TooltipContent>
          </Tooltip>
        </Panel>

        {minimapOpen && (
          <MiniMap pannable zoomable nodeColor={(node) => (node.id === entryBoxId ? "#10b981" : "#64748b")} />
        )}
        <Controls onInteractiveChange={setInteractive} />
      </ReactFlow>

      {pendingAdd && !locked && (
        <QuickAddPopover
          left={pendingAdd.left}
          top={pendingAdd.top}
          placeholder="ข้อความคำถามของกล่องใหม่"
          submitLabel="สร้างและเชื่อมลูกศร"
          onSubmit={(text) => {
            onQuickAddNextBox(pendingAdd.boxId, pendingAdd.handleId, text);
            setPendingAdd(null);
          }}
          onClose={() => setPendingAdd(null)}
        />
      )}
    </div>
  );
}
