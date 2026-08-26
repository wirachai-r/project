import { useMemo, useState, type ReactNode } from "react";
import { ArrowUp, ArrowDown, ArrowUpDown } from "lucide-react";
import {
  Table,
  TableHeader,
  TableBody,
  TableRow,
  TableHead,
  TableCell,
} from "./Table";
import { EmptyState } from "./EmptyState";
import { cn } from "../../lib/utils";

export type SortDirection = "asc" | "desc" | null;

export interface Column<T> {
  key: string;
  label: ReactNode;
  render?: (row: T, index: number) => ReactNode;
  className?: string;
  /** เปิด sort ให้คอลัมน์นี้ */
  sortable?: boolean;
  /** ใช้เทียบค่าตอน sort ฝั่ง client — ถ้าไม่ส่งมาจะ fallback ไปอ่าน row[key] */
  sortValue?: (row: T) => string | number | null | undefined;
}

interface DataTableProps<T> {
  columns: Column<T>[];
  data: T[];
  keyExtractor?: (row: T) => string | number;
  emptyMessage?: string;

  /** ควบคุม sort จากภายนอก (เช่น ยิง API ใหม่) — ถ้าส่ง onSortChange มา จะไม่ sort เองฝั่ง client */
  sortKey?: string;
  sortDirection?: SortDirection;
  onSortChange?: (key: string, direction: SortDirection) => void;
}

function nextDirection(current: SortDirection): SortDirection {
  if (current === null) return "asc";
  if (current === "asc") return "desc";
  return null;
}

export function DataTable<T>({
  columns,
  data,
  keyExtractor,
  emptyMessage = "ไม่พบข้อมูล",
  sortKey: controlledKey,
  sortDirection: controlledDirection,
  onSortChange,
}: DataTableProps<T>) {
  const isControlled = !!onSortChange;

  const [internalKey, setInternalKey] = useState<string | null>(null);
  const [internalDirection, setInternalDirection] =
    useState<SortDirection>(null);

  const activeKey = isControlled ? controlledKey ?? null : internalKey;
  const activeDirection = isControlled
    ? controlledDirection ?? null
    : internalDirection;

  const handleSortClick = (col: Column<T>) => {
    if (!col.sortable) return;
    const isSameCol = activeKey === col.key;
    const newDirection = nextDirection(isSameCol ? activeDirection : null);
    const newKey = newDirection === null ? null : col.key;

    if (isControlled) {
      onSortChange?.(newKey ?? "", newDirection);
    } else {
      setInternalKey(newKey);
      setInternalDirection(newDirection);
    }
  };

  const sortedData = useMemo(() => {
    if (isControlled || !activeKey || !activeDirection) return data;

    const col = columns.find((c) => c.key === activeKey);
    if (!col) return data;

    const getValue = (row: T) =>
      col.sortValue
        ? col.sortValue(row)
        : ((row as Record<string, unknown>)[col.key] as
            | string
            | number
            | null
            | undefined);

    return [...data].sort((a, b) => {
      const va = getValue(a);
      const vb = getValue(b);
      if (va == null && vb == null) return 0;
      if (va == null) return 1;
      if (vb == null) return -1;
      if (typeof va === "number" && typeof vb === "number") {
        return activeDirection === "asc" ? va - vb : vb - va;
      }
      const sa = String(va);
      const sb = String(vb);
      return activeDirection === "asc"
        ? sa.localeCompare(sb, "th")
        : sb.localeCompare(sa, "th");
    });
  }, [data, columns, activeKey, activeDirection, isControlled]);

  if (data.length === 0) {
    return <EmptyState title={emptyMessage} />;
  }

  return (
    <Table>
      <TableHeader>
        <TableRow>
          {columns.map((col) => {
            const isIdentifierColumn =
              col.key === "id" ||
              (col.key.endsWith("_id") &&
                (col.label === "ID" || col.label === "รหัส"));
            const isSortable = false;
            const isActive = activeKey === col.key && !!activeDirection;
            return (
              <TableHead key={col.key} className={col.className}>
                {isSortable ? (
                  <button
                    type="button"
                    onClick={() => handleSortClick(col)}
                    className={cn(
                      "group flex cursor-pointer select-none items-center gap-1 transition-colors hover:text-[var(--color-primary)]",
                      isActive && "text-[var(--color-primary)]",
                    )}
                  >
                    {isIdentifierColumn ? "ลำดับ" : col.label}
                    {isActive ? (
                      activeDirection === "asc" ? (
                        <ArrowUp className="h-3.5 w-3.5 text-[var(--color-primary)]" />
                      ) : (
                        <ArrowDown className="h-3.5 w-3.5 text-[var(--color-primary)]" />
                      )
                    ) : (
                      <ArrowUpDown className="h-3.5 w-3.5 text-[var(--color-text-secondary)] transition-colors group-hover:text-[var(--color-primary)]" />
                    )}
                  </button>
                ) : (
                  isIdentifierColumn ? "ลำดับ" : col.label
                )}
              </TableHead>
            );
          })}
        </TableRow>
      </TableHeader>
      <TableBody>
        {sortedData.map((row, i) => (
          <TableRow key={keyExtractor ? keyExtractor(row) : i}>
            {columns.map((col) => {
              const fallback = (row as Record<string, unknown>)[col.key];
              const rowNumber = (row as Record<string, unknown>).__rowNumber;
              const isIdentifierColumn =
                col.key === "id" ||
                (col.key.endsWith("_id") &&
                  (col.label === "ID" || col.label === "รหัส"));
              return (
                <TableCell key={col.key} className={col.className}>
                  {isIdentifierColumn && typeof rowNumber === "number"
                    ? rowNumber
                    : col.render
                      ? col.render(row, i)
                      : String(fallback ?? "—")}
                </TableCell>
              );
            })}
          </TableRow>
        ))}
      </TableBody>
    </Table>
  );
}
