import { useCallback, useEffect, useMemo, useState } from "react";
import { Plus } from "lucide-react";
import { toast } from "sonner";
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
import { Button } from "@/components/ui/Button";
import { Card } from "@/components/ui/Card";
import { TableSkeleton } from "@/components/ui/TableSkeleton";
import { bodyAreaGroupApi } from "@/lib/api/bodyAreaGroup";
import { symptomApi } from "@/lib/api/symptom";
import { getErrorMessage } from "@/lib/getErrorMessage";
import type { BodyAreaGroup, BodyAreaGroupForm, BodyAreaSubgroup } from "@/types/bodyAreaGroup";
import type { Symptom } from "@/types/symptom";
import { fuzzyIncludes } from "@/lib/fuzzySearch";
import {
  BodyAreaGroupFilters,
  type BodyAreaGroupFilterValue,
} from "../components/BodyAreaGroupFilters";
import { BodyAreaGroupFormDialog } from "../components/BodyAreaGroupFormDialog";
import { BodyAreaGroupTable } from "../components/BodyAreaGroupTable";
import { BodyAreaSubgroupsDialog } from "../components/BodyAreaSubgroupsDialog";

const emptyForm = (displayOrder = 0): BodyAreaGroupForm => ({
  name: "",
  name_en: "",
  description: "",
  display_order: displayOrder,
  status: "1",
  symptom_ids: [],
  image: null,
  subgroups: [],
});

