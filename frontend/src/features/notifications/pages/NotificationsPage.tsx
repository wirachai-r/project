import { useCallback, useEffect, useState } from "react";
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
import {
  Dialog,
  DialogContent,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/Dialog";
import { Input } from "@/components/ui/Input";
import { Pagination } from "@/components/ui/Pagination";
import { SimpleSelect } from "@/components/ui/SimpleSelect";
import { TableSkeleton } from "@/components/ui/TableSkeleton";
import { withRowNumbers } from "@/lib/tableRows";
import { RichTextEditor } from "@/components/ui/RichTextEditor";
import { api } from "@/lib/api";
import { userApi } from "@/lib/api/user";
import type { User } from "@/types/user";
import { NotificationFilters } from "../components/NotificationFilters";
import { NotificationTable } from "../components/NotificationTable";
import {
  NOTIFICATION_TYPES,
  type AdminNotification,
  type NotificationFilters as FilterValue,
} from "@/types/notification";

type Page<T> = {
  data: T[];
  meta: { last_page: number; total: number };
};

const ALL_USERS = "__all__";
const PER_PAGE = 15;

export function NotificationsPage() {
  const [items, setItems] = useState<AdminNotification[]>([]);
  const [users, setUsers] = useState<User[]>([]);
  const [filters, setFilters] = useState<FilterValue>({
    search: "",
    type: "all",
    read: "all",
    sortDirection: "desc",
  });
  const [page, setPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [total, setTotal] = useState(0);
  const [loading, setLoading] = useState(true);
  const [initialLoading, setInitialLoading] = useState(true);
  const [busyId, setBusyId] = useState<number | null>(null);
  const [deleteTarget, setDeleteTarget] = useState<number | null>(null);
  const [open, setOpen] = useState(false);
  const [saving, setSaving] = useState(false);
  const [form, setForm] = useState({
    title: "",
    body: "",
    type: "S",
    user_id: "",
  });

  const load = useCallback(async () => {
    setLoading(true);
    try {
      const response = await api.get<Page<AdminNotification>>(
        "/admin/notifications",
        {
          params: {
            page,
            per_page: PER_PAGE,
            search: filters.search || undefined,
            type: filters.type === "all" ? undefined : filters.type,
            is_read: filters.read === "all" ? undefined : filters.read,
            sort_by: "created_at",
            sort_direction: filters.sortDirection,
          },
        },
      );
      setItems(response.data.data);
      setLastPage(response.data.meta.last_page);
      setTotal(response.data.meta.total);
    } catch {
      toast.error("ไม่สามารถโหลดการแจ้งเตือนได้");
    } finally {
      setLoading(false);
      setInitialLoading(false);
    }
  }, [filters, page]);

  useEffect(() => void load(), [load]);
  useEffect(() => {
    userApi
      .list({ per_page: 50 })
      .then((response) => setUsers(response.data))
      .catch(() => toast.error("ไม่สามารถโหลดรายชื่อผู้ใช้ได้"));
  }, []);

  const changeFilters = useCallback((value: FilterValue) => {
    setFilters(value);
    setPage(1);
  }, []);

  const submit = async () => {
    if (!form.title.trim() || !form.body.trim() || !form.user_id) {
      toast.error("กรุณากรอกข้อมูลให้ครบ");
      return;
    }
    setSaving(true);
    try {
      await api.post("/admin/notifications", {
        ...form,
        audience: form.user_id === ALL_USERS ? "all" : "individual",
        user_id: form.user_id === ALL_USERS ? undefined : form.user_id,
      });
      toast.success("ส่งการแจ้งเตือนแล้ว");
      setOpen(false);
      setForm({ title: "", body: "", type: "S", user_id: "" });
      await load();
    } catch {
      toast.error("ส่งการแจ้งเตือนไม่สำเร็จ");
    } finally {
      setSaving(false);
    }
  };

  const markRead = async (id: number) => {
    setBusyId(id);
    try {
      await api.patch(`/admin/notifications/${id}/mark-as-read`);
      await load();
    } catch {
      toast.error("อัปเดตสถานะไม่สำเร็จ");
    } finally {
      setBusyId(null);
    }
  };

  const remove = async () => {
    if (deleteTarget === null) return;
    setBusyId(deleteTarget);
    try {
      await api.delete(`/admin/notifications/${deleteTarget}`);
      toast.success("ลบการแจ้งเตือนแล้ว");
      setDeleteTarget(null);
      await load();
    } catch {
      toast.error("ลบการแจ้งเตือนไม่สำเร็จ");
    } finally {
      setBusyId(null);
    }
  };

  return (
    <div>
      <div className="mb-5 flex items-center justify-between gap-3">
        <div>
          <h1 className="text-xl font-semibold text-[var(--color-text-primary)]">
            จัดการการแจ้งเตือน
          </h1>
          {/* <p className="mt-1 text-sm text-[var(--color-text-secondary)]">
            ส่งและติดตามการแจ้งเตือนของผู้ใช้งาน
          </p> */}
        </div>
        <Button onClick={() => setOpen(true)}>
          <Plus className="h-4 w-4" /> ส่งการแจ้งเตือน
        </Button>
      </div>

      <div className="mb-4">
        <NotificationFilters value={filters} onChange={changeFilters} />
      </div>
      <Card className="p-0">
        {initialLoading ? (
          <TableSkeleton columns={6} rows={8} />
        ) : (
          <div className={loading ? "opacity-50" : ""}>
            <NotificationTable
              data={withRowNumbers(items, (page - 1) * PER_PAGE + 1)}
              busyId={busyId}
              onRead={(id) => void markRead(id)}
              onDelete={setDeleteTarget}
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
            itemLabel="การแจ้งเตือน"
            onChange={setPage}
          />
        </div>
      )}

      <Dialog open={open} onOpenChange={setOpen}>
        <DialogContent
          maxWidth="2xl"
          className="flex flex-col md:h-[90vh] md:max-h-[900px] md:overflow-hidden"
        >
          <DialogHeader className="shrink-0">
            <DialogTitle>ส่งการแจ้งเตือน</DialogTitle>
          </DialogHeader>
          <div className="min-h-0 flex-1 space-y-4 overflow-y-auto pr-1 sm:pr-2">
            <SimpleSelect
              label="ผู้รับ"
              value={form.user_id}
              onChange={(user_id) => setForm((value) => ({ ...value, user_id }))}
              options={[
                { value: ALL_USERS, label: "ผู้ใช้ทั้งหมด" },
                ...users.map((user) => ({
                  value: user.user_id,
                  label: `${user.first_name} ${user.last_name} (${user.email})`,
                })),
              ]}
            />
            <Input label="หัวข้อ" value={form.title} onChange={(event) => setForm((value) => ({ ...value, title: event.target.value }))} />
            <div className="space-y-1.5">
              <p className="text-sm font-medium text-[var(--color-text-primary)]">
                เนื้อหา
              </p>
              <div className="[&_.ProseMirror]:max-h-60 [&_.ProseMirror]:min-h-32 [&_.ProseMirror]:overflow-y-auto [&>div>div:first-child]:static">
                <RichTextEditor
                  value={form.body}
                  onChange={(body) => setForm((value) => ({ ...value, body }))}
                  placeholder="พิมพ์รายละเอียดการแจ้งเตือน..."
                  folder="notifications"
                />
              </div>
            </div>
            <SimpleSelect label="ประเภท" value={form.type} onChange={(type) => setForm((value) => ({ ...value, type }))} options={NOTIFICATION_TYPES} />
          </div>
          <DialogFooter className="shrink-0 border-t border-[var(--color-border)] pt-4">
            <Button variant="outline" onClick={() => setOpen(false)}>ยกเลิก</Button>
            <Button loading={saving} onClick={() => void submit()}>ส่งการแจ้งเตือน</Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      <AlertDialog
        open={deleteTarget !== null}
        onOpenChange={(isOpen) => !isOpen && setDeleteTarget(null)}
      >
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>ยืนยันการลบการแจ้งเตือน</AlertDialogTitle>
            <AlertDialogDescription>
              การแจ้งเตือนนี้จะถูกลบอย่างถาวร
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel disabled={busyId === deleteTarget}>ยกเลิก</AlertDialogCancel>
            <AlertDialogAction loading={busyId === deleteTarget} onClick={() => void remove()}>
              ลบการแจ้งเตือน
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </div>
  );
}
