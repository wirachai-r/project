import { useEffect, useState, useCallback, useRef } from "react";
import axios from "axios";
import { toast } from "sonner";
import { useNavigate } from "react-router-dom";
import { Plus } from "lucide-react";
import { diagnosisRuleApi } from "@/lib/api/diagnosisRule";
import { diagramApi } from "@/lib/api/diagram";
import { encodeId } from "@/lib/idCodec";
import type { DiagnosisRule } from "@/types/diagnosisRule";
import { getRuleDisplayName } from "@/types/diagnosisRule";
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
  DiagnosisRuleFilters,
  type DiagnosisRuleFilterValue,
} from "../components/DiagnosisRuleFilters";
import { DiagnosisRuleTable } from "../components/DiagnosisRuleTable";
import { TableSkeleton } from "../../../components/ui/TableSkeleton";
import { FilterBar } from "@/components/ui/FilterBar";
import { usePersistentTableSort } from "@/hooks/usePersistentTableSort";
import { usePersistentTablePagination } from "@/hooks/usePersistentTablePagination";
import { useResetPageOnChange } from "@/hooks/useResetPageOnChange";

interface DiagramOption {
  diagram_id: string;
  diagram_name: string;
}

export function DiagnosisRulesPage() {
  const navigate = useNavigate();

  const [rules, setRules] = useState<DiagnosisRule[]>([]);
  const [diagrams, setDiagrams] = useState<DiagramOption[]>([]);
  const [loading, setLoading] = useState(true);
  const [initialLoading, setInitialLoading] = useState(true);
  const { page, setPage, pageSize, setPageSize } =
    usePersistentTablePagination("diagnosis-rules");
  const [lastPage, setLastPage] = useState(1);
  const [totalItems, setTotalItems] = useState(0);
  const [filters, setFilters] = useState<DiagnosisRuleFilterValue>({
    search: "",
    status: "",
    diagram_id: "",
    urgency_level: "",
  });

  const { sortKey, setSortKey, sortDirection, setSortDirection } =
    usePersistentTableSort("diagnosis-rules");

  const [toggleTarget, setToggleTarget] = useState<DiagnosisRule | null>(null);
  const [toggling, setToggling] = useState(false);

  const [deleteTarget, setDeleteTarget] = useState<DiagnosisRule | null>(null);
  const [deleting, setDeleting] = useState(false);

  const abortRef = useRef<AbortController | null>(null);

  useEffect(() => {
    diagramApi
      .list({ per_page: 200 })
      .then((res) => setDiagrams(res.data))
      .catch(() => toast.error("ไม่สามารถโหลดรายการแผนภูมิได้"));
  }, []);

  const fetchRules = useCallback(async () => {
    abortRef.current?.abort();
    const controller = new AbortController();
    abortRef.current = controller;

    setLoading(true);
    try {
      const res = await diagnosisRuleApi.list(
        {
          search: filters.search || undefined,
          status: filters.status || undefined,
          diagram_id: filters.diagram_id || undefined,
          urgency_level: filters.urgency_level || undefined,
          page,
          per_page: pageSize,
          sort_by:
            sortKey === "urgency_level" || sortKey === "created_at"
              ? sortKey
              : undefined,
          sort_direction: sortDirection ?? undefined,
        },
        controller.signal,
      );
      setRules(res.data);
      setLastPage(res.meta?.last_page ?? 1);
      setTotalItems(res.meta?.total ?? 0);
    } catch (err) {
      if (
        axios.isCancel(err) ||
        (axios.isAxiosError(err) && err.code === "ERR_CANCELED")
      )
        return;
      toast.error("ไม่สามารถโหลดข้อมูลกฎการวินิจฉัยได้");
    } finally {
      if (abortRef.current === controller) {
        setLoading(false);
        setInitialLoading(false);
      }
    }
  }, [filters, page, pageSize, sortKey, sortDirection]);

  useEffect(() => {
    fetchRules();
    return () => abortRef.current?.abort();
  }, [fetchRules]);

  useResetPageOnChange(setPage, JSON.stringify([filters, pageSize]));

  const handleSortChange = (
    key: string,
    direction: "asc" | "desc" | null,
  ) => {
    setSortKey(direction ? key : null);
    setSortDirection(direction);
    setPage(1);
  };

  const openCreate = () => navigate("/diagnosis-rules/create");
  const openEdit = (rule: DiagnosisRule) =>
    navigate(`/diagnosis-rules/edit/${encodeId(rule.rule_id)}`);

  const handleToggleStatus = async () => {
    if (!toggleTarget) return;
    setToggling(true);
    try {
      const nextStatus = toggleTarget.status === "1" ? "2" : "1";
      await diagnosisRuleApi.update(toggleTarget.rule_id, { status: nextStatus });
      toast.success(
        nextStatus === "1" ? "เปิดใช้งานกฎสำเร็จ" : "ปิดใช้งานกฎสำเร็จ",
      );
      setToggleTarget(null);
      fetchRules();
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
      await diagnosisRuleApi.delete(deleteTarget.rule_id);
      toast.success("ลบกฎการวินิจฉัยสำเร็จ");
      setDeleteTarget(null);
      fetchRules();
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
            จัดการกฎการวินิจฉัย
          </h1>
          {/* <p className="mt-1 text-sm text-[var(--color-text-secondary)]">
            กำหนดเงื่อนไขและระดับความเร่งด่วน เพื่อสรุปผลการประเมินอาการ
          </p> */}
        </div>

        <Button onClick={openCreate}>
          <Plus className="h-4 w-4" />
          เพิ่มกฎการวินิจฉัย
        </Button>
      </div>

      <FilterBar
        sortKey={sortKey}
        direction={sortDirection}
        dateSortKey="created_at"
        onSortChange={(key, direction) => {
          setSortKey(key);
          setSortDirection(direction);
          setPage(1);
        }}
      >
        <DiagnosisRuleFilters
          value={filters}
          onChange={setFilters}
          diagrams={diagrams}
        />
      </FilterBar>

      <Card className="mt-4 p-0">
        {initialLoading ? (
          <TableSkeleton
            columns={7}
            rows={pageSize}
            columnWidths={["w-40", "w-32", "w-32", "w-24", "w-20", "w-20", "w-16"]}
          />
        ) : (
          <div className={loading ? "opacity-50 transition-opacity" : ""}>
            <DiagnosisRuleTable
              data={rules}
              loading={false}
              onEdit={openEdit}
              onToggleStatus={setToggleTarget}
              onDelete={setDeleteTarget}
              sortKey={sortKey}
              sortDirection={sortDirection}
              onSortChange={handleSortChange}
              sequenceStart={(page - 1) * pageSize + 1}
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

      <AlertDialog
        open={!!toggleTarget}
        onOpenChange={(open) => !open && setToggleTarget(null)}
      >
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>
              {toggleTarget?.status === "1"
                ? "ยืนยันการปิดใช้งานกฎการวินิจฉัย"
                : "ยืนยันการเปิดใช้งานกฎการวินิจฉัย"}
            </AlertDialogTitle>
            <AlertDialogDescription>
              {toggleTarget?.status === "1"
                ? `กฎ "${toggleTarget ? getRuleDisplayName(toggleTarget) : ""}" จะไม่ถูกใช้ในการประเมินอาการ`
                : `กฎ "${toggleTarget ? getRuleDisplayName(toggleTarget) : ""}" จะกลับมาใช้งานได้ตามปกติ`}
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

      <AlertDialog
        open={!!deleteTarget}
        onOpenChange={(open) => !open && setDeleteTarget(null)}
      >
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>ยืนยันการลบกฎการวินิจฉัย</AlertDialogTitle>
            <AlertDialogDescription>
              กฎ "{deleteTarget ? getRuleDisplayName(deleteTarget) : ""}" และเงื่อนไขทั้งหมดของกฎนี้จะถูกลบอย่างถาวร
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel disabled={deleting}>ยกเลิก</AlertDialogCancel>
            <AlertDialogAction onClick={handleDelete} loading={deleting}>
              ลบกฎการวินิจฉัย
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </div>
  );
}
