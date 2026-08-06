import { useEffect, useState, useCallback, useRef } from "react";
import axios from "axios";
import { toast } from "sonner";
import { useNavigate } from "react-router-dom";
import { Plus } from "lucide-react";
import { firstAidApi } from "@/lib/api/firstaid";
import { firstAidCategoryApi } from "@/lib/api/firstAidCategory";
import { getErrorMessage } from "@/lib/getErrorMessage";
import { encodeId } from "@/lib/idCodec";
import type { FirstAid } from "@/types/firstaid";
import type { FirstAidCategory } from "@/types/firstAidCategory";
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
  FirstAidFilters,
  type FirstAidFilterValue,
} from "../components/FirstAidFilters";
import { FirstAidTable } from "../components/FirstAidTable";
import { TableSkeleton } from "../../../components/ui/TableSkeleton";
import { usePersistentTableSort } from "@/hooks/usePersistentTableSort";
import { usePersistentTablePagination } from "@/hooks/usePersistentTablePagination";
import { useResetPageOnChange } from "@/hooks/useResetPageOnChange";

export function FirstAidsPage() {
  const navigate = useNavigate();

  const [firstAids, setFirstAids] = useState<FirstAid[]>([]);
  const [categories, setCategories] = useState<FirstAidCategory[]>([]);
  const [loading, setLoading] = useState(true);
  const [initialLoading, setInitialLoading] = useState(true);
  const { page, setPage, pageSize, setPageSize } =
    usePersistentTablePagination("firstaids");
  const [lastPage, setLastPage] = useState(1);
  const [totalItems, setTotalItems] = useState(0);
  const [filters, setFilters] = useState<FirstAidFilterValue>({
    search: "",
    status: "",
    first_aid_category_id: "",
  });

  const { sortKey, setSortKey, sortDirection, setSortDirection } =
    usePersistentTableSort("firstaids");

  const [viewItem, setViewItem] = useState<FirstAid | null>(null);

  const [deleteTarget, setDeleteTarget] = useState<FirstAid | null>(null);
  const [deleting, setDeleting] = useState(false);

  const abortRef = useRef<AbortController | null>(null);

  useEffect(() => {
    firstAidCategoryApi
      .list({ per_page: 100 })
      .then((res) => setCategories(res.data))
      .catch(() => toast.error("ไม่สามารถโหลดหมวดหมู่ปฐมพยาบาลได้"));
  }, []);

  const fetchFirstAids = useCallback(async () => {
    abortRef.current?.abort();
    const controller = new AbortController();
    abortRef.current = controller;

    setLoading(true);
    try {
      const res = await firstAidApi.list(
        {
          search: filters.search || undefined,
          status: filters.status || undefined,
          first_aid_category_id: filters.first_aid_category_id || undefined,
          page,
          per_page: pageSize,
          sort_by: sortKey ?? undefined,
          sort_direction: sortDirection ?? undefined,
        },
        controller.signal,
      );
      setFirstAids(res.data);
      setLastPage(res.meta?.last_page ?? 1);
      setTotalItems(res.meta?.total ?? 0);
    } catch (err) {
      if (
        axios.isCancel(err) ||
        (axios.isAxiosError(err) && err.code === "ERR_CANCELED")
      )
        return;
      toast.error("ไม่สามารถโหลดข้อมูลปฐมพยาบาลได้");
    } finally {
      if (abortRef.current === controller) {
        setLoading(false);
        setInitialLoading(false);
      }
    }
  }, [filters, page, pageSize, sortKey, sortDirection]);

  useEffect(() => {
    fetchFirstAids();
    return () => abortRef.current?.abort();
  }, [fetchFirstAids]);

  useResetPageOnChange(setPage, JSON.stringify([filters, pageSize]));

  const handleSortChange = (key: string, direction: "asc" | "desc" | null) => {
    setSortKey(direction ? key : null);
    setSortDirection(direction);
    setPage(1);
  };

  const openCreate = () => {
    navigate("/first-aids/create");
  };

  const openEdit = (firstAid: FirstAid) => {
    navigate(`/first-aids/edit/${encodeId(firstAid.first_aid_id)}`);
  };
  
  const handleStatusChange = async (
    firstAid: FirstAid,
    status: "1" | "2" | "3",
  ) => {
    const promise = firstAidApi.update(firstAid.first_aid_id, { status });

    toast.promise(promise, {
      loading: "กำลังเปลี่ยนสถานะ...",
      success: "เปลี่ยนสถานะสำเร็จ",
      error: (err) => getErrorMessage(err),
    });

    await promise.then(fetchFirstAids).catch(() => {});
  };

  const handleDelete = async () => {
    if (!deleteTarget) return;
    setDeleting(true);
    try {
      await firstAidApi.delete(deleteTarget.first_aid_id);
      toast.success("ลบข้อมูลปฐมพยาบาลสำเร็จ");
      setDeleteTarget(null);
      fetchFirstAids();
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
            จัดการข้อมูลปฐมพยาบาล
          </h1>
          <p className="mt-1 text-sm text-[var(--color-text-secondary)]">
            ดูและจัดการข้อมูลปฐมพยาบาลทั้งหมดในระบบ
          </p>
        </div>

        <Button onClick={openCreate}>
          <Plus className="h-4 w-4" />
          เพิ่มข้อมูลปฐมพยาบาล
        </Button>
      </div>

      <div className="mb-4">
        <FirstAidFilters
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
            <FirstAidTable
              data={firstAids}
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
        <DialogContent
          maxWidth="xl"
          className="max-h-[100dvh] overflow-y-auto overscroll-contain md:max-h-[90dvh]"
        >
          <DialogHeader>
            <DialogTitle>รายละเอียดข้อมูลปฐมพยาบาล</DialogTitle>
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
            <AlertDialogTitle>ยืนยันการลบข้อมูลปฐมพยาบาล</AlertDialogTitle>
            <AlertDialogDescription>
              "{deleteTarget?.title}" จะถูกลบอย่างถาวร
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel disabled={deleting}>ยกเลิก</AlertDialogCancel>
            <AlertDialogAction onClick={handleDelete} loading={deleting}>
              ลบข้อมูลปฐมพยาบาล
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </div>
  );
}
