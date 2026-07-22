import { useEffect, useState, useCallback, useRef } from "react";
import axios from "axios";
import { toast } from "sonner";
import { Plus } from "lucide-react";
import * as Icons from "lucide-react";
import { firstAidCategoryApi } from "@/lib/api/firstAidCategory";
import type {
  FirstAidCategory,
  FirstAidCategoryFormValues,
} from "@/types/firstAidCategory";
import { Card } from "../../../components/ui/Card";
import { Button } from "../../../components/ui/Button";
import { Pagination } from "../../../components/ui/Pagination";
import { Input } from "../../../components/ui/Input";
import { Label } from "../../../components/ui/Label";
import { SimpleSelect } from "../../../components/ui/SimpleSelect";
import { FormSkeleton } from "../../../components/ui/FormSkeleton";
import { IconPicker } from "../../../components/ui/IconPicker";
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogFooter,
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
  FirstAidCategoryFilters,
  type FirstAidCategoryFilterValue,
} from "../components/FirstAidCategoryFilters";
import { FirstAidCategoryTable } from "../components/FirstAidCategoryTable";
import { TableSkeleton } from "../../../components/ui/TableSkeleton";
import { getErrorMessage } from "@/lib/getErrorMessage";

const EMPTY_FORM: FirstAidCategoryFormValues = {
  category_name: "",
  category_name_en: "",
  description: "",
  icon: "",
  status: "1",
};

