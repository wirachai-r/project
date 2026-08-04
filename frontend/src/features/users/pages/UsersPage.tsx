import { useEffect, useState, useCallback, useRef } from "react";
import axios from "axios";
import { toast } from "sonner";
import { Users as UsersIcon, UserCheck, UserX, Ban } from "lucide-react";
import { userApi, type UserStats } from "@/lib/api/user";
import type { User } from "@/types/user";
import { Card } from "../../../components/ui/Card";
import { Button } from "../../../components/ui/Button";
import { Pagination } from "../../../components/ui/Pagination";
import { StatCardSkeleton } from "../../../components/ui/StatCardSkeleton";
import { Input } from "../../../components/ui/Input";
import { Label } from "../../../components/ui/Label";
import { SimpleSelect } from "../../../components/ui/SimpleSelect";
import { FormSkeleton } from "../../../components/ui/FormSkeleton";
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
import { UserFilters, type UserFilterValue } from "../components/UserFilters";
import { UserTable } from "../components/UserTable";
import { TableSkeleton } from "../../../components/ui/TableSkeleton";

export default function UsersPage() {
  const [users, setUsers] = useState<User[]>([]);
  const [loading, setLoading] = useState(true);
  const [page, setPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [pageSize, setPageSize] = useState(10);
  const [totalItems, setTotalItems] = useState(0);
  const [filters, setFilters] = useState<UserFilterValue>({
    search: "",
    role: "",
    status: "",
  });
  const [selectedIds, setSelectedIds] = useState<string[]>([]);
  const [sortKey, setSortKey] = useState<string | null>(null);
  const [sortDirection, setSortDirection] = useState<"asc" | "desc" | null>(
    null,
  );

  const [stats, setStats] = useState<UserStats | null>(null);
  const [statsLoading, setStatsLoading] = useState(true);

  const [editUser, setEditUser] = useState<User | null>(null);
  const [editForm, setEditForm] = useState<{
    first_name: string;
    last_name: string;
    email: string;
    role: "User" | "Admin";
  }>({
    first_name: "",
    last_name: "",
    email: "",
    role: "User",
  });
  const [saving, setSaving] = useState(false);

  const [toggleTarget, setToggleTarget] = useState<User | null>(null);
  const [toggling, setToggling] = useState(false);

  const [bulkConfirmOpen, setBulkConfirmOpen] = useState(false);
  const [bulkLoading, setBulkLoading] = useState(false);

  const abortRef = useRef<AbortController | null>(null);

  const [initialLoading, setInitialLoading] = useState(true);

  const fetchUsers = useCallback(async () => {
    abortRef.current?.abort();
    const controller = new AbortController();
    abortRef.current = controller;

    setLoading(true);
    try {
      const res = await userApi.list(
        {
          search: filters.search || undefined,
          role: filters.role || undefined,
          status: filters.status || undefined,
          page,
          per_page: pageSize,
          sort_by:
            (sortKey as "name" | "last_login" | "role" | undefined) ??
            undefined,
          sort_direction: sortDirection ?? undefined,
        },
        controller.signal,
      );
      setUsers(res.data);
      setLastPage(res.meta?.last_page ?? 1);
      setTotalItems(res.meta?.total ?? 0);
    } catch (err) {
      if (
        axios.isCancel(err) ||
        (axios.isAxiosError(err) && err.code === "ERR_CANCELED")
      )
        return;
      toast.error("ไม่สามารถโหลดข้อมูลผู้ใช้ได้");
      throw err;
    } finally {
      if (abortRef.current === controller) {
        setLoading(false);
        setInitialLoading(false); // โหลดครั้งแรกเสร็จแล้ว
      }
    }
  }, [filters, page, pageSize, sortKey, sortDirection]);

  useEffect(() => {
    fetchUsers();
    return () => abortRef.current?.abort();
  }, [fetchUsers]);

  // stats: ยิงครั้งเดียวตอน mount (ไม่ผูกกับ filters/page แล้ว)
  useEffect(() => {
    userApi
      .stats()
      .then(setStats)
      .finally(() => setStatsLoading(false));
  }, []);

  // reset ไปหน้าแรกเมื่อ filter, page size, หรือ sort เปลี่ยน
  useEffect(() => {
    setPage(1);
    setSelectedIds([]);
  }, [filters, pageSize, sortKey, sortDirection]);

  const refreshStats = () => userApi.stats().then(setStats);

  const handleSortChange = (key: string, direction: "asc" | "desc" | null) => {
    setSortKey(direction ? key : null);
    setSortDirection(direction);
  };


  const openEdit = (user: User) => {
    setEditUser(user);
    setEditForm({
      first_name: user.first_name,
      last_name: user.last_name,
      email: user.email,
      role: user.role,
    });
  };

  const handleSaveEdit = async () => {
    if (!editUser) return;
    setSaving(true);
    try {
      await userApi.update(editUser.user_id, editForm);
      toast.success("บันทึกข้อมูลผู้ใช้สำเร็จ");
      setEditUser(null);
      fetchUsers();
    } catch {
      toast.error("บันทึกข้อมูลไม่สำเร็จ กรุณาลองใหม่");
    } finally {
      setSaving(false);
    }
  };

  const handleToggleStatus = async () => {
    if (!toggleTarget) return;
    setToggling(true);
    try {
      if (toggleTarget.status === "1") {
        await userApi.ban(toggleTarget.user_id);
        toast.success("ปิดใช้งานผู้ใช้สำเร็จ");
      } else {
        await userApi.unban(toggleTarget.user_id);
        toast.success("เปิดใช้งานผู้ใช้สำเร็จ");
      }
      setToggleTarget(null);
      fetchUsers();
      refreshStats();
    } catch {
      toast.error("ดำเนินการไม่สำเร็จ กรุณาลองใหม่");
    } finally {
      setToggling(false);
    }
  };

  const handleBulkBan = async () => {
    setBulkLoading(true);
    try {
      await Promise.all(selectedIds.map((id) => userApi.ban(id)));
      toast.success(`ปิดใช้งานผู้ใช้ ${selectedIds.length} คนสำเร็จ`);
      setBulkConfirmOpen(false);
      setSelectedIds([]);
      fetchUsers();
      refreshStats();
    } catch {
      toast.error("ปิดใช้งานผู้ใช้บางรายการไม่สำเร็จ");
    } finally {
      setBulkLoading(false);
    }
  };

  return (
    <div>
      {/* Header */}
      <div className="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-xl font-semibold text-[var(--color-text-primary)]">
            จัดการผู้ใช้งาน
          </h1>
          <p className="mt-1 text-sm text-[var(--color-text-secondary)]">
            ดูและจัดการบัญชีผู้ใช้ทั้งหมดในระบบ
          </p>
        </div>

        {selectedIds.length > 0 && (
          <Button
            variant="danger"
            size="sm"
            onClick={() => setBulkConfirmOpen(true)}
          >
            <Ban className="h-4 w-4" />
            ปิดใช้งานที่เลือก ({selectedIds.length})
          </Button>
        )}
      </div>

      {/* Stat cards */}
      <div className="mb-5 grid grid-cols-1 gap-4 sm:grid-cols-3">
        {statsLoading ? (
          <>
            <StatCardSkeleton />
            <StatCardSkeleton />
            <StatCardSkeleton />
          </>
        ) : (
          <>
            <Card className="flex-row items-center gap-3 p-4">
              <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-[var(--color-primary-light)]">
                <UsersIcon className="h-5 w-5 text-[var(--color-primary)]" />
              </div>
              <div>
                <p className="text-xs text-[var(--color-text-secondary)]">
                  ผู้ใช้ทั้งหมด
                </p>
                <p className="text-lg font-semibold text-[var(--color-text-primary)]">
                  {stats?.total ?? 0}
                </p>
              </div>
            </Card>
            <Card className="flex-row items-center gap-3 p-4">
              <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-green-100">
                <UserCheck className="h-5 w-5 text-green-600" />
              </div>
              <div>
                <p className="text-xs text-[var(--color-text-secondary)]">
                  ใช้งานได้
                </p>
                <p className="text-lg font-semibold text-[var(--color-text-primary)]">
                  {stats?.active ?? 0}
                </p>
              </div>
            </Card>
            <Card className="flex-row items-center gap-3 p-4">
              <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-red-100">
                <UserX className="h-5 w-5 text-red-600" />
              </div>
              <div>
                <p className="text-xs text-[var(--color-text-secondary)]">
                  ปิดใช้งาน
                </p>
                <p className="text-lg font-semibold text-[var(--color-text-primary)]">
                  {stats?.banned ?? 0}
                </p>
              </div>
            </Card>
          </>
        )}
      </div>

      {/* Filters */}
      <div className="mb-4">
        <UserFilters value={filters} onChange={setFilters} />
      </div>

      {/* Table */}
      <Card className="p-0">
        {initialLoading ? (
          <TableSkeleton
            columns={6}
            rows={pageSize}
            columnWidths={["w-5", "w-40", "w-24", "w-20", "w-24", "w-16"]}
          />
        ) : (
          <div className={loading ? "opacity-50 transition-opacity" : ""}>
            <UserTable
              data={users}
              loading={false}
              selectedIds={selectedIds}
              onSelectedIdsChange={setSelectedIds}
              onEdit={openEdit}
              onToggleStatus={setToggleTarget}
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
            selectedCount={selectedIds.length}
          />
        </div>
      )}

      {/* Edit dialog */}
      <Dialog
        open={!!editUser}
        onOpenChange={(open) => !open && setEditUser(null)}
      >
        <DialogContent>
          <DialogHeader>
            <DialogTitle>แก้ไขข้อมูลผู้ใช้</DialogTitle>
          </DialogHeader>

          {!editUser ? (
            <FormSkeleton fields={4} />
          ) : (
            <div className="space-y-4">
              <div className="grid grid-cols-2 gap-3">
                <div>
                  <Label htmlFor="first_name">ชื่อ</Label>
                  <Input
                    id="first_name"
                    value={editForm.first_name}
                    onChange={(e) =>
                      setEditForm({ ...editForm, first_name: e.target.value })
                    }
                  />
                </div>
                <div>
                  <Label htmlFor="last_name">นามสกุล</Label>
                  <Input
                    id="last_name"
                    value={editForm.last_name}
                    onChange={(e) =>
                      setEditForm({ ...editForm, last_name: e.target.value })
                    }
                  />
                </div>
              </div>
              <div>
                <Label htmlFor="email">อีเมล</Label>
                <Input
                  id="email"
                  type="email"
                  value={editForm.email}
                  onChange={(e) =>
                    setEditForm({ ...editForm, email: e.target.value })
                  }
                />
              </div>
              <div>
                <Label htmlFor="role">บทบาท</Label>
                <SimpleSelect
                  value={editForm.role}
                  onChange={(role) =>
                    setEditForm({ ...editForm, role: role as "User" | "Admin" })
                  }
                  options={[
                    { label: "ผู้ใช้ทั่วไป", value: "User" },
                    { label: "ผู้ดูแลระบบ", value: "Admin" },
                  ]}
                />
              </div>
            </div>
          )}

          <DialogFooter>
            <Button variant="outline" onClick={() => setEditUser(null)}>
              ยกเลิก
            </Button>
            <Button onClick={handleSaveEdit} loading={saving}>
              บันทึก
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* Ban/Unban confirm รายคน */}
      <AlertDialog
        open={!!toggleTarget}
        onOpenChange={(open) => !open && setToggleTarget(null)}
      >
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>
              {toggleTarget?.status === "1"
                ? "ยืนยันการปิดใช้งานผู้ใช้"
                : "ยืนยันการเปิดใช้งาน"}
            </AlertDialogTitle>
            <AlertDialogDescription>
              {toggleTarget?.status === "1"
                ? `ผู้ใช้ "${toggleTarget?.first_name} ${toggleTarget?.last_name}" จะไม่สามารถเข้าสู่ระบบได้จนกว่าจะเปิดใช้งาน`
                : `ผู้ใช้ "${toggleTarget?.first_name} ${toggleTarget?.last_name}" จะสามารถเข้าสู่ระบบได้ตามปกติ`}
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

      {/* Bulk ban confirm */}
      <AlertDialog open={bulkConfirmOpen} onOpenChange={setBulkConfirmOpen}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>ยืนยันการปิดใช้งานผู้ใช้ที่เลือก</AlertDialogTitle>
            <AlertDialogDescription>
              ผู้ใช้ที่เลือกทั้งหมด {selectedIds.length} คน
              จะปิดใช้งานทันที
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel disabled={bulkLoading}>ยกเลิก</AlertDialogCancel>
            <AlertDialogAction onClick={handleBulkBan} loading={bulkLoading}>
              ยืนยัน
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </div>
  );
}
