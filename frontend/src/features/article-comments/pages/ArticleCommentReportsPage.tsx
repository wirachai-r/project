import { useCallback, useEffect, useState } from "react";
import { Flag, MessageSquareText } from "lucide-react";
import { useNavigate } from "react-router-dom";
import { toast } from "sonner";
import { Badge } from "@/components/ui/Badge";
import { AlertDialog, AlertDialogAction, AlertDialogCancel, AlertDialogContent, AlertDialogDescription, AlertDialogFooter, AlertDialogHeader, AlertDialogTitle } from "@/components/ui/AlertDialog";
import { Card } from "@/components/ui/Card";
import { Pagination } from "@/components/ui/Pagination";
import { TableSkeleton } from "@/components/ui/TableSkeleton";
import { DataLoadError } from "@/components/ui/DataLoadError";
import { api, queryGet } from "@/lib/api";
import { resourceKeys } from "@/lib/queryClient";
import { withRowNumbers } from "@/lib/tableRows";
import { ArticleCommentReportFilters } from "../components/ArticleCommentReportFilters";
import { ArticleCommentReportPreview } from "../components/ArticleCommentReportPreview";
import { ArticleCommentReportTable, type ReportAction } from "../components/ArticleCommentReportTable";
import type { ArticleCommentReport, ArticleCommentReportFilters as FilterValue } from "@/types/articleComment";

type LaravelPagination<T> = { data: T[]; last_page: number; total: number };
type ActionTarget = { report: ArticleCommentReport; action: ReportAction };

const actionCopy: Record<ReportAction, { title: string; description: string; confirm: string }> = {
  dismiss: { title: "ยืนยันว่าไม่พบการละเมิด", description: "รายงานนี้จะถูกปิด โดยความคิดเห็นยังแสดงตามปกติ", confirm: "ยืนยันผลตรวจสอบ" },
  hide: { title: "ยืนยันการซ่อนความคิดเห็น", description: "ความคิดเห็นจะไม่แสดงต่อผู้ใช้งาน และรายงานนี้จะถือว่าดำเนินการแล้ว", confirm: "ซ่อนความคิดเห็น" },
  delete: { title: "ยืนยันการลบความคิดเห็น", description: "ความคิดเห็นและข้อความตอบกลับจะถูกลบถาวร ไม่สามารถเรียกคืนได้", confirm: "ลบความคิดเห็นถาวร" },
};

