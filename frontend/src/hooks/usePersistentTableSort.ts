import { useEffect, useState } from "react";

export type TableSortDirection = "asc" | "desc" | null;

interface StoredTableSort {
  key: string | null;
  direction: TableSortDirection;
}

export function usePersistentTableSort(storageKey: string) {
  const fullKey = `table-sort:${storageKey}`;
  const [initialSort] = useState<StoredTableSort>(() => {
    try {
      const stored = localStorage.getItem(fullKey);
      if (!stored) return { key: null, direction: null };

      const parsed = JSON.parse(stored) as Partial<StoredTableSort>;
      const direction =
        parsed.direction === "asc" || parsed.direction === "desc"
          ? parsed.direction
          : null;

      return {
        key: direction && typeof parsed.key === "string" ? parsed.key : null,
        direction,
      };
    } catch {
      return { key: null, direction: null };
    }
  });

  const [sortKey, setSortKey] = useState<string | null>(initialSort.key);
  const [sortDirection, setSortDirection] =
    useState<TableSortDirection>(initialSort.direction);

  useEffect(() => {
    localStorage.setItem(
      fullKey,
      JSON.stringify({ key: sortKey, direction: sortDirection }),
    );
  }, [fullKey, sortKey, sortDirection]);

  return { sortKey, setSortKey, sortDirection, setSortDirection };
}
