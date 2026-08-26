import { useEffect, useMemo, useState } from "react";
import { toast } from "sonner";
import { GitBranch, X, ArrowUp, ArrowDown, Loader2 } from "lucide-react";
import { questionBoxApi } from "@/lib/api/questionBox";
import type { QuestionBox } from "@/types/questionBox";
import type { AnswerChoice } from "@/types/answerChoice";
import type { ConditionDraft } from "@/types/diagnosisRule";
import { makeConditionKey } from "@/types/diagnosisRule";

interface RuleConditionGraphProps {
  diagramId: string | null;
  entryBoxId: string | null;
  conditions: ConditionDraft[];
  onChange: (conditions: ConditionDraft[]) => void;
}

const COL_WIDTH = 232;
const ROW_HEIGHT = 172;
const NODE_WIDTH = 200;
const NODE_TOP_PAD = 24;
const MAX_NODES = 180;

interface TreeEdge {
  choice: AnswerChoice;
  child: TreeNode | null;
  terminalX?: number;
}

interface TreeNode {
  box: QuestionBox;
  edges: TreeEdge[];
  x: number;
  depth: number;
  isRef: boolean;
}

async function fetchDiagramBoxTree(
  diagramId: string,
  signal?: AbortSignal,
): Promise<QuestionBox[]> {
  const res = await questionBoxApi.list(
    diagramId,
    { per_page: 200, status: "1" },
    signal,
  );
  return res.data;
}

function buildTree(boxes: QuestionBox[], entryBoxId: string) {
  const byId = new Map(boxes.map((b) => [b.box_id, b]));
  const visited = new Set<string>();
  let truncated = false;
  let nodeCount = 0;

  function build(boxId: string, depth: number): TreeNode | null {
    const box = byId.get(boxId);
    if (!box) return null;

    if (visited.has(boxId)) {
      return { box, edges: [], x: 0, depth, isRef: true };
    }
    if (nodeCount >= MAX_NODES) {
      truncated = true;
      return { box, edges: [], x: 0, depth, isRef: true };
    }
    visited.add(boxId);
    nodeCount++;

    const choices = [...(box.choices ?? [])]
      .filter((c) => c.status === "1")
      .sort((a, b) => a.order - b.order);

    const edges: TreeEdge[] = choices.map((choice) => ({
      choice,
      child: choice.next_box_id ? build(choice.next_box_id, depth + 1) : null,
    }));

    return { box, edges, x: 0, depth, isRef: false };
  }

  const root = build(entryBoxId, 0);
  return { root, truncated };
}

function layoutTree(root: TreeNode | null) {
  if (!root) return { maxX: 0, maxDepth: 0 };
  let xCounter = 0;
  let maxDepth = 0;

  function assign(node: TreeNode): number {
    maxDepth = Math.max(maxDepth, node.depth);

    if (node.edges.length === 0 || node.isRef) {
      node.x = xCounter++;
      return node.x;
    }

    const xs: number[] = [];
    for (const edge of node.edges) {
      if (edge.child) {
        xs.push(assign(edge.child));
      } else {
        edge.terminalX = xCounter++;
        maxDepth = Math.max(maxDepth, node.depth + 1);
        xs.push(edge.terminalX);
      }
    }
    node.x = (Math.min(...xs) + Math.max(...xs)) / 2;
    return node.x;
  }

  assign(root);
  return { maxX: xCounter, maxDepth };
}

function flattenNodes(root: TreeNode | null): TreeNode[] {
  const out: TreeNode[] = [];
  function walk(n: TreeNode | null) {
    if (!n) return;
    out.push(n);
    if (!n.isRef) n.edges.forEach((e) => walk(e.child));
  }
  walk(root);
  return out;
}

function pos(x: number, depth: number) {
  return {
    left: x * COL_WIDTH + 16,
    top: depth * ROW_HEIGHT + NODE_TOP_PAD,
  };
}