export function BodyAreaGroupsPage() {
  const [groups, setGroups] = useState<BodyAreaGroup[]>([]);
  const [symptoms, setSymptoms] = useState<Symptom[]>([]);
  const [loading, setLoading] = useState(true);
  const [filters, setFilters] = useState<BodyAreaGroupFilterValue>({
    search: "",
    status: "all",
  });
  const [open, setOpen] = useState(false);
  const [editing, setEditing] = useState<BodyAreaGroup | null>(null);
  const [form, setForm] = useState<BodyAreaGroupForm>(emptyForm());
  const [saving, setSaving] = useState(false);
  const [saveError, setSaveError] = useState<string | null>(null);
  const [deleteTarget, setDeleteTarget] = useState<BodyAreaGroup | null>(null);
  const [deleting, setDeleting] = useState(false);
  const [draggingId, setDraggingId] = useState<number | null>(null);
  const [reordering, setReordering] = useState(false);
  const [statusBusyId, setStatusBusyId] = useState<number | null>(null);
  const [subgroupTarget, setSubgroupTarget] = useState<BodyAreaGroup | null>(null);
  const [subgroupDraft, setSubgroupDraft] = useState<BodyAreaSubgroup[]>([]);
  const [subgroupsSaving, setSubgroupsSaving] = useState(false);

  const load = useCallback(async () => {
    setLoading(true);
    try {
      const [groupData, symptomData] = await Promise.all([
        bodyAreaGroupApi.list(),
        symptomApi.list({ per_page: 500 }),
      ]);
      setGroups(groupData);
      setSymptoms(symptomData.data);
    } catch (error) {
      toast.error(getErrorMessage(error));
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => void load(), [load]);

  const filteredGroups = useMemo(() => {
    const term = filters.search.trim();
    return groups.filter((group) => {
      const matchesSearch =
        !term ||
        fuzzyIncludes(`${group.name} ${group.name_en ?? ""} ${group.description ?? ""}`, term);
      return (
        matchesSearch &&
        (filters.status === "all" || group.status === filters.status)
      );
    });
  }, [filters, groups]);

  const canReorder =
    !reordering && !filters.search.trim() && filters.status === "all";

  const openCreate = () => {
    setSaveError(null);
    setEditing(null);
    setForm(emptyForm(groups.length + 1));
    setOpen(true);
  };

  const openEdit = (group: BodyAreaGroup) => {
    setSaveError(null);
    setEditing(group);
    setForm({
      name: group.name,
      name_en: group.name_en ?? "",
      description: group.description ?? "",
      display_order: group.display_order,
      status: group.status,
      symptom_ids: group.symptom_ids ?? [],
      image: null,
      subgroups: group.subgroups ?? [],
    });
    setOpen(true);
  };

  const save = async () => {
    if (!form.name.trim()) return toast.error("กรุณากรอกชื่อกลุ่มบริเวณ");
    if (!editing && !form.image) return toast.error("กรุณาเพิ่มรูป PNG");
    if (form.subgroups.some((subgroup) => !subgroup.name.trim())) {
      return toast.error("กรุณากรอกชื่อบริเวณย่อยให้ครบ");
    }
    setSaving(true);
    setSaveError(null);
    try {
      if (editing) await bodyAreaGroupApi.update(editing.id, form);
      else await bodyAreaGroupApi.create(form);
      toast.success("บันทึกกลุ่มบริเวณสำเร็จ");
      setOpen(false);
      await load();
    } catch (error) {
      const message = getErrorMessage(error);
      setSaveError(message);
      toast.error(message);
    } finally {
      setSaving(false);
    }
  };

  const remove = async () => {
    if (!deleteTarget) return;
    setDeleting(true);
    try {
      await bodyAreaGroupApi.delete(deleteTarget.id);
      toast.success("ลบกลุ่มบริเวณสำเร็จ");
      setDeleteTarget(null);
      await load();
    } catch (error) {
      toast.error(getErrorMessage(error));
    } finally {
      setDeleting(false);
    }
  };

  const toggleStatus = async (group: BodyAreaGroup) => {
    const nextStatus = group.status === "1" ? "2" : "1";
    setStatusBusyId(group.id);
    try {
      await bodyAreaGroupApi.update(group.id, {
        name: group.name,
        name_en: group.name_en ?? "",
        description: group.description ?? "",
        display_order: group.display_order,
        status: nextStatus,
        symptom_ids: group.symptom_ids ?? [],
        image: null,
        subgroups: group.subgroups ?? [],
      });
      setGroups((current) =>
        current.map((item) =>
          item.id === group.id ? { ...item, status: nextStatus } : item,
        ),
      );
      toast.success(
        nextStatus === "1"
          ? "เปิดใช้งานกลุ่มบริเวณแล้ว"
          : "ปิดใช้งานกลุ่มบริเวณแล้ว",
      );
    } catch (error) {
      toast.error(getErrorMessage(error));
    } finally {
      setStatusBusyId(null);
    }
  };

  const openSubgroups = (group: BodyAreaGroup) => {
    setSubgroupTarget(group);
    setSubgroupDraft(group.subgroups ?? []);
  };

  const saveSubgroups = async () => {
    if (!subgroupTarget) return;
    if (subgroupDraft.some((subgroup) => !subgroup.name.trim())) {
      return toast.error("กรุณากรอกชื่อบริเวณย่อยให้ครบ");
    }
    setSubgroupsSaving(true);
    try {
      await bodyAreaGroupApi.update(subgroupTarget.id, {
        name: subgroupTarget.name,
        name_en: subgroupTarget.name_en ?? "",
        description: subgroupTarget.description ?? "",
        display_order: subgroupTarget.display_order,
        status: subgroupTarget.status,
        symptom_ids: subgroupTarget.symptom_ids ?? [],
        image: null,
        subgroups: subgroupDraft,
      });
      toast.success("บันทึกบริเวณย่อยสำเร็จ");
      setSubgroupTarget(null);
      await load();
    } catch (error) {
      toast.error(getErrorMessage(error));
    } finally {
      setSubgroupsSaving(false);
    }
  };

  const dropAt = async (
    targetId: number,
    position: "before" | "after",
  ) => {
    if (!canReorder || draggingId === null || draggingId === targetId)
      return setDraggingId(null);
    const previous = groups;
    const fromIndex = groups.findIndex((group) => group.id === draggingId);
    const targetIndex = groups.findIndex((group) => group.id === targetId);
    if (fromIndex < 0 || targetIndex < 0) return setDraggingId(null);

    const reordered = [...groups];
    const [moved] = reordered.splice(fromIndex, 1);
    const adjustedTargetIndex = fromIndex < targetIndex
      ? targetIndex - 1
      : targetIndex;
    const insertionIndex = position === "after"
      ? adjustedTargetIndex + 1
      : adjustedTargetIndex;
    reordered.splice(insertionIndex, 0, moved);
    setGroups(
      reordered.map((group, index) => ({ ...group, display_order: index + 1 })),
    );
    setDraggingId(null);
    setReordering(true);
    try {
      await bodyAreaGroupApi.reorder(reordered.map((group) => group.id));
      toast.success("บันทึกลำดับใหม่แล้ว");
    } catch (error) {
      setGroups(previous);
      toast.error(getErrorMessage(error));
    } finally {
      setReordering(false);
    }
  };

  return (
    <div>
      <div className="mb-5 flex items-center justify-between gap-3">
        <div>
          <h1 className="text-xl font-semibold">กลุ่มบริเวณร่างกาย</h1>
          <p className="mt-1 text-sm text-[var(--color-text-secondary)]">
            จัดการรูปภาพและอาการที่ให้ผู้ใช้เลือกก่อนเริ่มประเมิน
          </p>
        </div>
        <Button onClick={openCreate}>
          <Plus className="h-4 w-4" />
          เพิ่มกลุ่มบริเวณ
        </Button>
      </div>

      <BodyAreaGroupFilters value={filters} onChange={setFilters} />
      <div className="mb-3 mt-4 flex items-center justify-between text-sm text-[var(--color-text-secondary)]">
        <span>ทั้งหมด {filteredGroups.length} กลุ่ม</span>
        <span>
          {canReorder
            ? "ลากแถวเพื่อเปลี่ยนลำดับการแสดง"
            : reordering
              ? "กำลังบันทึกลำดับ..."
              : "ล้างตัวกรองเพื่อจัดลำดับ"}
        </span>
      </div>
      <Card className="p-0">
        {loading ? (
          <TableSkeleton
            columns={6}
            rows={5}
            columnWidths={["w-14", "w-80", "w-32", "w-56", "w-28", "w-24"]}
          />
        ) : (
          <BodyAreaGroupTable
            data={filteredGroups}
            canReorder={canReorder}
            draggingId={draggingId}
            onDragStart={setDraggingId}
            onDragEnd={() => setDraggingId(null)}
            onDrop={(id, position) => void dropAt(id, position)}
            onEdit={openEdit}
            onManageSubgroups={openSubgroups}
            onDelete={setDeleteTarget}
            onStatusChange={(group) => void toggleStatus(group)}
            statusBusyId={statusBusyId}
          />
        )}
      </Card>

      {open && (
        <BodyAreaGroupFormDialog
          open
          editing={editing}
          form={form}
          symptoms={symptoms}
          saving={saving}
          saveError={saveError}
          onOpenChange={setOpen}
          onFormChange={setForm}
          onSave={() => void save()}
        />
      )}

      {subgroupTarget && (
        <BodyAreaSubgroupsDialog
          open
          items={subgroupDraft}
          symptoms={symptoms}
          availableSymptomIds={subgroupTarget.symptom_ids ?? []}
          onChange={setSubgroupDraft}
          onClose={() => setSubgroupTarget(null)}
          onSave={() => void saveSubgroups()}
          saving={subgroupsSaving}
        />
      )}

      <AlertDialog
        open={deleteTarget !== null}
        onOpenChange={(nextOpen) =>
          !nextOpen && !deleting && setDeleteTarget(null)
        }
      >
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>ยืนยันการลบกลุ่มบริเวณ</AlertDialogTitle>
            <AlertDialogDescription>
              ต้องการลบ “{deleteTarget?.name}” หรือไม่?
              การเชื่อมโยงอาการของกลุ่มนี้จะถูกลบด้วย
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel disabled={deleting}>ยกเลิก</AlertDialogCancel>
            <AlertDialogAction loading={deleting} onClick={() => void remove()}>
              ลบ
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </div>
  );
}
