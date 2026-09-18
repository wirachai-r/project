import { useEffect, useState } from "react";

export type TableSortDirection = "asc" | "desc" | null;

interface TableSortState {
  key: string | null;
  direction: TableSortDirection;
}

const sortMemory = new Map<string, TableSortState>();

export function usePersistentTableSort(
  storageKey: string,
  defaultKey = "created_at",
  defaultDirection: Exclude<TableSortDirection, null> = "desc",
) {
  const remembered = sortMemory.get(storageKey);
  const [sortKey, setSortKey] = useState<string | null>(
    remembered?.key ?? defaultKey,
  );
  const [sortDirection, setSortDirection] =
    useState<TableSortDirection>(remembered?.direction ?? defaultDirection);

  useEffect(() => {
    sortMemory.set(storageKey, {
      key: sortKey,
      direction: sortDirection,
    });
  }, [sortDirection, sortKey, storageKey]);

  return { sortKey, setSortKey, sortDirection, setSortDirection };
}