export function RuleConditionGraph({
  diagramId,
  entryBoxId,
  conditions,
  onChange,
}: RuleConditionGraphProps) {
  const [boxes, setBoxes] = useState<QuestionBox[]>([]);
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    const controller = new AbortController();

    async function load() {
      if (!diagramId) {
        setBoxes([]);
        return;
      }
      setLoading(true);
      try {
        const data = await fetchDiagramBoxTree(diagramId, controller.signal);
        setBoxes(data);
      } catch {
        toast.error("ไม่สามารถโหลดกรอบคำถามของแผนภูมินี้ได้");
      } finally {
        setLoading(false);
      }
    }

    load();
    return () => controller.abort();
  }, [diagramId]);

  const { root, truncated, maxX, maxDepth } = useMemo(() => {
    if (!entryBoxId || boxes.length === 0) {
      return {
        root: null as TreeNode | null,
        truncated: false,
        maxX: 0,
        maxDepth: 0,
      };
    }
    const { root, truncated } = buildTree(boxes, entryBoxId);
    const { maxX, maxDepth } = layoutTree(root);
    return { root, truncated, maxX, maxDepth };
  }, [boxes, entryBoxId]);

  const nodes = useMemo(() => flattenNodes(root), [root]);

  const selectedMap = useMemo(() => {
    const m = new Map<string, ConditionDraft>();
    conditions.forEach((c) => m.set(c.key, c));
    return m;
  }, [conditions]);

  const boxLookup = useMemo(() => {
    const m = new Map<
      string,
      { question_text: string; choices: Map<string, string> }
    >();
    boxes.forEach((b) => {
      m.set(b.box_id, {
        question_text: b.question_text,
        choices: new Map(
          (b.choices ?? []).map((c) => [c.choice_id, c.choice_text]),
        ),
      });
    });
    return m;
  }, [boxes]);

  function toggleChoice(box: QuestionBox, choice: AnswerChoice) {
    const key = makeConditionKey(box.box_id, choice.choice_id);
    if (selectedMap.has(key)) {
      onChange(conditions.filter((c) => c.key !== key));
      return;
    }
    const next: ConditionDraft = {
      key,
      box_id: box.box_id,
      choice_id: choice.choice_id,
      logic_operator: "AND",
      status: "1",
    };
    onChange([...conditions, next]);
  }

  function setOperator(index: number, op: "AND" | "OR") {
    const next = [...conditions];
    next[index] = { ...next[index], logic_operator: op };
    onChange(next);
  }

  function removeAt(index: number) {
    onChange(conditions.filter((_, i) => i !== index));
  }

  function move(index: number, dir: -1 | 1) {
    const target = index + dir;
    if (target < 0 || target >= conditions.length) return;
    const next = [...conditions];
    [next[index], next[target]] = [next[target], next[index]];
    onChange(next);
  }

  if (!diagramId) {
    return (
      <p className="rounded-lg border border-dashed border-[var(--color-border)] p-6 text-center text-sm text-[var(--color-text-secondary)]">
        เลือกแผนภูมิ (diagram) ก่อน จึงจะแสดงกราฟกรอบคำถามให้เลือกเงื่อนไขได้
      </p>
    );
  }

  if (loading) {
    return (
      <div className="flex items-center justify-center gap-2 rounded-lg border border-[var(--color-border)] p-10 text-sm text-[var(--color-text-secondary)]">
        <Loader2 className="h-4 w-4 animate-spin" />
        กำลังโหลดกราฟกรอบคำถาม...
      </div>
    );
  }

  if (!entryBoxId || !root) {
    return (
      <p className="rounded-lg border border-dashed border-[var(--color-border)] p-6 text-center text-sm text-[var(--color-text-secondary)]">
        แผนภูมินี้ยังไม่ได้ตั้งค่ากรอบคำถามเริ่มต้น (entry box)
        กรุณาไปตั้งค่าที่หน้าแผนภูมิก่อน
      </p>
    );
  }

  const canvasWidth = Math.max(maxX + 1, 1) * COL_WIDTH + 32;
  const canvasHeight = (maxDepth + 1) * ROW_HEIGHT + NODE_TOP_PAD + 40;

  return (
    <div className="grid gap-4 lg:grid-cols-[1fr_280px]">
      <div className="relative overflow-auto rounded-lg border border-[var(--color-border)] bg-[var(--color-bg-subtle,#f8fafc)]">
        {truncated && (
          <div className="sticky top-0 z-10 bg-amber-50 px-3 py-1.5 text-xs text-amber-800">
            แผนภูมินี้มีกรอบคำถามจำนวนมาก แสดงผลเพียงบางส่วนเพื่อความลื่นไหล
          </div>
        )}
        <div
          className="relative"
          style={{ width: canvasWidth, height: canvasHeight, minWidth: "100%" }}
        >
          <svg
            className="pointer-events-none absolute inset-0"
            width={canvasWidth}
            height={canvasHeight}
          >
            {nodes.map((node) =>
              node.isRef
                ? null
                : node.edges.map((edge, i) => {
                    const from = pos(node.x, node.depth);
                    const childX = edge.child
                      ? edge.child.x
                      : (edge.terminalX ?? node.x);
                    const childDepth = node.depth + 1;
                    const to = pos(childX, childDepth);
                    const x1 = from.left + NODE_WIDTH / 2;
                    const y1 = from.top + 58;
                    const x2 = to.left + NODE_WIDTH / 2;
                    const y2 = to.top;
                    const midY = (y1 + y2) / 2;
                    const selected = selectedMap.has(
                      makeConditionKey(node.box.box_id, edge.choice.choice_id),
                    );
                    return (
                      <path
                        key={`${node.box.box_id}-${edge.choice.choice_id}-${i}`}
                        d={`M ${x1} ${y1} C ${x1} ${midY}, ${x2} ${midY}, ${x2} ${y2}`}
                        fill="none"
                        stroke={selected ? "var(--color-primary)" : "#cbd5e1"}
                        strokeWidth={selected ? 2.5 : 1.5}
                      />
                    );
                  }),
            )}
          </svg>

          {nodes.map((node) => {
            const { left, top } = pos(node.x, node.depth);
            return (
              <div
                key={`box-${node.box.box_id}-${node.depth}-${node.x}`}
                className="absolute rounded-lg border border-[var(--color-border)] bg-[var(--color-surface)] p-2.5 shadow-sm"
                style={{ left, top, width: NODE_WIDTH }}
              >
                {node.isRef ? (
                  <p className="text-xs italic text-[var(--color-text-secondary)]">
                    ↳ ย้อนไปกรอบ {node.box.box_id} (แสดงด้านบนแล้ว)
                  </p>
                ) : (
                  <>
                    <span className="mb-1 inline-block rounded bg-[var(--color-primary-light)] px-1.5 py-0.5 text-[10px] font-medium text-[var(--color-primary)]">
                      {node.box.box_id}
                    </span>
                    <p className="line-clamp-3 text-xs font-medium text-[var(--color-text-primary)]">
                      {node.box.question_text}
                    </p>
                  </>
                )}
              </div>
            );
          })}

          {nodes.map((node) =>
            node.isRef
              ? null
              : node.edges.map((edge, i) => {
                  const from = pos(node.x, node.depth);
                  const childX = edge.child
                    ? edge.child.x
                    : (edge.terminalX ?? node.x);
                  const to = pos(childX, node.depth + 1);
                  const midLeft =
                    (from.left + to.left) / 2 + NODE_WIDTH / 2 - 46;
                  const midTop = (from.top + 58 + to.top) / 2 - 12;
                  const key = makeConditionKey(
                    node.box.box_id,
                    edge.choice.choice_id,
                  );
                  const selected = selectedMap.has(key);
                  return (
                    <button
                      key={`edge-${key}-${i}`}
                      type="button"
                      onClick={() => toggleChoice(node.box, edge.choice)}
                      className={`absolute z-[1] max-w-[120px] truncate rounded-full border px-2 py-1 text-[11px] font-medium shadow-sm transition-colors ${
                        selected
                          ? "border-[var(--color-primary)] bg-[var(--color-primary)] text-white"
                          : "border-[var(--color-border)] bg-white text-[var(--color-text-secondary)] hover:border-[var(--color-primary)] hover:text-[var(--color-primary)]"
                      }`}
                      style={{ left: midLeft, top: midTop }}
                      title={edge.choice.choice_text}
                    >
                      {edge.choice.choice_text}
                    </button>
                  );
                }),
          )}

          {nodes.map((node) =>
            node.isRef
              ? null
              : node.edges
                  .filter((e) => !e.child)
                  .map((edge, i) => {
                    const { left, top } = pos(
                      edge.terminalX ?? node.x,
                      node.depth + 1,
                    );
                    return (
                      <div
                        key={`terminal-${node.box.box_id}-${edge.choice.choice_id}-${i}`}
                        className="absolute rounded-lg border border-dashed border-[var(--color-border)] bg-white px-2.5 py-2 text-[11px] text-[var(--color-text-secondary)]"
                        style={{ left, top, width: NODE_WIDTH }}
                      >
                        จบเส้นทาง — เลือกตัวเลือกนี้ด้านบนเพื่อใช้เป็นเงื่อนไข
                      </div>
                    );
                  }),
          )}
        </div>
      </div>

      <div className="rounded-lg border border-[var(--color-border)] p-3">
        <div className="mb-2 flex items-center gap-1.5 text-sm font-semibold text-[var(--color-text-primary)]">
          <GitBranch className="h-4 w-4 text-[var(--color-primary)]" />
          เงื่อนไขของกฎนี้ ({conditions.length})
        </div>

        {conditions.length === 0 ? (
          <p className="text-xs text-[var(--color-text-secondary)]">
            คลิกป้ายตัวเลือกบนกราฟด้านซ้ายเพื่อเพิ่มเงื่อนไข
          </p>
        ) : (
          <ul className="space-y-2">
            {conditions.map((c, i) => (
              <li
                key={c.key}
                className="rounded-md border border-[var(--color-border)] p-2"
              >
                {i > 0 && (
                  <div className="mb-1.5 flex gap-1">
                    {(["AND", "OR"] as const).map((op) => (
                      <button
                        key={op}
                        type="button"
                        onClick={() => setOperator(i, op)}
                        className={`rounded px-2 py-0.5 text-[10px] font-semibold ${
                          c.logic_operator === op
                            ? "bg-[var(--color-primary)] text-white"
                            : "bg-[var(--color-bg-subtle,#f1f5f9)] text-[var(--color-text-secondary)]"
                        }`}
                      >
                        {op === "AND" ? "และ" : "หรือ"}
                      </button>
                    ))}
                  </div>
                )}
                <div className="flex items-start justify-between gap-1.5">
                  <div className="min-w-0">
                    <p className="truncate text-[11px] text-[var(--color-text-secondary)]">
                      {boxLookup.get(c.box_id)?.question_text ?? c.box_id}
                    </p>
                    <p className="text-xs font-medium text-[var(--color-text-primary)]">
                      {boxLookup.get(c.box_id)?.choices.get(c.choice_id) ??
                        c.choice_id}
                    </p>
                  </div>
                  <div className="flex shrink-0 items-center gap-0.5">
                    <button
                      type="button"
                      onClick={() => move(i, -1)}
                      disabled={i === 0}
                      className="rounded p-1 text-[var(--color-text-secondary)] hover:bg-[var(--color-bg-subtle,#f1f5f9)] disabled:opacity-30"
                    >
                      <ArrowUp className="h-3.5 w-3.5" />
                    </button>
                    <button
                      type="button"
                      onClick={() => move(i, 1)}
                      disabled={i === conditions.length - 1}
                      className="rounded p-1 text-[var(--color-text-secondary)] hover:bg-[var(--color-bg-subtle,#f1f5f9)] disabled:opacity-30"
                    >
                      <ArrowDown className="h-3.5 w-3.5" />
                    </button>
                    <button
                      type="button"
                      onClick={() => removeAt(i)}
                      className="rounded p-1 text-red-500 hover:bg-red-50"
                    >
                      <X className="h-3.5 w-3.5" />
                    </button>
                  </div>
                </div>
              </li>
            ))}
          </ul>
        )}
      </div>
    </div>
  );
}
