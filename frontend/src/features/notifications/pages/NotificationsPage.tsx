import { useCallback, useEffect, useRef, useState } from "react";
import { Eye, Plus } from "lucide-react";
import { toast } from "sonner";
import { Button } from "@/components/ui/Button";
import { Card } from "@/components/ui/Card";
import { Checkbox } from "@/components/ui/Checkbox";
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
import { MultiSelectFilter } from "@/components/ui/MultiSelectFilter";
import { TableSkeleton } from "@/components/ui/TableSkeleton";
import { DataLoadError } from "@/components/ui/DataLoadError";
import { RichTextEditor } from "@/components/ui/RichTextEditor";
import { api, queryGet } from "@/lib/api";
import { withRowNumbers } from "@/lib/tableRows";
import { userApi } from "@/lib/api/user";
import type { User } from "@/types/user";
import {
  NOTIFICATION_TYPES,
  type AdminNotification,
  type NotificationFilters as NotificationFilterValue,
} from "@/types/notification";
import { NotificationTable } from "../components/NotificationTable";
import { NotificationDetailDialog } from "../components/NotificationDetailDialog";
import { NotificationPreview } from "../components/NotificationPreview";
import { NotificationFilters } from "../components/NotificationFilters";
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
type Page<T> = { data: T[]; meta: { last_page: number; total: number } };
const PER_PAGE = 15;
const initial = {
  title: "",
  body: "",
  type: "S",
  audience: "all",
  user_ids: [] as string[],
  group_role: "User",
  group_status: "1",
  target_url: "",
  send_mode: "now",
  scheduled_at: "",
  is_persistent: false,
  starts_at: "",
};
const toLocalDateTimeInput = (value: string | null) => {
  if (!value) return "";
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return "";
  const pad = (part: number) => String(part).padStart(2, "0");
  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
};
export function NotificationsPage() {
  const [items, setItems] = useState<AdminNotification[]>([]),
    [users, setUsers] = useState<User[]>([]),
    [page, setPage] = useState(1),
    [lastPage, setLastPage] = useState(1),
    [total, setTotal] = useState(0);
  const [loading, setLoading] = useState(true),
    [open, setOpen] = useState(false),
    [preview, setPreview] = useState(false),
    [saving, setSaving] = useState(false),
    [busyId, setBusyId] = useState<number | null>(null);
  const [loadError, setLoadError] = useState<string | null>(null);
  const [form, setForm] = useState(initial);
  const [detail, setDetail] = useState<AdminNotification | null>(null);
  const [editId, setEditId] = useState<number | null>(null);
  const [deleteTargetId, setDeleteTargetId] = useState<number | null>(null);
  const detailRequestRef = useRef(0);
  const [filters, setFilters] = useState<NotificationFilterValue>({
    search: "",
    types: [],
    status: "all",
    sortDirection: "desc",
  });
  const load = useCallback(async () => {
    setLoading(true);
    setLoadError(null);
    try {
      const selectedTypes = filters.types ?? [];
      const params = {
        page,
        per_page: PER_PAGE,
        search: filters.search || undefined,
        type: selectedTypes.length > 0 ? selectedTypes : undefined,
        status: filters.status === "all" ? undefined : filters.status,
        sort_direction: filters.sortDirection,
      };
      const r = await queryGet<Page<AdminNotification>>(
        ["notification-campaigns", params],
        "/admin/notifications",
        { params },
        0,
      );
      setItems(r.data);
      setLastPage(r.meta.last_page);
      setTotal(r.meta.total);
    } catch {
      setLoadError("ไม่สามารถโหลดข้อมูลการแจ้งเตือนได้");
    } finally {
      setLoading(false);
    }
  }, [filters, page]);
  useEffect(() => void load(), [load]);
  useEffect(() => {
    userApi
      .list({ per_page: 100 })
      .then((r) => setUsers(r.data))
      .catch(() => undefined);
  }, []);
  const handleFiltersChange = useCallback((value: NotificationFilterValue) => {
    setFilters(value);
    setPage(1);
  }, []);
  const set = <K extends keyof typeof initial>(
    key: K,
    value: (typeof initial)[K],
  ) => setForm((v) => ({ ...v, [key]: value }));
  const submit = async () => {
    if (
      !form.title.trim() ||
      !form.body.trim() ||
      (form.audience === "individual" && form.user_ids.length === 0)
    ) {
      toast.error("กรุณากรอกข้อมูลให้ครบ");
      return;
    }
    setSaving(true);
    try {
      const payload = {
        title: form.title,
        body: form.body,
        type: form.type,
        audience: form.audience,
        user_ids:
          form.audience === "individual" ? form.user_ids : undefined,
        audience_filter:
          form.audience === "group"
            ? { role: form.group_role, status: form.group_status }
            : undefined,
        channels: ["in_app"],
        target_url: form.target_url || undefined,
        scheduled_at:
          form.send_mode === "scheduled" && form.scheduled_at
            ? new Date(form.scheduled_at).toISOString()
            : undefined,
        is_persistent: form.is_persistent,
        starts_at:
          form.is_persistent && form.starts_at
            ? new Date(form.starts_at).toISOString()
            : undefined,
      };
      if (editId) {
        await api.put(`/admin/notifications/${editId}`, payload);
      } else {
        await api.post("/admin/notifications", payload);
      }
      toast.success(
        editId
          ? "แก้ไขการแจ้งเตือนแล้ว"
          : form.send_mode === "scheduled"
            ? "ตั้งเวลาส่งแล้ว"
            : "สร้างและส่งการแจ้งเตือนแล้ว",
      );
      setOpen(false);
      setEditId(null);
      setForm(initial);
      await load();
    } catch {
      toast.error("บันทึกการแจ้งเตือนไม่สำเร็จ");
    } finally {
      setSaving(false);
    }
  };
  const action = async (id: number, kind: "cancel" | "retry") => {
    setBusyId(id);
    try {
      await api.post(`/admin/notifications/${id}/${kind}`);
      toast.success(kind === "retry" ? "ส่งซ้ำแล้ว" : "ยกเลิกรายการแล้ว");
      await load();
    } catch {
      toast.error("ดำเนินการไม่สำเร็จ");
    } finally {
      setBusyId(null);
    }
  };
  const viewDetail = async (item: AdminNotification) => {
    const requestId = ++detailRequestRef.current;
    setDetail(item);
    try {
      const response = await api.get<{ data: AdminNotification }>(
        `/admin/notifications/${item.id}`,
      );
      if (requestId !== detailRequestRef.current) return;
      setDetail(response.data.data);
      setItems((current) =>
        current.map((value) =>
          value.id === item.id ? response.data.data : value,
        ),
      );
    } catch {
      if (requestId === detailRequestRef.current)
        toast.error("ไม่สามารถอัปเดตสถิติการแจ้งเตือนได้");
    }
  };
  const closeDetail = () => {
    detailRequestRef.current += 1;
    setDetail(null);
  };
  const editNotification = (item: AdminNotification) => {
    const userIds = item.audience_filter?.user_ids;
    setEditId(item.id);
    setForm({
      title: item.title,
      body: item.body,
      type: item.type,
      audience: item.audience,
      user_ids: Array.isArray(userIds) ? userIds.map(String) : [],
      group_role:
        typeof item.audience_filter?.role === "string"
          ? item.audience_filter.role
          : "User",
      group_status:
        typeof item.audience_filter?.status === "string"
          ? item.audience_filter.status
          : "1",
      target_url: item.target_url ?? "",
      send_mode: item.scheduled_at ? "scheduled" : "now",
      scheduled_at: toLocalDateTimeInput(item.scheduled_at),
      is_persistent: item.is_persistent,
      starts_at: toLocalDateTimeInput(item.starts_at),
    });
    setOpen(true);
  };
  const deleteNotification = async () => {
    if (deleteTargetId === null) return;
    setBusyId(deleteTargetId);
    try {
      await api.delete(`/admin/notifications/${deleteTargetId}`);
      toast.success("ลบการแจ้งเตือนแล้ว");
      setDeleteTargetId(null);
      await load();
    } catch {
      toast.error("ลบการแจ้งเตือนไม่สำเร็จ");
    } finally {
      setBusyId(null);
    }
  };
  return (
    <div>
      <div className="mb-5 flex items-center justify-between">
        <div>
          <h1 className="text-xl font-semibold">จัดการการแจ้งเตือน</h1>
          <p className="mt-1 text-sm text-[var(--color-text-secondary)]">
            สร้าง ตั้งเวลา และติดตามผลการส่ง
          </p>
        </div>
        <Button
          onClick={() => {
            closeDetail();
            setEditId(null);
            setForm(initial);
            setOpen(true);
          }}
        >
          <Plus className="h-4 w-4" />
          สร้างการแจ้งเตือน
        </Button>
      </div>
      <div className="mb-4">
        <NotificationFilters value={filters} onChange={handleFiltersChange} />
      </div>
      <Card className="p-0">
        {loading && items.length === 0 ? (
          <TableSkeleton columns={8} />
        ) : loadError && items.length === 0 ? (
          <DataLoadError description={loadError} onRetry={() => void load()} />
        ) : (
          <div className={loading ? "opacity-50 transition-opacity" : ""}>
            <NotificationTable
              data={withRowNumbers(items, (page - 1) * PER_PAGE + 1)}
              busyId={busyId}
              onView={(item) => void viewDetail(item)}
              onEdit={editNotification}
              onDelete={setDeleteTargetId}
              onCancel={(id) => void action(id, "cancel")}
              onRetry={(id) => void action(id, "retry")}
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
            itemLabel="รายการ"
            onChange={setPage}
          />
        </div>
      )}
      <NotificationDetailDialog item={detail} onClose={closeDetail} />
      <AlertDialog
        open={deleteTargetId !== null}
        onOpenChange={(open) => {
          if (!open && busyId === null) setDeleteTargetId(null);
        }}
      >
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>ยืนยันการลบการแจ้งเตือน</AlertDialogTitle>
            <AlertDialogDescription>
              ต้องการลบ “
              {items.find((item) => item.id === deleteTargetId)?.title}”
              หรือไม่? เมื่อลบแล้วจะไม่สามารถเรียกคืนได้
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel disabled={busyId !== null}>
              ยกเลิก
            </AlertDialogCancel>
            <AlertDialogAction
              loading={busyId !== null}
              onClick={() => void deleteNotification()}
            >
              ลบการแจ้งเตือน
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
      <Dialog open={open} onOpenChange={setOpen}>
        <DialogContent
          maxWidth="2xl"
          className="flex flex-col md:h-[90vh] md:max-h-[900px] md:overflow-hidden"
        >
          <DialogHeader>
            <DialogTitle>
              {editId ? "แก้ไขการแจ้งเตือน" : "สร้างการแจ้งเตือน"}
            </DialogTitle>
          </DialogHeader>
          <div className="min-h-0 flex-1 space-y-4 overflow-y-auto pr-1">
            <section>
              <div className="mb-3">
                <p className="text-sm font-semibold">ผู้รับ</p>
                <p className="mt-1 text-xs text-[var(--color-text-secondary)]">
                  กำหนดผู้ใช้ที่จะได้รับข้อความในกล่องแจ้งเตือน
                </p>
              </div>
              <SimpleSelect
                label="รูปแบบผู้รับ"
                value={form.audience}
                onChange={(v) => set("audience", v)}
                options={[
                  { value: "all", label: "ผู้ใช้ทุกคน" },
                  { value: "group", label: "เลือกตามบทบาทและสถานะ" },
                  { value: "individual", label: "เลือกผู้ใช้รายบุคคล" },
                ]}
              />
              {form.audience === "individual" && (
                <div className="mt-4">
                  <MultiSelectFilter
                    label="ผู้ใช้งาน"
                    values={form.user_ids}
                    onChange={(values) => set("user_ids", values)}
                    emptyLabel="เลือกผู้ใช้งาน..."
                    searchable
                    searchPlaceholder="ค้นหาชื่อหรืออีเมล..."
                    options={users.map((u) => ({
                      value: u.user_id,
                      label: `${u.first_name} ${u.last_name} (${u.email})`,
                    }))}
                  />
                </div>
              )}
              {form.audience === "group" && (
                <div className="mt-4 grid gap-4 md:grid-cols-2">
                  <SimpleSelect
                    label="บทบาทผู้ใช้"
                    value={form.group_role}
                    onChange={(v) => set("group_role", v)}
                    options={[
                      { value: "User", label: "ผู้ใช้งาน" },
                      { value: "Admin", label: "ผู้ดูแลระบบ" },
                    ]}
                  />
                  <SimpleSelect
                    label="สถานะบัญชี"
                    value={form.group_status}
                    onChange={(v) => set("group_status", v)}
                    options={[
                      { value: "1", label: "ใช้งาน" },
                      { value: "2", label: "ปิดใช้งาน" },
                    ]}
                  />
                </div>
              )}
              {form.audience === "all" && (
                <p className="mt-3 text-sm text-[var(--color-text-secondary)]">
                  ข้อความนี้จะส่งถึงผู้ใช้ทุกบัญชี
                </p>
              )}
            </section>
            <Input
              label="หัวข้อ *"
              value={form.title}
              onChange={(e) => set("title", e.target.value)}
            />
            <div>
              <p className="mb-1.5 text-sm font-medium">เนื้อหา *</p>
              <RichTextEditor
                value={form.body}
                onChange={(v) => set("body", v)}
                folder="notifications"
                placeholder="พิมพ์รายละเอียด..."
              />
            </div>
            <SimpleSelect
              label="ประเภท"
              value={form.type}
              onChange={(v) => set("type", v)}
              options={NOTIFICATION_TYPES}
            />
            <Input
              label="ลิงก์ปลายทาง"
              value={form.target_url}
              onChange={(e) => set("target_url", e.target.value)}
              placeholder="เช่น /health-episodes/123 หรือ https://..."
            />
            <section className="space-y-4">
              <SimpleSelect
                label="เวลาส่ง"
                value={form.send_mode}
                onChange={(v) => set("send_mode", v)}
                options={[
                  { value: "now", label: "ส่งทันที" },
                  { value: "scheduled", label: "ตั้งเวลาส่ง" },
                ]}
              />
              {form.send_mode === "scheduled" && (
                <Input
                  label="วันและเวลาส่ง"
                  type="datetime-local"
                  value={form.scheduled_at}
                  onChange={(e) => set("scheduled_at", e.target.value)}
                />
              )}
            </section>
            <section className="rounded-xl border border-[var(--color-border)] p-4">
              <label className="flex items-center gap-2 font-medium">
                <Checkbox
                  checked={form.is_persistent}
                  onCheckedChange={(v) => set("is_persistent", v === true)}
                />
                ส่งให้ผู้สมัครใหม่ด้วย (ข้อความคงอยู่)
              </label>
              <p className="mt-1 text-sm text-[var(--color-text-secondary)]">
                ผู้สมัครใหม่จะได้รับข้อความนี้ในกล่องแจ้งเตือนด้วย
              </p>
              {form.is_persistent && (
                <div className="mt-3">
                  <Input
                    label="เริ่มแสดง (ไม่บังคับ)"
                    type="datetime-local"
                    value={form.starts_at}
                    onChange={(e) => set("starts_at", e.target.value)}
                  />
                </div>
              )}
            </section>
          </div>
          <DialogFooter className="shrink-0 border-t border-[var(--color-border)] pt-4">
            <Button variant="outline" onClick={() => setOpen(false)}>
              ยกเลิก
            </Button>
            <Button variant="outline" onClick={() => setPreview(true)}>
              <Eye className="h-4 w-4" />
              ดูตัวอย่าง
            </Button>
            <Button loading={saving} onClick={() => void submit()}>
              {editId
                ? "บันทึกการแก้ไข"
                : form.send_mode === "scheduled"
                  ? "ตั้งเวลาส่ง"
                  : "ส่ง"}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
      <Dialog open={preview} onOpenChange={setPreview}>
        <DialogContent maxWidth="lg" className="max-h-[90vh] min-w-0 overflow-y-auto">
          <DialogHeader>
            <DialogTitle>ตัวอย่างการแจ้งเตือน</DialogTitle>
          </DialogHeader>
          <NotificationPreview
            title={form.title}
            body={form.body}
            targetUrl={form.target_url}
          />
          <DialogFooter>
            <Button onClick={() => setPreview(false)}>ปิดตัวอย่าง</Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}
