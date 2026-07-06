import { useState, useCallback } from "react";

interface UsePaginationReturn {
  currentPage: number;
  lastPage: number;
  total: number;
  setCurrentPage: (page: number) => void;
  setMeta: (meta: { last_page: number; total: number }) => void;
  resetPage: () => void;
}

export function usePagination(initialPage = 1): UsePaginationReturn {
  const [currentPage, setCurrentPage] = useState(initialPage);
  const [lastPage, setLastPage] = useState(1);
  const [total, setTotal] = useState(0);

  const setMeta = useCallback(
    ({ last_page, total }: { last_page: number; total: number }) => {
      setLastPage(last_page);
      setTotal(total);
    },
    []
  );

  const resetPage = useCallback(() => setCurrentPage(1), []);

  return { currentPage, lastPage, total, setCurrentPage, setMeta, resetPage };
}