import { useEffect, useState } from "react";

interface StoredTablePagination {
  page: number;
  pageSize: number;
}

export function usePersistentTablePagination(storageKey: string) {
  const fullKey = `table-pagination:${storageKey}`;
  const [initialPagination] = useState<StoredTablePagination>(() => {
    try {
      const parsed = JSON.parse(localStorage.getItem(fullKey) ?? "{}") as
        Partial<StoredTablePagination>;

      return {
        page:
          Number.isInteger(parsed.page) && Number(parsed.page) > 0
            ? Number(parsed.page)
            : 1,
        pageSize:
          Number.isInteger(parsed.pageSize) && Number(parsed.pageSize) > 0
            ? Number(parsed.pageSize)
            : 10,
      };
    } catch {
      return { page: 1, pageSize: 10 };
    }
  });

  const [page, setPage] = useState(initialPagination.page);
  const [pageSize, setPageSize] = useState(initialPagination.pageSize);

  useEffect(() => {
    localStorage.setItem(fullKey, JSON.stringify({ page, pageSize }));
  }, [fullKey, page, pageSize]);

  return {
    page,
    setPage,
    pageSize,
    setPageSize,
  };
}
