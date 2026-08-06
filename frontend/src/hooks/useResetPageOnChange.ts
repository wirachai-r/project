import { useEffect, useRef, type Dispatch, type SetStateAction } from "react";

/** Reset pagination only when the watched values actually change after mount. */
export function useResetPageOnChange(
  setPage: Dispatch<SetStateAction<number>>,
  watchKey: string,
  onReset?: () => void,
) {
  const previousKeyRef = useRef(watchKey);

  useEffect(() => {
    if (previousKeyRef.current === watchKey) return;

    previousKeyRef.current = watchKey;
    setPage(1);
    onReset?.();
  }, [onReset, setPage, watchKey]);
}
