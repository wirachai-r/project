import { useEffect, useState, useCallback, useRef } from "react";
import axios from "axios";
import { toast } from "sonner";
import { useNavigate } from "react-router-dom";
import { Plus } from "lucide-react";
import { diseaseApi } from "@/lib/api/disease";
import { diseaseCategoryApi } from "@/lib/api/diseaseCategory";
import { encodeId } from "@/lib/idCodec";
import type { Disease } from "@/types/disease";
import type { DiseaseCategory } from "@/types/diseaseCategory";
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
  DiseaseFilters,
  type DiseaseFilterValue,
} from "../components/DiseaseFilters";
import { DiseaseTable } from "../components/DiseaseTable";
import { withRowNumbers } from "@/lib/tableRows";
import { TableSkeleton } from "../../../components/ui/TableSkeleton";
import { FilterBar } from "@/components/ui/FilterBar";
import { usePersistentTableSort } from "@/hooks/usePersistentTableSort";
import { usePersistentTablePagination } from "@/hooks/usePersistentTablePagination";
import { useResetPageOnChange } from "@/hooks/useResetPageOnChange";

export function DiseasesPage() {
  const navigate = useNavigate();

  const [diseases, setDiseases] = useState<Disease[]>([]);
  const [categories, setCategories] = useState<DiseaseCategory[]>([]);
  const [loading, setLoading] = useState(true);
  const [initialLoading, setInitialLoading] = useState(true);
  const { page, setPage, pageSize, setPageSize } =
    usePersistentTablePagination("diseases");
  const [lastPage, setLastPage] = useState(1);
  const [totalItems, setTotalItems] = useState(0);
  const [filters, setFilters] = useState<DiseaseFilterValue>({
    search: "",
    status: "",
    disease_category_id: "",
  });

  const { sortKey, setSortKey, sortDirection, setSortDirection } =
    usePersistentTableSort("diseases");

  const [viewItem, setViewItem] = useState<Disease | null>(null);

  const [toggleTarget, setToggleTarget] = useState<Disease | null>(null);
  const [toggling, setToggling] = useState(false);

  const [deleteTarget, setDeleteTarget] = useState<Disease | null>(null);
  const [deleting, setDeleting] = useState(false);

  const abortRef = useRef<AbortController | null>(null);

  useEffect(() => {
    diseaseCategoryApi
      .list({ per_page: 100 })
      .then((res) => setCategories(res.data))
      .catch(() => toast.error("ไม่สามารถโหลดหมวดหมู่โรคได้"));
  }, []);

  const fetchDiseases = useCallback(async () => {
    abortRef.current?.abort();
    const controller = new AbortController();
    abortRef.current = controller;

    setLoading(true);
    try {
      const res = await diseaseApi.list(
        {
          search: filters.search || undefined,
          status: filters.status || undefined,
          disease_category_id: filters.disease_category_id || undefined,
          page,
          per_page: pageSize,
          sort_by: sortKey ?? undefined,
          sort_direction: sortDirection ?? undefined,
        },
        controller.signal,
      );
      setDiseases(res.data);
      setLastPage(res.meta?.last_page ?? 1);
      setTotalItems(res.meta?.total ?? 0);
    } catch (err) {
      if (
        axios.isCancel(err) ||
        (axios.isAxiosError(err) && err.code === "ERR_CANCELED")
      )
        return;
      toast.error("ไม่สามารถโหลดข้อมูลโรคได้");
    } finally {
      if (abortRef.current === controller) {
        setLoading(false);
        setInitialLoading(false);
      }
    }
  }, [filters, page, pageSize, sortKey, sortDirection]);

  useEffect(() => {
    fetchDiseases();
    return () => abortRef.current?.abort();
  }, [fetchDiseases]);

  useResetPageOnChange(setPage, JSON.stringify([filters, pageSize]));

  const handleSortChange = (
    key: string,
    direction: "asc" | "desc" | null,
  ) => {
    setSortKey(direction ? key : null);
    setSortDirection(direction);
    setPage(1);
  };

  const openCreate = () => {
    navigate("/diseases/create");
  };

  const openEdit = (disease: Disease) => {
    navigate(`/diseases/edit/${encodeId(disease.disease_id)}`);
  };

  const handleToggleStatus = async () => {
    if (!toggleTarget) return;
    setToggling(true);
    try {
      const nextStatus = toggleTarget.status === "1" ? "2" : "1";
      await diseaseApi.update(toggleTarget.disease_id, {
        status: nextStatus,
      });
      toast.success(
        nextStatus === "1" ? "เปิดใช้งานโรคสำเร็จ" : "ปิดใช้งานโรคสำเร็จ",
      );
      setToggleTarget(null);
      fetchDiseases();
    } catch {
      toast.error("ดำเนินการไม่สำเร็จ กรุณาลองใหม่");
    } finally {
      setToggling(false);
    }
  };

  const handleDelete = async () => {
    if (!deleteTarget) return;
    setDeleting(true);
    try {
      await diseaseApi.delete(deleteTarget.disease_id);
      toast.success("ลบโรคสำเร็จ");
      setDeleteTarget(null);
      fetchDiseases();
    } catch (err) {
      const message =
        (axios.isAxiosError(err) && err.response?.data?.message) ||
        "ไม่สามารถลบได้ กรุณาลองใหม่";
      toast.error(message);
    } finally {
      setDeleting(false);
    }
  };

  return (
    <div>
      <div className="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-xl font-semibold text-[var(--color-text-primary)]">
            จัดการข้อมูลโรค
          </h1>
          {/* <p className="mt-1 text-sm text-[var(--color-text-secondary)]">
            ดูและจัดการข้อมูลโรคทั้งหมดในระบบ
          </p> */}
        </div>

        <Button onClick={openCreate}>
          <Plus className="h-4 w-4" />
          เพิ่มโรค
        </Button>
      </div>

      <FilterBar
        sortKey={sortKey}
        direction={sortDirection}
        dateSortKey="updated_at"
        nameSortKey="name"
        onSortChange={(key, direction) => {
          setSortKey(key);
          setSortDirection(direction);
          setPage(1);
        }}
      >
        <DiseaseFilters
          value={filters}
          onChange={setFilters}
          categories={categories}
        />
      </FilterBar>

      <Card className="mt-4 p-0">
        {initialLoading ? (
          <TableSkeleton
            columns={5}
            rows={pageSize}
            columnWidths={["w-20", "w-48", "w-32", "w-20", "w-16"]}
          />
        ) : (
          <div className={loading ? "opacity-50 transition-opacity" : ""}>
            <DiseaseTable
              data={withRowNumbers(diseases, (page - 1) * pageSize + 1)}
              loading={false}
              onView={setViewItem}
              onEdit={openEdit}
              onToggleStatus={setToggleTarget}
              onDelete={setDeleteTarget}
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

      {/* View dialog (คงไว้ ดูรายละเอียดแบบ read-only) */}
      <Dialog
        open={!!viewItem}
        onOpenChange={(open) => !open && setViewItem(null)}
      >
        <DialogContent>
          <DialogHeader>
            <DialogTitle>รายละเอียดโรค</DialogTitle>
          </DialogHeader>
          {viewItem && (
            <div className="space-y-4">
              <div className="flex items-center gap-3">
                {viewItem.disease_image && (
                  <img
                    src={viewItem.disease_image}
                    alt={viewItem.disease_name}
                    className="h-12 w-12 shrink-0 rounded-lg object-cover"
                  />
                )}
                <div>
                  <p className="font-medium text-[var(--color-text-primary)]">
                    {viewItem.disease_name}
                  </p>
                  {viewItem.disease_name_en && (
                    <p className="text-sm text-[var(--color-text-secondary)]">
                      {viewItem.disease_name_en}
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
                    {viewItem.status === "1" ? "ใช้งานได้" : "ปิดใช้งาน"}
                  </dd>
                </div>
                <div className="col-span-2">
                  <dt className="text-[var(--color-text-secondary)]">
                    คำอธิบาย
                  </dt>
                  <dd
                    className="prose prose-sm max-w-none text-[var(--color-text-primary)]"
                    dangerouslySetInnerHTML={{
                      __html: viewItem.description || "-",
                    }}
                  />
                </div>
                <div className="col-span-2">
                  <dt className="text-[var(--color-text-secondary)]">สาเหตุ</dt>
                  <dd
                    className="prose prose-sm max-w-none text-[var(--color-text-primary)]"
                    dangerouslySetInnerHTML={{
                      __html: viewItem.cause || "-",
                    }}
                  />
                </div>
                <div className="col-span-2">
                  <dt className="text-[var(--color-text-secondary)]">
                    ลักษณะอาการ
                  </dt>
                  <dd
                    className="prose prose-sm max-w-none text-[var(--color-text-primary)]"
                    dangerouslySetInnerHTML={{
                      __html: viewItem.symptom_description || "-",
                    }}
                  />
                </div>
                <div className="col-span-2">
                  <dt className="text-[var(--color-text-secondary)]">
                    การป้องกัน
                  </dt>
                  <dd
                    className="prose prose-sm max-w-none text-[var(--color-text-primary)]"
                    dangerouslySetInnerHTML={{
                      __html: viewItem.prevention || "-",
                    }}
                  />
                </div>
                <div className="col-span-2">
                  <dt className="text-[var(--color-text-secondary)]">ภาวะแทรกซ้อน</dt>
                  <dd className="prose prose-sm max-w-none text-[var(--color-text-primary)]" dangerouslySetInnerHTML={{ __html: viewItem.complications || "-" }} />
                </div>
                <div className="col-span-2">
                  <dt className="text-[var(--color-text-secondary)]">การวินิจฉัย</dt>
                  <dd className="prose prose-sm max-w-none text-[var(--color-text-primary)]" dangerouslySetInnerHTML={{ __html: viewItem.diagnosis || "-" }} />
                </div>
                <div className="col-span-2">
                  <dt className="text-[var(--color-text-secondary)]">การรักษาโดยแพทย์</dt>
                  <dd className="prose prose-sm max-w-none text-[var(--color-text-primary)]" dangerouslySetInnerHTML={{ __html: viewItem.medical_treatment || "-" }} />
                </div>
                <div className="col-span-2">
                  <dt className="text-[var(--color-text-secondary)]">การดูแลตนเอง</dt>
                  <dd className="prose prose-sm max-w-none text-[var(--color-text-primary)]" dangerouslySetInnerHTML={{ __html: viewItem.self_care || "-" }} />
                </div>
                <div className="col-span-2">
                  <dt className="text-[var(--color-text-secondary)]">ควรกลับไปพบแพทย์เมื่อใด</dt>
                  <dd className="prose prose-sm max-w-none text-[var(--color-text-primary)]" dangerouslySetInnerHTML={{ __html: viewItem.when_to_see_doctor || "-" }} />
                </div>
                <div className="col-span-2">
                  <dt className="text-[var(--color-text-secondary)]">ข้อแนะนำ</dt>
                  <dd className="prose prose-sm max-w-none text-[var(--color-text-primary)]" dangerouslySetInnerHTML={{ __html: viewItem.recommendations || "-" }} />
                </div>
              </dl>
            </div>
          )}
        </DialogContent>
      </Dialog>

      {/* Toggle status confirm */}
      <AlertDialog
        open={!!toggleTarget}
        onOpenChange={(open) => !open && setToggleTarget(null)}
      >
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>
              {toggleTarget?.status === "1"
                ? "ยืนยันการปิดใช้งานโรค"
                : "ยืนยันการเปิดใช้งานโรค"}
            </AlertDialogTitle>
            <AlertDialogDescription>
              {toggleTarget?.status === "1"
                ? `โรค "${toggleTarget?.disease_name}" จะไม่แสดงในฝั่งผู้ใช้`
                : `โรค "${toggleTarget?.disease_name}" จะกลับมาแสดงตามปกติ`}
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel disabled={toggling}>ยกเลิก</AlertDialogCancel>
            <AlertDialogAction onClick={handleToggleStatus} loading={toggling}>
              ยืนยัน
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>

      {/* Delete confirm */}
      <AlertDialog
        open={!!deleteTarget}
        onOpenChange={(open) => !open && setDeleteTarget(null)}
      >
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>ยืนยันการลบโรค</AlertDialogTitle>
            <AlertDialogDescription>
              โรค "{deleteTarget?.disease_name}" จะถูกลบอย่างถาวร
              หากมีคำสั่งการรักษาของโรคนี้อยู่ จะไม่สามารถลบได้
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel disabled={deleting}>ยกเลิก</AlertDialogCancel>
            <AlertDialogAction onClick={handleDelete} loading={deleting}>
              ลบโรค
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </div>
  );
}
