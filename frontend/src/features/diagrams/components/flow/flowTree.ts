import type { QuestionBox } from "@/types/questionBox";
import type { AnswerChoice } from "@/types/answerChoice";

const MAX_NODES = 180;

export interface PathCondition {
  box_id: string;
  choice_id: string;
}

export interface TreeEdge {
  choice: AnswerChoice;
  child: TreeNode | null;
  terminalX?: number;
  /** เงื่อนไขทั้งหมดตั้งแต่ต้น flow จนถึง choice นี้ (รวม choice นี้ด้วย) */
  path: PathCondition[];
}

export interface TreeNode {
  box: QuestionBox;
  edges: TreeEdge[];
  x: number;
  depth: number;
  isRef: boolean;
  /** เงื่อนไขที่ตอบมาก่อนจะถึงกล่องนี้ (ไม่รวมคำตอบของกล่องนี้เอง) */
  path: PathCondition[];
}

export function buildTree(boxMap: Map<string, QuestionBox>, entryBoxId: string) {
  const visited = new Set<string>();
  let truncated = false;
  let nodeCount = 0;

  function build(boxId: string, depth: number, path: PathCondition[]): TreeNode | null {
    const box = boxMap.get(boxId);
    if (!box) return null;

    if (visited.has(boxId)) {
      return { box, edges: [], x: 0, depth, isRef: true, path };
    }
    if (nodeCount >= MAX_NODES) {
      truncated = true;
      return { box, edges: [], x: 0, depth, isRef: true, path };
    }
    visited.add(boxId);
    nodeCount++;

    const choices = [...(box.choices ?? [])]
      .filter((c) => c.status === "1")
      .sort((a, b) => a.order - b.order);

    const edges: TreeEdge[] = choices.map((choice) => {
      const choicePath = [...path, { box_id: box.box_id, choice_id: choice.choice_id }];
      if (choice.next_box_id) {
        return {
          choice,
          child: build(choice.next_box_id, depth + 1, choicePath),
          path: choicePath,
        };
      }
      return { choice, child: null, path: choicePath };
    });

    return { box, edges, x: 0, depth, isRef: false, path };
  }

  const root = build(entryBoxId, 0, []);
  return { root, truncated };
}

export function layoutTree(root: TreeNode | null) {
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

export function flattenNodes(root: TreeNode | null): TreeNode[] {
  const out: TreeNode[] = [];
  function walk(n: TreeNode | null) {
    if (!n) return;
    out.push(n);
    if (!n.isRef) n.edges.forEach((e) => walk(e.child));
  }
  walk(root);
  return out;
}

/**
 * กำหนดเลขขั้นตอน (1,2,3...) ตามตำแหน่งบนลงล่าง แล้วซ้ายไปขวา
 * กล่องเดียวกัน (isRef ชี้กลับ) ใช้เลขเดียวกับต้นฉบับ
 */
export function assignStepNumbers(nodes: TreeNode[]): Map<string, number> {
  const realNodes = nodes.filter((n) => !n.isRef);
  const unique = new Map<string, TreeNode>();
  realNodes.forEach((n) => {
    if (!unique.has(n.box.box_id)) unique.set(n.box.box_id, n);
  });

  const ordered = [...unique.values()].sort((a, b) => a.x - b.x || a.depth - b.depth);

  const stepMap = new Map<string, number>();
  ordered.forEach((n, i) => stepMap.set(n.box.box_id, i + 1));
  return stepMap;
}

export function pos(x: number, depth: number, colWidth: number, rowHeight: number, topPad: number) {
  return {
    left: depth * colWidth + 16,
    top: x * rowHeight + topPad,
  };
}

export function truncateText(text: string, max = 30) {
  return text.length > max ? `${text.slice(0, max)}…` : text;
}

export function isNegativeChoice(choice: AnswerChoice) {
  const text = choice.choice_text.trim().toLocaleLowerCase();
  return /^(ไม่|ไม่ใช่|ไม่ใช่ค่ะ|ไม่ใช่ครับ|no\b|false\b)/i.test(text);
}
