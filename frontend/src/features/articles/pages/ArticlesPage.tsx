import { useEffect, useState, useCallback, useRef } from "react";
import axios from "axios";
import { toast } from "sonner";
import { useNavigate } from "react-router-dom";
import { Plus } from "lucide-react";
import { articleApi } from "@/lib/api/article";
import { articleCategoryApi } from "@/lib/api/articleCategory";
import { getErrorMessage } from "@/lib/getErrorMessage";
import { encodeId } from "@/lib/idCodec";
import type { Article } from "@/types/article";
import type { ArticleCategory } from "@/types/articleCategory";
import { Card } from "../../../components/ui/Card";
import { Button } from "../../../components/ui/Button";
import { Pagination } from "../../../components/ui/Pagination";
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
} from "../../../components/ui/Dialog";
import {
  AlertDialog,
  AlertDialogContent,
  AlertDialogHeader,
  AlertDialogTitle,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogAction,
  AlertDialogCancel,
} from "../../../components/ui/AlertDialog";
import {
  ArticleFilters,
  type ArticleFilterValue,
} from "../components/ArticleFilters";
import { ArticleTable } from "../components/ArticleTable";
import { TableSkeleton } from "../../../components/ui/TableSkeleton";

export function ArticlesPage() {
  const navigate = useNavigate();

  const [articles, setArticles] = useState<Article[]>([]);
  const [categories, setCategories] = useState<ArticleCategory[]>([]);
  const [loading, setLoading] = useState(true);
  const [initialLoading, setInitialLoading] = useState(true);
  const [page, setPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [pageSize, setPageSize] = useState(10);
  const [totalItems, setTotalItems] = useState(0);
  const [filters, setFilters] = useState<ArticleFilterValue>({
    search: "",
    status: "",
    article_category_id: "",
  });

  const [sortKey, setSortKey] = useState<string | null>(null);
  const [sortDirection, setSortDirection] = useState<"asc" | "desc" | null>(
    null,
  );

  const [viewItem, setViewItem] = useState<Article | null>(null);

  const [deleteTarget, setDeleteTarget] = useState<Article | null>(null);
  const [deleting, setDeleting] = useState(false);

  const abortRef = useRef<AbortController | null>(null);

  useEffect(() => {
    articleCategoryApi
      .list({ per_page: 100 })
      .then((res) => setCategories(res.data))
      .catch(() => toast.error("ไม่สามารถโหลดหมวดหมู่บทความได้"));
  }, []);

  const fetchArticles = useCallback(async () => {
    abortRef.current?.abort();
    const controller = new AbortController();
    abortRef.current = controller;

    setLoading(true);
    try {
      const res = await articleApi.list(
        {
          search: filters.search || undefined,
          status: filters.status || undefined,
          article_category_id: filters.article_category_id || undefined,
          page,
          per_page: pageSize,
          sort_by: sortKey ?? undefined,
          sort_direction: sortDirection ?? undefined,
        },
        controller.signal,
      );
      setArticles(res.data);
      setLastPage(res.meta?.last_page ?? 1);
      setTotalItems(res.meta?.total ?? 0);
    } catch (err) {
      if (
        axios.isCancel(err) ||
        (axios.isAxiosError(err) && err.code === "ERR_CANCELED")
      )
        return;
      toast.error("ไม่สามารถโหลดข้อมูลบทความได้");
    } finally {
      if (abortRef.current === controller) {
        setLoading(false);
        setInitialLoading(false);
      }
    }
  }, [filters, page, pageSize, sortKey, sortDirection]);

  useEffect(() => {
    fetchArticles();
    return () => abortRef.current?.abort();
  }, [fetchArticles]);

  useEffect(() => {
    setPage(1);
  }, [filters, pageSize]);

  const handleSortChange = (
    key: string,
    direction: "asc" | "desc" | null,
  ) => {
    setSortKey(direction ? key : null);
    setSortDirection(direction);
    setPage(1);
  };

  const openCreate = () => {
    navigate("/articles/create");
  };

  const openEdit = (article: Article) => {
    navigate(`/articles/edit/${encodeId(article.article_id)}`);
  };

  const handleStatusChange = async (
    article: Article,
    status: "1" | "2" | "3",
  ) => {
    const promise = articleApi.update(article.article_id, { status });

    toast.promise(promise, {
      loading: "กำลังเปลี่ยนสถานะ...",
      success: "เปลี่ยนสถานะสำเร็จ",
      error: (err) => getErrorMessage(err),
    });

    await promise.then(fetchArticles).catch(() => {});
  };

  const handleDelete = async () => {
    if (!deleteTarget) return;
    setDeleting(true);
    try {
      await articleApi.delete(deleteTarget.article_id);
      toast.success("ลบบทความสำเร็จ");
      setDeleteTarget(null);
      fetchArticles();
    } catch (err) {
      toast.error(getErrorMessage(err));
    } finally {
      setDeleting(false);
    }
  };

  return (
    <div>
      <div className="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-xl font-semibold text-[var(--color-text-primary)]">
            จัดการบทความ
          </h1>
          <p className="mt-1 text-sm text-[var(--color-text-secondary)]">
            ดูและจัดการบทความทั้งหมดในระบบ
          </p>
        </div>

        <Button onClick={openCreate}>
          <Plus className="h-4 w-4" />
          เพิ่มบทความ
        </Button>
      </div>

      <div className="mb-4">
        <ArticleFilters
          value={filters}
          onChange={setFilters}
          categories={categories}
        />
      </div>

      <Card className="p-0">
        {initialLoading ? (
          <TableSkeleton
            columns={5}
            rows={pageSize}
            columnWidths={["w-20", "w-48", "w-32", "w-20", "w-16"]}
          />
        ) : (
          <div className={loading ? "opacity-50 transition-opacity" : ""}>
            <ArticleTable
              data={articles}
              loading={false}
              onView={setViewItem}
              onEdit={openEdit}
              onDelete={setDeleteTarget}
              onStatusChange={handleStatusChange}
              sortKey={sortKey}
              sortDirection={sortDirection}
              onSortChange={handleSortChange}
            />
          </div>
        )}
      </Card>

      {totalItems > 0 && (
        <div className="mt-4">
          <Pagination
            current={page}
            total={lastPage}
            onChange={setPage}
            pageSize={pageSize}
            onPageSizeChange={setPageSize}
            totalItems={totalItems}
          />
        </div>
      )}

      {/* View dialog */}
      <Dialog
        open={!!viewItem}
        onOpenChange={(open) => !open && setViewItem(null)}
      >
        <DialogContent>
          <DialogHeader>
            <DialogTitle>รายละเอียดบทความ</DialogTitle>
          </DialogHeader>
          {viewItem && (
            <div className="space-y-4">
              <div className="flex items-center gap-3">
                {viewItem.thumbnail && (
                  <img
                    src={viewItem.thumbnail}
                    alt={viewItem.title}
                    className="h-12 w-12 shrink-0 rounded-lg object-cover"
                  />
                )}
                <div>
                  <p className="font-medium text-[var(--color-text-primary)]">
                    {viewItem.title}
                  </p>
                  {viewItem.title_en && (
                    <p className="text-sm text-[var(--color-text-secondary)]">
                      {viewItem.title_en}
                    </p>
                  )}
                </div>
              </div>

              <dl className="grid grid-cols-2 gap-3 text-sm">
                <div>
                  <dt className="text-[var(--color-text-secondary)]">
                    หมวดหมู่
                  </dt>
                  <dd className="text-[var(--color-text-primary)]">
                    {viewItem.category?.category_name ?? "-"}
                  </dd>
                </div>
                <div>
                  <dt className="text-[var(--color-text-secondary)]">สถานะ</dt>
                  <dd className="text-[var(--color-text-primary)]">
                    {viewItem.status === "1"
                      ? "เผยแพร่"
                      : viewItem.status === "2"
                        ? "ฉบับร่าง"
                        : "เก็บถาวร"}
                  </dd>
                </div>
                <div className="col-span-2">
                  <dt className="text-[var(--color-text-secondary)]">
                    เนื้อหา
                  </dt>
                  <dd
                    className="prose prose-sm max-w-none text-[var(--color-text-primary)]"
                    dangerouslySetInnerHTML={{
                      __html: viewItem.content || "-",
                    }}
                  />
                </div>
              </dl>
            </div>
          )}
        </DialogContent>
      </Dialog>

      {/* Delete confirm */}
      <AlertDialog
        open={!!deleteTarget}
        onOpenChange={(open) => !open && setDeleteTarget(null)}
      >
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>ยืนยันการลบบทความ</AlertDialogTitle>
            <AlertDialogDescription>
              บทความ "{deleteTarget?.title}" จะถูกลบอย่างถาวร
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel disabled={deleting}>ยกเลิก</AlertDialogCancel>
            <AlertDialogAction onClick={handleDelete} loading={deleting}>
              ลบบทความ
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </div>
  );
}