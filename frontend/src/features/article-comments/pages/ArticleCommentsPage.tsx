import { useCallback, useEffect, useMemo, useState } from "react";
import { ArrowLeft, MessageCircle } from "lucide-react";
import { useNavigate, useParams, useSearchParams } from "react-router-dom";
import { toast } from "sonner";
import { Button } from "@/components/ui/Button";
import { Card } from "@/components/ui/Card";
import { Pagination } from "@/components/ui/Pagination";
import { TableSkeleton } from "@/components/ui/TableSkeleton";
import { usePersistentTablePagination } from "@/hooks/usePersistentTablePagination";
import { useResetPageOnChange } from "@/hooks/useResetPageOnChange";
import { api, queryGet } from "@/lib/api";
import { resourceKeys } from "@/lib/queryClient";
import { ArticleCommentActionDialogs, type ReportActionTarget } from "../components/ArticleCommentActionDialogs";
import { ArticleCommentFilters, type ArticleCommentFilterValue } from "../components/ArticleCommentFilters";
import { ArticleCommentsDetail, ArticleCommentsDetailSkeleton } from "../components/ArticleCommentsDetail";
import { ArticleCommentsTable } from "../components/ArticleCommentsTable";
import type { ArticleComment, CommentReply } from "../components/ArticleCommentThread";

type LaravelPagination<T> = {
  data: T[];
  last_page: number;
  total: number;
  article_total: number;
};

const defaultFilters: ArticleCommentFilterValue = {
  search: "",
  visibility: "all",
  reportStatus: "all",
  sortBy: "latest_comment",
};

