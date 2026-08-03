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
import { LayoutTemplate } from "lucide-react";
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
const POSITION_STORAGE_PREFIX = "diagram-flow-positions:v9:";
const COLUMN_GAP = 540;
const ROW_GAP = 440;
const COLLISION_PADDING = 90;

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

function estimatedResultHeight(rules: DiagnosisRule[]) {
  const contentLength = rules.reduce(
    (total, rule) => total + (rule.note?.length ?? 0) + (rule.diseases?.length ?? 0) * 35,
    0,
  );
  return Math.max(190, 125 + rules.length * 65 + Math.ceil(contentLength / 38) * 18);
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
  onNodeClick: (box: QuestionBox) => void;
  onTerminalConfigure: (
    choice: AnswerChoice,
    path: PathCondition[],
    existingRules: DiagnosisRule[],
  ) => void;
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
    const parts = label.split(".").map(Number);
    let maxY = position.y;

    if (parts.length === 1) {
      // วาดชุดย่อย 1.x / 4.x ทางขวาให้จบก่อน แล้วค่อยวางเลขหลักถัดไปด้านล่าง
      const subBranches = children.filter((id) => guidebookNumbers.get(id)?.startsWith(`${label}.`));
      const mainBranches = children.filter((id) => !subBranches.includes(id));
      subBranches.forEach((id, index) => {
        maxY = Math.max(maxY, visit(id, position.x + COLUMN_GAP * (index + 1), position.y));
      });
      mainBranches.forEach((id) => {
        const nextY = maxY + ROW_GAP;
        maxY = Math.max(maxY, visit(id, position.x, nextY));
      });
    } else {
      const expectedNext = `${parts[0]}.${parts[1] + 1}`;
      const continuation = children.find((id) => guidebookNumbers.get(id) === expectedNext);
      const sideBranches = children.filter((id) => id !== continuation);
      sideBranches.forEach((id, index) => {
        maxY = Math.max(maxY, visit(id, position.x + COLUMN_GAP * (index + 1), position.y));
      });
      if (continuation) maxY = Math.max(maxY, visit(continuation, position.x, position.y + ROW_GAP));
    }

    // ผลลัพธ์ที่จบทางด้านล่างต้องกินพื้นที่ใน subtree นี้ทันที
    // เพื่อให้คำถามหลักถัดไปถูกเลื่อนลง ไม่ใช่ผลลัพธ์ถูกผลักไปกองท้ายผัง
    for (const choice of box.choices ?? []) {
      if (choice.next_box_id) continue;
      const rules = ruleMap?.get(`${box.box_id}__${choice.choice_id}`) ?? [];
      if (rules.length === 0 || getChoiceDirection(box, choice, guidebookNumbers) !== "down") continue;
      const resultHeight = estimatedResultHeight(rules);
      const occupiedRows = Math.max(1, Math.ceil((resultHeight + COLLISION_PADDING) / ROW_GAP));
      maxY = Math.max(maxY, position.y + ROW_GAP * occupiedRows);
    }
    return maxY;
  }

  visit(entryBoxId, 80, 60);
  let fallbackY = Math.max(60, ...Object.values(positions).map((position) => position.y + ROW_GAP));
  boxes.forEach((box, index) => {
    if (!positions[box.box_id]) {
      positions[box.box_id] = reserve(80 + (index % 3) * COLUMN_GAP, fallbackY);
      if (index % 3 === 2) fallbackY += ROW_GAP;
    }
  });
  return positions;
}

/**
 * สร้างเลขกรอบตามรูปแบบหนังสือ:
 * ทาง "ไม่" ของกรอบหลักไปเลขหลักถัดไป และทาง "ใช่" เปิดกรอบย่อย .1
 * เมื่ออยู่ในกรอบย่อย กล่องคำถามที่เชื่อมต่อถัดไปจะเป็น .2, .3 ...
 */
