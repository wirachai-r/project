import { useCallback, useEffect, useState } from "react";
import { toast } from "sonner";
import { Card } from "@/components/ui/Card";
import { Pagination } from "@/components/ui/Pagination";
import { TableSkeleton } from "@/components/ui/TableSkeleton";
import { DataLoadError } from "@/components/ui/DataLoadError";
import { withRowNumbers } from "@/lib/tableRows";
import { api, queryGet } from "@/lib/api";
import { resourceKeys } from "@/lib/queryClient";
import { UserFeedbackFilters } from "../components/UserFeedbackFilters";
import { UserFeedbackTable } from "../components/UserFeedbackTable";
import type {
  UserFeedback,
  UserFeedbackFilterValue,
} from "@/types/userFeedback";

type Page<T> = { data: T[]; last_page: number; total: number };

export function UserFeedbackPage() {
  const [items, setItems] = useState<UserFeedback[]>([]);
  const [filters, setFilters] = useState<UserFeedbackFilterValue>({
    search: "",
    status: "pending",
    feedbackTypes: [],
    sortDirection: "desc",
  });
  const [page, setPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [total, setTotal] = useState(0);
  const [loading, setLoading] = useState(true);
  const [initialLoading, setInitialLoading] = useState(true);
  const [loadError, setLoadError] = useState<string | null>(null);
  const [busyId, setBusyId] = useState<number | null>(null);

  const load = useCallback(async () => {
    setLoading(true);
    setLoadError(null);
    try {
      const selectedFeedbackTypes = filters.feedbackTypes ?? [];
      const params = {
          page,
          per_page: 20,
          search: filters.search || undefined,
          status: filters.status === "all" ? undefined : filters.status,
          feedback_type:
            selectedFeedbackTypes.length > 0 ? selectedFeedbackTypes : undefined,
          sort_by: "created_at",
          sort_direction: filters.sortDirection,
      };
      const response = await queryGet<Page<UserFeedback>>(
        resourceKeys("feedback").list(params),
        "/admin/feedback",
        { params },
        60_000,
      );
      setItems(response.data);
      setLastPage(response.last_page);
      setTotal(response.total);
    } catch {
      setLoadError("ไม่สามารถโหลดข้อเสนอแนะจากผู้ใช้ได้");
    } finally {
      setLoading(false);
      setInitialLoading(false);
    }
  }, [filters, page]);
  useEffect(() => void load(), [load]);
  const handleFiltersChange = useCallback((value: UserFeedbackFilterValue) => {
    setFilters(value);
    setPage(1);
  }, []);

  const updateStatus = async (
    id: number,
    status: "in_review" | "resolved" | "dismissed",
    adminNote?: string,
  ) => {
    setBusyId(id);
    try {
      await api.patch(`/admin/feedback/${id}`, { status, admin_note: adminNote || null });
      toast.success("อัปเดตสถานะเรียบร้อยแล้ว");
      await load();
    } catch {
      toast.error("อัปเดตสถานะไม่สำเร็จ");
      throw new Error("feedback update failed");
    } finally {
      setBusyId(null);
    }
  };

  return (
    <div>
      <div className="mb-5">
        <h1 className="text-xl font-semibold text-[var(--color-text-primary)]">
          ข้อเสนอแนะจากผู้ใช้
        </h1>
        {/* <p className="mt-1 text-sm text-[var(--color-text-secondary)]">
          ตรวจสอบความคิดเห็น รายงานเนื้อหา และปัญหาจากผลประเมิน
        </p> */}
      </div>
      <div className="mb-4">
        <UserFeedbackFilters value={filters} onChange={handleFiltersChange} />
      </div>
      <Card className="p-0">
        {(initialLoading || loading) && items.length === 0 ? (
          <TableSkeleton columns={4} />
        ) : loadError && items.length === 0 ? (
          <DataLoadError description={loadError} onRetry={() => void load()} />
        ) : (
          <div className={loading ? "opacity-50" : ""}>
            <UserFeedbackTable
              data={withRowNumbers(items, (page - 1) * 20 + 1)}
              busyId={busyId}
              onStatusChange={updateStatus}
            />
          </div>
        )}
      </Card>
      {total > 0 && (
        <div className="mt-4">
          <Pagination
            current={page}
            total={lastPage}
            totalItems={total}
            itemLabel="ข้อเสนอแนะ"
            onChange={setPage}
          />
        </div>
      )}
    </div>
  );
}