export function ArticleCommentsPage() {
  const navigate = useNavigate();
  const { articleId } = useParams<{ articleId: string }>();
  const [searchParams] = useSearchParams();
  const openedFromReports = searchParams.get("from") === "reports";
  const [items, setItems] = useState<ArticleComment[]>([]);
  const [loading, setLoading] = useState(true);
  const [filters, setFilters] = useState(defaultFilters);
  const [search, setSearch] = useState("");
  const { page, setPage, pageSize, setPageSize } = usePersistentTablePagination(
    articleId ? "article-comments-detail" : "article-comments",
  );
  const [lastPage, setLastPage] = useState(1);
  const [total, setTotal] = useState(0);
  const [articleTotal, setArticleTotal] = useState(0);
  const [busyId, setBusyId] = useState<number | null>(null);
  const [deleteTarget, setDeleteTarget] = useState<CommentReply | null>(null);
  const [reportActionTarget, setReportActionTarget] = useState<ReportActionTarget | null>(null);

  useEffect(() => {
    const timer = window.setTimeout(() => setSearch(filters.search.trim()), 400);
    return () => window.clearTimeout(timer);
  }, [filters.search]);

  const load = useCallback(async () => {
    setLoading(true);
    try {
      const params = {
        page,
        per_page: pageSize,
        search: search || undefined,
        visibility: filters.visibility === "all" ? undefined : filters.visibility,
        report_status: filters.reportStatus === "all" ? undefined : filters.reportStatus,
        sort_by: filters.sortBy,
        article_id: articleId,
      };
      const response = await queryGet<LaravelPagination<ArticleComment>>(
        resourceKeys("article-comments").list(params),
        "/admin/article-comments",
        { params },
        30_000,
      );
      setItems(response.data);
      setLastPage(response.last_page);
      setTotal(response.total);
      setArticleTotal(response.article_total);
    } catch {
      toast.error("ไม่สามารถโหลดความคิดเห็นได้");
    } finally {
      setLoading(false);
    }
  }, [articleId, filters.reportStatus, filters.sortBy, filters.visibility, page, pageSize, search]);

  useResetPageOnChange(
    setPage,
    JSON.stringify([articleId, pageSize, search, filters.visibility, filters.reportStatus, filters.sortBy]),
  );
  useEffect(() => void load(), [load]);

  const articleGroups = useMemo(() => {
    const groups = new Map<string, ArticleComment[]>();
    for (const comment of items) {
      groups.set(comment.article_id, [...(groups.get(comment.article_id) ?? []), comment]);
    }
    return [...groups.values()];
  }, [items]);

  const updateVisibility = async (comment: CommentReply) => {
    setBusyId(comment.id);
    try {
      await api.patch(`/admin/article-comments/${comment.id}/visibility`, { hidden: !comment.hidden_at });
      toast.success(comment.hidden_at ? "แสดงความคิดเห็นแล้ว" : "ซ่อนความคิดเห็นแล้ว");
      await load();
    } catch {
      toast.error("จัดการความคิดเห็นไม่สำเร็จ");
    } finally {
      setBusyId(null);
    }
  };

  const deleteComment = async () => {
    if (!deleteTarget) return;
    setBusyId(deleteTarget.id);
    try {
      await api.delete(`/admin/article-comments/${deleteTarget.id}`);
      toast.success("ลบความคิดเห็นแล้ว");
      setDeleteTarget(null);
      await load();
    } catch {
      toast.error("ลบความคิดเห็นไม่สำเร็จ");
    } finally {
      setBusyId(null);
    }
  };

  const resolveReports = async () => {
    if (!reportActionTarget) return;
    const { comment, action } = reportActionTarget;
    setBusyId(comment.id);
    try {
      await api.patch(`/admin/article-comments/${comment.id}/resolve-reports`, { action });
      toast.success(action === "dismiss" ? "บันทึกว่าไม่พบการละเมิดแล้ว" : action === "hide" ? "ซ่อนความคิดเห็นและจัดการรายงานแล้ว" : "ลบความคิดเห็นและจัดการรายงานแล้ว");
      setReportActionTarget(null);
      await load();
    } catch {
      toast.error("จัดการรายงานความคิดเห็นไม่สำเร็จ");
    } finally {
      setBusyId(null);
    }
  };

  return (
    <div className="space-y-5">
      <div>
        {articleId && <Button variant="ghost" size="sm" className="mb-3" onClick={() => navigate(openedFromReports ? "/articles/comment-reports" : "/articles/comments")}><ArrowLeft />{openedFromReports ? "กลับหน้ารายงาน" : "กลับไปรายการบทความ"}</Button>}
        <h1 className="text-xl font-semibold text-[var(--color-text-primary)]">{articleId ? "จัดการความคิดเห็น" : "ความคิดเห็นบทความ"}</h1>
      </div>

      <ArticleCommentFilters value={filters} onChange={(value) => { setFilters(value); setPage(1); }} />

      {loading ? (
        articleId ? <ArticleCommentsDetailSkeleton /> : <Card className="p-0"><TableSkeleton columns={6} rows={8} columnWidths={["w-16", "w-48", "w-20", "w-24", "w-36", "w-28"]} /></Card>
      ) : articleGroups.length === 0 ? (
        <Card className="flex flex-col items-center gap-2 p-10 text-center text-[var(--color-text-secondary)]"><MessageCircle className="h-9 w-9" /><p>ไม่พบความคิดเห็น</p></Card>
      ) : articleId ? (
        <ArticleCommentsDetail groups={articleGroups} busyId={busyId} onVisibilityChange={(comment) => void updateVisibility(comment)} onDelete={setDeleteTarget} onResolveReports={(comment, action) => setReportActionTarget({ comment, action })} />
      ) : (
        <ArticleCommentsTable groups={articleGroups} firstRow={(page - 1) * pageSize + 1} onView={(id) => navigate(`/articles/comments/${id}`)} />
      )}

      {!loading && articleGroups.length > 0 && <Pagination current={page} total={lastPage} totalItems={articleId ? total : articleTotal} itemLabel={articleId ? "ความคิดเห็นหลัก" : "บทความ"} onChange={setPage} pageSize={pageSize} onPageSizeChange={setPageSize} />}

      <ArticleCommentActionDialogs deleteTarget={deleteTarget} reportTarget={reportActionTarget} busyId={busyId} onCloseDelete={() => setDeleteTarget(null)} onConfirmDelete={() => void deleteComment()} onCloseReport={() => setReportActionTarget(null)} onConfirmReport={() => void resolveReports()} />
    </div>
  );
}
