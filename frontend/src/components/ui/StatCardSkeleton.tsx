// components/ui/StatCardSkeleton.tsx
import { Card } from "./Card";
import { Skeleton } from "./Skeleton";

export function StatCardSkeleton() {
  return (
    <Card className="flex-row items-center gap-3 p-4">
      <Skeleton className="h-10 w-10 rounded-lg" />
      <div className="flex flex-col gap-2">
        <Skeleton className="h-3 w-16" />
        <Skeleton className="h-5 w-8" />
      </div>
    </Card>
  );
}

export function StatCardSkeletonGrid({ count = 4 }: { count?: number }) {
  return (
    <div className="grid grid-cols-2 gap-4 md:grid-cols-4">
      {Array.from({ length: count }).map((_, i) => (
        <StatCardSkeleton key={i} />
      ))}
    </div>
  );
}