export function ArticleCommentReportsPage() {
  const navigate = useNavigate();
  const [items, setItems] = useState<ArticleCommentReport[]>([]);
  const [loading, setLoading] = useState(true);
  const [initialLoading, setInitialLoading] = useState(true);
  const [loadError, setLoadError] = useState<string | null>(null);
  const [filters, setFilters] = useState<FilterValue>({ search: "", status: "pending", reasons: [], sortDirection: "desc" });
  const [page, setPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [total, setTotal] = useState(0);
  const [busyId, setBusyId] = useState<number | null>(null);
  const [actionTarget, setActionTarget] = useState<ActionTarget | null>(null);
  const [previewTarget, setPreviewTarget] = useState<ArticleCommentReport | null>(null);

  const load = useCallback(async (background = false) => {
    if (!background) {
      setLoading(true);
      setLoadError(null);
    }
    try {
      const selectedReasons = filters.reasons ?? [];
      const params = { page, per_page: 20, search: filters.search.trim() || undefined, status: filters.status === "all" ? undefined : filters.status, reason: selectedReasons.length > 0 ? selectedReasons : undefined, sort_by: "created_at", sort_direction: filters.sortDirection };
      const response = await queryGet<LaravelPagination<ArticleCommentReport>>(resourceKeys("article-comment-reports").list(params), "/admin/article-comment-reports", { params }, 0);
      setItems(response.data); setLastPage(response.last_page); setTotal(response.total);
    } catch {
      if (!background) setLoadError("ไม่สามารถโหลดรายงานความคิดเห็นได้");
    }
    finally {
      if (!background) setLoading(false);
      setInitialLoading(false);
    }
  }, [filters, page]);

  useEffect(() => void load(), [load]);

  useEffect(() => {
    const refresh = () => void load(true);
    const interval = window.setInterval(refresh, 10_000);
    window.addEventListener("focus", refresh);

    return () => {
      window.clearInterval(interval);
      window.removeEventListener("focus", refresh);
    };
  }, [load]);

  const resolve = async (target: ActionTarget) => {
    setBusyId(target.report.id);
    try {
      await api.patch(`/admin/article-comment-reports/${target.report.id}/resolve`, { action: target.action });
      toast.success(target.action === "dismiss" ? "บันทึกว่าไม่พบการละเมิดแล้ว" : target.action === "hide" ? "ซ่อนความคิดเห็นแล้ว" : "ลบความคิดเห็นแล้ว");
      setActionTarget(null);
      await load();
    } catch { toast.error("จัดการรายงานไม่สำเร็จ"); }
    finally { setBusyId(null); }
  };

  const copy = actionTarget ? actionCopy[actionTarget.action] : null;

  return (
    <div className="space-y-5">
      <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-xl font-semibold text-[var(--color-text-primary)]">รายงานความคิดเห็นบทความ</h1>
          <p className="mt-1 text-sm text-[var(--color-text-secondary)]">ตรวจสอบเหตุผลที่รายงาน แล้วเลือกเปิดดูบริบทหรือดำเนินการกับความคิดเห็น</p>
        </div>
        <Badge variant={filters.status === "pending" ? "warning" : "default"} className="w-fit gap-1 px-3 py-1.5"><Flag /> {total} รายงาน</Badge>
      </div>

      <ArticleCommentReportFilters value={filters} onChange={(value) => { setFilters(value); setPage(1); }} />

      <Card className="p-0">
        {(initialLoading || loading) && items.length === 0 ? <TableSkeleton columns={6} columnWidths={["w-16", "w-48", "w-64", "w-52", "w-28", "w-52"]} /> : loadError && items.length === 0 ? (
          <DataLoadError description={loadError} onRetry={() => void load()} />
        ) : (
          <div className={loading ? "opacity-50 transition-opacity" : ""}>
            <ArticleCommentReportTable data={withRowNumbers(items, (page - 1) * 20 + 1)} busyId={busyId} onView={setPreviewTarget} onResolve={(report, action) => setActionTarget({ report, action })} />
          </div>
        )}
      </Card>

      {total > 0 && <Pagination current={page} total={lastPage} totalItems={total} itemLabel="รายงาน" onChange={setPage} />}

      <ArticleCommentReportPreview
        report={previewTarget}
        onClose={() => setPreviewTarget(null)}
        onOpenManagement={(report) => {
          const articleId = report.comment?.article_id;
          if (articleId) navigate(`/articles/comments/${articleId}?from=reports`);
        }}
      />

      <AlertDialog open={actionTarget !== null} onOpenChange={(open) => !open && setActionTarget(null)}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>{copy?.title}</AlertDialogTitle>
            <AlertDialogDescription>{copy?.description}</AlertDialogDescription>
          </AlertDialogHeader>
          {actionTarget?.report.comment?.content && <div className="rounded-lg bg-[var(--color-surface)] p-3 text-sm"><MessageSquareText className="mb-2 h-4 w-4 text-[var(--color-text-secondary)]" /><p className="line-clamp-3 whitespace-pre-wrap">{actionTarget.report.comment.content}</p></div>}
          <AlertDialogFooter>
            <AlertDialogCancel disabled={busyId !== null}>ยกเลิก</AlertDialogCancel>
            <AlertDialogAction loading={busyId !== null} onClick={() => actionTarget && void resolve(actionTarget)}>{copy?.confirm}</AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </div>
  );
}
