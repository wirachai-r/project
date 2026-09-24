// components/ui/TableSkeleton.tsx
import { Skeleton } from "./Skeleton";
import { Table, TableHeader, TableBody, TableRow, TableHead, TableCell } from "./Table";

interface TableSkeletonProps {
  columns: number;
  columnWidths?: string[]; // เช่น ["w-32", "w-48", "w-20", "w-16"]
  rows?: number;
}

const SKELETON_ROW_COUNT = 10;

export function TableSkeleton({
  columns,
  columnWidths,
  rows = SKELETON_ROW_COUNT,
}: TableSkeletonProps) {
  return (
    <Table>
      <TableHeader>
        <TableRow>
          {Array.from({ length: columns }).map((_, i) => (
            <TableHead key={i}>
              <Skeleton className="h-3 w-16" />
            </TableHead>
          ))}
        </TableRow>
      </TableHeader>
      <TableBody>
        {Array.from({ length: rows }).map((_, r) => (
          <TableRow key={r}>
            {Array.from({ length: columns }).map((_, c) => (
              <TableCell key={c}>
                <Skeleton className={`h-4 ${columnWidths?.[c] ?? "w-24"}`} />
              </TableCell>
            ))}
          </TableRow>
        ))}
      </TableBody>
    </Table>
  );
}
