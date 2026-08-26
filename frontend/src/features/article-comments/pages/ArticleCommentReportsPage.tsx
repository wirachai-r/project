import { useCallback, useEffect, useState } from "react";
import { Flag } from "lucide-react";
import { useNavigate } from "react-router-dom";
import { toast } from "sonner";
import { Badge } from "@/components/ui/Badge";
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from "@/components/ui/AlertDialog";
import { Card } from "@/components/ui/Card";
import { Pagination } from "@/components/ui/Pagination";
import { TableSkeleton } from "@/components/ui/TableSkeleton";
import { withRowNumbers } from "@/lib/tableRows";
import { api } from "@/lib/api";
import { ArticleCommentReportFilters } from "../components/ArticleCommentReportFilters";
import { ArticleCommentReportTable } from "../components/ArticleCommentReportTable";
import type {
  ArticleCommentReport,
  ArticleCommentReportFilters as FilterValue,
} from "@/types/articleComment";

type LaravelPagination<T> = {
  data: T[];
  last_page: number;
  total: number;
};

export function ArticleCommentReportsPage() {
  const navigate = useNavigate();
  const [items, setItems] = useState<ArticleCommentReport[]>([]);
  const [loading, setLoading] = useState(true);
  const [initialLoading, setInitialLoading] = useState(true);
  const [filters, setFilters] = useState<FilterValue>({
    search: "",
    status: "pending",
    reason: "all",
    sortDirection: "desc",
  });
  const [page, setPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [total, setTotal] = useState(0);
  const [busyId, setBusyId] = useState<number | null>(null);
  const [deleteTarget, setDeleteTarget] = useState<number | null>(null);

  const load = useCallback(async () => {
    setLoading(true);
    try {
      const response = await api.get<LaravelPagination<ArticleCommentReport>>(
        "/admin/article-comment-reports",
        {
          params: {
            page,
            per_page: 20,
            search: filters.search.trim() || undefined,
            status: filters.status === "all" ? undefined : filters.status,
            reason: filters.reason === "all" ? undefined : filters.reason,
            sort_by: "created_at",
            sort_direction: filters.sortDirection,
          },
        },
      );
      setItems(response.data.data);
      setLastPage(response.data.last_page);
      setTotal(response.data.total);
    } catch {
      toast.error("ไม่สามารถโหลดรายงานความคิดเห็นได้");
    } finally {
      setLoading(false);
      setInitialLoading(false);
    }
  }, [filters, page]);

  useEffect(() => void load(), [load]);

  const handleFiltersChange = useCallback((value: FilterValue) => {
    setFilters(value);
    setPage(1);
  }, []);

  const resolve = async (
    id: number,
    action: "dismiss" | "hide" | "delete",
  ) => {
    setBusyId(id);
    try {
      await api.patch(`/admin/article-comment-reports/${id}/resolve`, {
        action,
      });
      toast.success("จัดการรายงานแล้ว");
      await load();
    } catch {
      toast.error("จัดการรายงานไม่สำเร็จ");
    } finally {
      setBusyId(null);
    }
  };

  return (
    <div>
      <div className="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-xl font-semibold text-[var(--color-text-primary)]">
            รายงานความคิดเห็นบทความ
          </h1>
          {/* <p className="mt-1 text-sm text-[var(--color-text-secondary)]">
            ตรวจสอบรายงานและเปิดดูความคิดเห็นในหน้าจัดการความคิดเห็น
          </p> */}
        </div>
        <Badge variant="danger" className="w-fit px-3 py-1.5">
          <Flag className="mr-1" /> {total} รายงาน
        </Badge>
      </div>

      <div className="mb-4">
        <ArticleCommentReportFilters
          value={filters}
          onChange={handleFiltersChange}
        />
      </div>

      <Card className="p-0">
        {initialLoading ? (
          <TableSkeleton
            columns={5}
            rows={8}
            columnWidths={["w-48", "w-64", "w-40", "w-20", "w-64"]}
          />
        ) : (
          <div className={loading ? "opacity-50 transition-opacity" : ""}>
            <ArticleCommentReportTable
              data={withRowNumbers(items, (page - 1) * 20 + 1)}
              busyId={busyId}
              onView={(report) => {
                const articleId = report.comment?.article_id;
                if (articleId) {
                  navigate(`/articles/comments/${articleId}?from=reports`);
                }
              }}
              onResolve={(id, action) =>
                action === "delete"
                  ? setDeleteTarget(id)
                  : void resolve(id, action)
              }
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
            itemLabel="รายงาน"
            onChange={setPage}
          />
        </div>
      )}
      <AlertDialog
        open={deleteTarget !== null}
        onOpenChange={(open) => !open && setDeleteTarget(null)}
      >
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>ยืนยันการลบความคิดเห็น</AlertDialogTitle>
            <AlertDialogDescription>
              ความคิดเห็นที่ถูกรายงานจะถูกลบอย่างถาวร
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel disabled={busyId === deleteTarget}>
              ยกเลิก
            </AlertDialogCancel>
            <AlertDialogAction
              loading={busyId === deleteTarget}
              onClick={() => {
                if (deleteTarget !== null) {
                  void resolve(deleteTarget, "delete").then(() =>
                    setDeleteTarget(null),
                  );
                }
              }}
            >
              ลบความคิดเห็น
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </div>
  );
}
