import { useEffect, useState } from "react";

interface TablePaginationState {
  page: number;
  pageSize: number;
}

const paginationMemory = new Map<string, TablePaginationState>();

export function usePersistentTablePagination(storageKey: string) {
  const remembered = paginationMemory.get(storageKey);
  const [page, setPage] = useState(remembered?.page ?? 1);
  const [pageSize, setPageSize] = useState(remembered?.pageSize ?? 10);

  useEffect(() => {
    paginationMemory.set(storageKey, { page, pageSize });
  }, [page, pageSize, storageKey]);

  return {
    page,
    setPage,
    pageSize,
    setPageSize,
  };
}
