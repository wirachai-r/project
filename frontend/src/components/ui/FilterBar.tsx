import type { ReactNode } from "react";
import { Card } from "./Card";
import { DateSortFilter } from "./DateSortFilter";

interface FilterBarProps {
  children: ReactNode;
  sortKey: string | null;
  direction: "asc" | "desc" | null;
  dateSortKey: string;
  nameSortKey?: string;
  onSortChange: (key: string, direction: "asc" | "desc") => void;
}

export function FilterBar({
  children,
  sortKey,
  direction,
  dateSortKey,
  nameSortKey,
  onSortChange,
}: FilterBarProps) {
  return (
    <Card className="mb-4 overflow-visible p-0 shadow-none">
      <div className="relative flex flex-col gap-3 p-4 sm:flex-row sm:items-end sm:gap-4 sm:px-5 [&_.filter-clear-button]:absolute [&_.filter-clear-button]:bottom-4 [&_.filter-clear-button]:right-4 sm:[&_.filter-clear-button]:right-5">
        <div className="min-w-0 flex-1 [&>div]:border-0 [&>div]:bg-transparent [&>div]:p-0 [&>div]:shadow-none">
          {children}
        </div>
        <DateSortFilter
          sortKey={sortKey}
          direction={direction}
          dateSortKey={dateSortKey}
          nameSortKey={nameSortKey}
          onChange={onSortChange}
        />
      </div>
    </Card>
  );
}
