import { useEffect, useState, useCallback, useRef } from "react";
import axios from "axios";
import { toast } from "sonner";
import { useNavigate } from "react-router-dom";
import { Plus } from "lucide-react";
import { diagramApi } from "@/lib/api/diagram";
import { symptomApi } from "@/lib/api/symptom";
import { encodeId } from "@/lib/idCodec";
import type { Diagram } from "@/types/diagram";
import type { Symptom } from "@/types/symptom";
import { Card } from "../../../components/ui/Card";
import { Button } from "../../../components/ui/Button";
import { Pagination } from "../../../components/ui/Pagination";
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
  DiagramFilters,
  type DiagramFilterValue,
} from "../components/DiagramFilters";
import { DiagramTable } from "../components/DiagramTable";
import { withRowNumbers } from "@/lib/tableRows";
import { TableSkeleton } from "../../../components/ui/TableSkeleton";
import { FilterBar } from "@/components/ui/FilterBar";
import { usePersistentTableSort } from "@/hooks/usePersistentTableSort";
import { usePersistentTablePagination } from "@/hooks/usePersistentTablePagination";
import { useResetPageOnChange } from "@/hooks/useResetPageOnChange";

export function DiagramsPage() {
  const navigate = useNavigate();

  const [diagrams, setDiagrams] = useState<Diagram[]>([]);
  const [symptoms, setSymptoms] = useState<Symptom[]>([]);
  const [loading, setLoading] = useState(true);
  const [initialLoading, setInitialLoading] = useState(true);
  const { page, setPage, pageSize, setPageSize } =
    usePersistentTablePagination("diagrams");
  const [lastPage, setLastPage] = useState(1);
  const [totalItems, setTotalItems] = useState(0);
  const [filters, setFilters] = useState<DiagramFilterValue>({
    search: "",
    status: "",
    symptom_id: "",
  });

  const { sortKey, setSortKey, sortDirection, setSortDirection } =
    usePersistentTableSort("diagrams");

  const [toggleTarget, setToggleTarget] = useState<Diagram | null>(null);
  const [toggling, setToggling] = useState(false);

  const [deleteTarget, setDeleteTarget] = useState<Diagram | null>(null);
  const [deleting, setDeleting] = useState(false);

  const abortRef = useRef<AbortController | null>(null);

  useEffect(() => {
    symptomApi
      .list({ per_page: 200 })
      .then((res) => setSymptoms(res.data))
      .catch(() => toast.error("ไม่สามารถโหลดรายการอาการได้"));
  }, []);

  const fetchDiagrams = useCallback(async () => {
    abortRef.current?.abort();
    const controller = new AbortController();
    abortRef.current = controller;

    setLoading(true);
    try {
      const res = await diagramApi.list(
        {
          search: filters.search || undefined,
          status: filters.status || undefined,
          symptom_id: filters.symptom_id || undefined,
          page,
          per_page: pageSize,
          sort_by: sortKey ?? undefined,
          sort_direction: sortDirection ?? undefined,
        },
        controller.signal,
      );
      setDiagrams(res.data);
      setLastPage(res.meta?.last_page ?? 1);
      setTotalItems(res.meta?.total ?? 0);
    } catch (err) {
      if (
        axios.isCancel(err) ||
        (axios.isAxiosError(err) && err.code === "ERR_CANCELED")
      )
        return;
      toast.error("ไม่สามารถโหลดข้อมูลแผนภูมิได้");
    } finally {
      if (abortRef.current === controller) {
        setLoading(false);
        setInitialLoading(false);
      }
    }
  }, [filters, page, pageSize, sortKey, sortDirection]);

  useEffect(() => {
    fetchDiagrams();
    return () => abortRef.current?.abort();
  }, [fetchDiagrams]);

  useResetPageOnChange(setPage, JSON.stringify([filters, pageSize]));

  const handleSortChange = (key: string, direction: "asc" | "desc" | null) => {
    setSortKey(direction ? key : null);
    setSortDirection(direction);
    setPage(1);
  };

  const openCreate = () => navigate("/diagrams/create");
  const openEdit = (diagram: Diagram) =>
    navigate(`/diagrams/edit/${encodeId(diagram.diagram_id)}`);
  const openFlow = (diagram: Diagram) =>
    navigate(`/diagrams/flow/${encodeId(diagram.diagram_id)}`);

  const handleToggleStatus = async () => {
    if (!toggleTarget) return;
    setToggling(true);
    try {
      const nextStatus = toggleTarget.status === "1" ? "2" : "1";
      await diagramApi.update(toggleTarget.diagram_id, {
        status: nextStatus,
      });
      toast.success(
        nextStatus === "1"
          ? "เปิดใช้งานแผนภูมิสำเร็จ"
          : "ปิดใช้งานแผนภูมิสำเร็จ",
      );
      setToggleTarget(null);
      fetchDiagrams();
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
      await diagramApi.delete(deleteTarget.diagram_id);
      toast.success("ลบแผนภูมิสำเร็จ");
      setDeleteTarget(null);
      fetchDiagrams();
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
            จัดการแผนภูมิการวินิจฉัย
          </h1>
          {/* <p className="mt-1 text-sm text-[var(--color-text-secondary)]">
            แผนภูมิ (decision tree) สำหรับซักถามอาการและวินิจฉัยเบื้องต้น
          </p> */}
        </div>

        <Button onClick={openCreate}>
          <Plus className="h-4 w-4" />
          เพิ่มแผนภูมิ
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
        <DiagramFilters
          value={filters}
          onChange={setFilters}
          symptoms={symptoms}
        />
      </FilterBar>

      <Card className="mt-4 p-0">
        {initialLoading ? (
          <TableSkeleton
            columns={5}
            rows={pageSize}
            columnWidths={["w-20", "w-56", "w-40", "w-20", "w-16"]}
          />
        ) : (
          <div className={loading ? "opacity-50 transition-opacity" : ""}>
            <DiagramTable
              data={withRowNumbers(diagrams, (page - 1) * pageSize + 1)}
              loading={false}
              onEdit={openEdit}
              onViewFlow={openFlow}
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

      {/* Toggle status confirm */}
      <AlertDialog
        open={!!toggleTarget}
        onOpenChange={(open) => !open && setToggleTarget(null)}
      >
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>
              {toggleTarget?.status === "1"
                ? "ยืนยันการปิดใช้งานแผนภูมิ"
                : "ยืนยันการเปิดใช้งานแผนภูมิ"}
            </AlertDialogTitle>
            <AlertDialogDescription>
              {toggleTarget?.status === "1"
                ? `แผนภูมิ "${toggleTarget?.diagram_name}" จะไม่ถูกใช้ในการประเมินอาการ`
                : `แผนภูมิ "${toggleTarget?.diagram_name}" จะกลับมาใช้งานได้ตามปกติ`}
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
            <AlertDialogTitle>ยืนยันการลบแผนภูมิ</AlertDialogTitle>
            <AlertDialogDescription>
              แผนภูมิ "{deleteTarget?.diagram_name}" จะถูกลบอย่างถาวร
              หากมีกรอบคำถามอยู่ในแผนภูมินี้ จะไม่สามารถลบได้
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel disabled={deleting}>ยกเลิก</AlertDialogCancel>
            <AlertDialogAction onClick={handleDelete} loading={deleting}>
              ลบแผนภูมิ
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </div>
  );
}