export function FirstAidCategoriesPage() {
  const [categories, setCategories] = useState<FirstAidCategory[]>([]);
  const [loading, setLoading] = useState(true);
  const [initialLoading, setInitialLoading] = useState(true);
  const [page, setPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [pageSize, setPageSize] = useState(10);
  const [totalItems, setTotalItems] = useState(0);
  const [filters, setFilters] = useState<FirstAidCategoryFilterValue>({
    search: "",
    status: "",
  });

  const [sortKey, setSortKey] = useState<string | null>(null);
  const [sortDirection, setSortDirection] = useState<"asc" | "desc" | null>(
    null,
  );

  const [viewItem, setViewItem] = useState<FirstAidCategory | null>(null);

  const [formOpen, setFormOpen] = useState(false);
  const [formMode, setFormMode] = useState<"create" | "edit">("create");
  const [editingId, setEditingId] = useState<string | null>(null);
  const [form, setForm] = useState<FirstAidCategoryFormValues>(EMPTY_FORM);
  const [saving, setSaving] = useState(false);

  const [toggleTarget, setToggleTarget] = useState<FirstAidCategory | null>(
    null,
  );
  const [toggling, setToggling] = useState(false);

  const [deleteTarget, setDeleteTarget] = useState<FirstAidCategory | null>(
    null,
  );
  const [deleting, setDeleting] = useState(false);

  const abortRef = useRef<AbortController | null>(null);

  const fetchCategories = useCallback(async () => {
    abortRef.current?.abort();
    const controller = new AbortController();
    abortRef.current = controller;

    setLoading(true);
    try {
      const res = await firstAidCategoryApi.list(
        {
          search: filters.search || undefined,
          status: filters.status || undefined,
          page,
          per_page: pageSize,
          sort_by: sortKey ?? undefined,
          sort_direction: sortDirection ?? undefined,
        },
        controller.signal,
      );
      setCategories(res.data);
      setLastPage(res.meta?.last_page ?? 1);
      setTotalItems(res.meta?.total ?? 0);
    } catch (err) {
      if (
        axios.isCancel(err) ||
        (axios.isAxiosError(err) && err.code === "ERR_CANCELED")
      )
        return;
      toast.error("ไม่สามารถโหลดข้อมูลหมวดหมู่ได้");
    } finally {
      if (abortRef.current === controller) {
        setLoading(false);
        setInitialLoading(false);
      }
    }
  }, [filters, page, pageSize, sortKey, sortDirection]);

  useEffect(() => {
    fetchCategories();
    return () => abortRef.current?.abort();
  }, [fetchCategories]);

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
    setFormMode("create");
    setEditingId(null);
    setForm(EMPTY_FORM);
    setFormOpen(true);
  };

  const openEdit = (category: FirstAidCategory) => {
    setFormMode("edit");
    setEditingId(category.first_aid_category_id);
    setForm({
      category_name: category.category_name,
      category_name_en: category.category_name_en ?? "",
      description: category.description ?? "",
      icon: category.icon ?? "",
      status: category.status,
    });
    setFormOpen(true);
  };

  const handleSave = async () => {
    if (!form.category_name.trim()) {
      toast.error("กรุณากรอกชื่อหมวดหมู่");
      return;
    }

    setSaving(true);
    try {
      if (formMode === "create") {
        await firstAidCategoryApi.create(form);
        toast.success("เพิ่มหมวดหมู่สำเร็จ");
      } else if (editingId) {
        await firstAidCategoryApi.update(editingId, form);
        toast.success("บันทึกข้อมูลหมวดหมู่สำเร็จ");
      }
      setFormOpen(false);
      fetchCategories();
    } catch (err) {
      toast.error(getErrorMessage(err));
    } finally {
      setSaving(false);
    }
  };

  const handleToggleStatus = async () => {
    if (!toggleTarget) return;
    setToggling(true);
    try {
      const nextStatus = toggleTarget.status === "1" ? "2" : "1";
      await firstAidCategoryApi.update(toggleTarget.first_aid_category_id, {
        status: nextStatus,
      });
      toast.success(
        nextStatus === "1"
          ? "เปิดใช้งานหมวดหมู่สำเร็จ"
          : "ปิดใช้งานหมวดหมู่สำเร็จ",
      );
      setToggleTarget(null);
      fetchCategories();
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
      await firstAidCategoryApi.delete(deleteTarget.first_aid_category_id);
      toast.success("ลบหมวดหมู่สำเร็จ");
      setDeleteTarget(null);
      fetchCategories();
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
            จัดการหมวดหมู่ปฐมพยาบาล
          </h1>
          <p className="mt-1 text-sm text-[var(--color-text-secondary)]">
            ดูและจัดการหมวดหมู่ปฐมพยาบาลทั้งหมดในระบบ
          </p>
        </div>

        <Button onClick={openCreate}>
          <Plus className="h-4 w-4" />
          เพิ่มหมวดหมู่
        </Button>
      </div>

      <div className="mb-4">
        <FirstAidCategoryFilters value={filters} onChange={setFilters} />
      </div>

      <Card className="p-0">
        {initialLoading ? (
          <TableSkeleton
            columns={5}
            rows={pageSize}
            columnWidths={["w-20", "w-48", "w-24", "w-20", "w-16"]}
          />
        ) : (
          <div className={loading ? "opacity-50 transition-opacity" : ""}>
            <FirstAidCategoryTable
              data={categories}
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

      {/* View dialog */}
      <Dialog
        open={!!viewItem}
        onOpenChange={(open) => !open && setViewItem(null)}
      >
        <DialogContent>
          <DialogHeader>
            <DialogTitle>รายละเอียดหมวดหมู่</DialogTitle>
          </DialogHeader>
          {viewItem && (
            <div className="space-y-4">
              <div className="flex items-center gap-3">
                {(() => {
                  const ViewIcon = viewItem.icon
                    ? ((Icons as Record<string, unknown>)[viewItem.icon] as
                        | typeof Icons.Activity
                        | undefined)
                    : null;
                  return ViewIcon ? (
                    <div className="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg bg-[var(--color-primary-light)]">
                      <ViewIcon className="h-6 w-6 text-[var(--color-primary)]" />
                    </div>
                  ) : null;
                })()}
                <div>
                  <p className="font-medium text-[var(--color-text-primary)]">
                    {viewItem.category_name}
                  </p>
                  {viewItem.category_name_en && (
                    <p className="text-sm text-[var(--color-text-secondary)]">
                      {viewItem.category_name_en}
                    </p>
                  )}
                </div>
              </div>

              <dl className="grid grid-cols-2 gap-3 text-sm">
                <div>
                  <dt className="text-[var(--color-text-secondary)]">
                    จำนวนรายการ
                  </dt>
                  <dd className="text-[var(--color-text-primary)]">
                    {viewItem.first_aids_count ?? 0} รายการ
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
                  <dd className="text-[var(--color-text-primary)]">
                    {viewItem.description || "-"}
                  </dd>
                </div>
              </dl>
            </div>
          )}
        </DialogContent>
      </Dialog>

      {/* Create/Edit dialog */}
      <Dialog open={formOpen} onOpenChange={setFormOpen}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>
              {formMode === "create" ? "เพิ่มหมวดหมู่" : "แก้ไขข้อมูลหมวดหมู่"}
            </DialogTitle>
          </DialogHeader>

          {formMode === "edit" && !editingId ? (
            <FormSkeleton fields={4} />
          ) : (
            <div className="space-y-4">
              <div>
                <Label htmlFor="category_name">ชื่อหมวดหมู่</Label>
                <Input
                  id="category_name"
                  value={form.category_name}
                  onChange={(e) =>
                    setForm({ ...form, category_name: e.target.value })
                  }
                />
              </div>
              <div>
                <Label htmlFor="category_name_en">ชื่อหมวดหมู่ (EN)</Label>
                <Input
                  id="category_name_en"
                  value={form.category_name_en}
                  onChange={(e) =>
                    setForm({ ...form, category_name_en: e.target.value })
                  }
                />
              </div>

              <div>
                <Label htmlFor="description">คำอธิบาย</Label>
                <Input
                  id="description"
                  value={form.description}
                  onChange={(e) =>
                    setForm({ ...form, description: e.target.value })
                  }
                />
              </div>

              <div>
                <Label htmlFor="icon">ไอคอน</Label>
                <IconPicker
                  value={form.icon}
                  onChange={(name) => setForm({ ...form, icon: name })}
                />
              </div>

              <div>
                <Label htmlFor="status">สถานะ</Label>
                <SimpleSelect
                  value={form.status}
                  onChange={(v) => setForm({ ...form, status: v as "1" | "2" })}
                  options={[
                    { label: "ใช้งานได้", value: "1" },
                    { label: "ปิดใช้งาน", value: "2" },
                  ]}
                />
              </div>
            </div>
          )}

          <DialogFooter>
            <Button variant="outline" onClick={() => setFormOpen(false)}>
              ยกเลิก
            </Button>
            <Button onClick={handleSave} loading={saving}>
              บันทึก
            </Button>
          </DialogFooter>
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
                ? "ยืนยันการปิดใช้งานหมวดหมู่"
                : "ยืนยันการเปิดใช้งานหมวดหมู่"}
            </AlertDialogTitle>
            <AlertDialogDescription>
              {toggleTarget?.status === "1"
                ? `หมวดหมู่ "${toggleTarget?.category_name}" จะไม่แสดงในฝั่งผู้ใช้`
                : `หมวดหมู่ "${toggleTarget?.category_name}" จะกลับมาแสดงตามปกติ`}
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
            <AlertDialogTitle>ยืนยันการลบหมวดหมู่</AlertDialogTitle>
            <AlertDialogDescription>
              หมวดหมู่ "{deleteTarget?.category_name}" จะถูกลบอย่างถาวร
              หากมีข้อมูลปฐมพยาบาลในหมวดหมู่นี้อยู่ จะไม่สามารถลบได้
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel disabled={deleting}>ยกเลิก</AlertDialogCancel>
            <AlertDialogAction onClick={handleDelete} loading={deleting}>
              ลบหมวดหมู่
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </div>
  );
}