function buildGuidebookNumbers(boxes: QuestionBox[], entryBoxId: string | null) {
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

    if (parts.length === 1) {
      // แขนงตรวจต่อของกรอบหลัก เช่น 1 → 1.1 หรือ 4 → 4.1
      visit(positive, `${parts[0]}.1`);
      // เส้นทางหลัก เช่น 1 → 2 → 3 → 4
      visit(negative, String(parts[0] + 1));
    } else {
      const nextSubLabel = `${parts[0]}.${parts[1] + 1}`;
      // ในกรอบย่อย ปกติทาง "ไม่" จะตรวจข้อต่อไป; บางกรอบกลับเงื่อนไขเป็นทาง "ใช่"
      if (negative) visit(negative, nextSubLabel);
      else visit(positive, nextSubLabel);
      if (negative && positive) visit(positive, `${label}.1`);
    }
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
    const sourceParts = sourceLabel.split(".");
    // จากเลขหลักไป .1 เป็นแขนงขวา; ลำดับอื่นเป็นแนวดิ่ง
    return sourceParts.length === 1 && targetLabel.startsWith(`${sourceLabel}.`) ? "right" : "down";
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
  onNodeClick,
  onTerminalConfigure,
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
            target: box.yes_next_box_id, targetHandle: "target-left", label: "ถึงเกณฑ์",
            type: "smoothstep",
            labelBgPadding: [8, 5], labelBgBorderRadius: 6, labelBgStyle: { fill: "white", fillOpacity: 0.92 },
            style: { stroke: "#10b981" }, markerEnd: { type: MarkerType.ArrowClosed, color: "#10b981" },
          });
        }
        if (box.no_next_box_id) {
          result.push({
            id: `${box.box_id}::no`, source: box.box_id, sourceHandle: "no",
            target: box.no_next_box_id, targetHandle: "target-top", label: "ไม่ถึงเกณฑ์",
            type: "smoothstep",
            labelBgPadding: [8, 5], labelBgBorderRadius: 6, labelBgStyle: { fill: "white", fillOpacity: 0.92 },
            style: { stroke: "#f43f5e" }, markerEnd: { type: MarkerType.ArrowClosed, color: "#f43f5e" },
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
            label: choice.choice_text,
            type: "smoothstep",
            labelBgPadding: [8, 5],
            labelBgBorderRadius: 6,
            labelBgStyle: { fill: "white", fillOpacity: 0.92 },
            markerEnd: { type: MarkerType.ArrowClosed },
          });
        }
      }
    }
    return result;
  }, [boxes, entryBoxId, ruleMap]);

  useEffect(() => {
    const saved = readPositions(diagramId);
    const automatic = buildGuidebookLayout(boxes, entryBoxId, ruleMap);
    const guidebookNumbers = buildGuidebookNumbers(boxes, entryBoxId);
    setNodes((previous) => {
      const previousPositions = new Map(previous.map((node) => [node.id, node.position]));
      const questionNodes: QuestionFlowNode[] = boxes.map((box, index) => ({
        id: box.box_id,
        type: "question" as const,
        deletable: false,
        zIndex: 2,
        position: previousPositions.get(box.box_id) ?? saved[box.box_id] ?? automatic[box.box_id] ?? { x: 80, y: index * 280 },
        data: {
          box,
          stepNumber: guidebookNumbers.get(box.box_id) ?? String(index + 1),
          isEntry: box.box_id === entryBoxId,
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
        for (const choice of box.choices ?? []) {
          if (choice.next_box_id) continue;
          const terminalRules = ruleMap.get(`${box.box_id}__${choice.choice_id}`) ?? [];
          if (terminalRules.length === 0) continue;
          const id = `result:${choice.choice_id}`;
          const isDown = getChoiceDirection(box, choice, guidebookNumbers) === "down";
          let position = previousPositions.get(id) ?? saved[id] ?? {
            x: sourcePosition.x + (isDown ? 0 : COLUMN_GAP),
            y: sourcePosition.y + (isDown ? ROW_GAP : 0),
          };
          const resultHeight = estimatedResultHeight(terminalRules);
          let attempts = 0;
          while (
            !isDown &&
            occupied.some((rect) => overlapsWithPadding(
              { x: position.x, y: position.y, width: 330, height: resultHeight },
              rect,
            )) && attempts < 12
          ) {
            // ผลด้านขวาขยับออกไปคอลัมน์ถัดไป ส่วนผลด้านล่างขยับลงแถวถัดไป
            position = isDown
              ? { ...position, y: position.y + ROW_GAP }
              : { ...position, x: position.x + COLUMN_GAP };
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
  }, [boxes, diagramId, entryBoxId, onNodeClick, onQuickAddChoice, onQuickAddNextBox, onTerminalConfigure, ruleMap]);

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
    const positions = buildGuidebookLayout(boxes, entryBoxId, ruleMap);
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
          y: sourcePosition.y + (isDown ? ROW_GAP : 0),
        };
        const resultHeight = estimatedResultHeight(node.data.rules);
        let attempts = 0;
        while (
          !isDown &&
          occupied.some((rect) => overlapsWithPadding(
            { x: resultPosition.x, y: resultPosition.y, width: 330, height: resultHeight },
            rect,
          )) && attempts < 12
        ) {
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
  }, [boxes, diagramId, entryBoxId, ruleMap]);

  return (
    <div ref={wrapperRef} className="relative h-[72vh] min-h-[560px] bg-slate-50">
      <ReactFlow<DiagramCanvasNode, Edge>
        nodes={nodes}
        edges={edges}
        defaultEdgeOptions={{ type: "smoothstep" }}
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
          if (!connection.source || !connection.sourceHandle || !connection.target) return;
          if (oldEdge.source !== connection.source || oldEdge.sourceHandle !== connection.sourceHandle) {
            if (oldEdge.sourceHandle) void onDisconnect(oldEdge.source, oldEdge.sourceHandle);
          }
          void handleConnect(connection);
        }}
        edgesReconnectable
        onEdgesDelete={(deleted) => {
          deleted.forEach((edge) => {
            if (edge.sourceHandle) void onDisconnect(edge.source, edge.sourceHandle);
          });
        }}
        onConnectEnd={(event, state) => {
          if (state.isValid || !state.fromNode || !state.fromHandle) return;
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
        deleteKeyCode={["Backspace", "Delete"]}
        nodesConnectable={!connecting}
        className="diagram-flow-editor"
      >
        <Background variant={BackgroundVariant.Dots} gap={18} size={1.2} />
        <Panel position="top-left" className="flex items-center gap-2 rounded-lg border border-slate-200 bg-white/95 p-2 shadow-sm">
          <button
            type="button"
            onClick={applyAutomaticLayout}
            className="nodrag flex items-center gap-1.5 rounded-md bg-[var(--color-primary)] px-2.5 py-1.5 text-xs font-medium text-white hover:opacity-90"
          >
            <LayoutTemplate className="h-3.5 w-3.5" /> จัดเรียงซ้าย → ขวา → ลงล่าง
          </button>
          <span className="hidden text-[10px] text-slate-500 lg:inline">ลากจุดสีออกไปเพื่อเชื่อม · ลากลงพื้นที่ว่างเพื่อสร้างกล่อง</span>
        </Panel>
        <MiniMap pannable zoomable nodeColor={(node) => node.id === entryBoxId ? "#10b981" : "#64748b"} />
        <Controls />
      </ReactFlow>

      {pendingAdd && (
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
