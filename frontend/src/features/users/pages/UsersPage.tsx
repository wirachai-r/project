import { useState } from "react";
import { keepPreviousData, useQuery, useQueryClient } from "@tanstack/react-query";
import { toast } from "sonner";
import {
  Users as UsersIcon,
  UserCheck,
  UserX,
  Ban,
} from "lucide-react";
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
import { withRowNumbers } from "@/lib/tableRows";
import { TableSkeleton } from "../../../components/ui/TableSkeleton";
import { FilterBar } from "@/components/ui/FilterBar";
import { usePersistentTableSort } from "@/hooks/usePersistentTableSort";
import { usePersistentTablePagination } from "@/hooks/usePersistentTablePagination";
import { useResetPageOnChange } from "@/hooks/useResetPageOnChange";
import { queryKeys } from "@/lib/queryClient";
import { DataLoadError } from "@/components/ui/DataLoadError";

export default function UsersPage() {
  const queryClient = useQueryClient();
  const { page, setPage, pageSize, setPageSize } =
    usePersistentTablePagination("users");
  const [filters, setFilters] = useState<UserFilterValue>({
    search: "",
    role: "",
    status: "",
  });
  const [selectedIds, setSelectedIds] = useState<string[]>([]);
  const { sortKey, setSortKey, sortDirection, setSortDirection } =
    usePersistentTableSort("users");

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

  const listParams = {
    search: filters.search || undefined,
    role: filters.role || undefined,
    status: filters.status || undefined,
    page,
    per_page: pageSize,
    sort_by:
      (sortKey as "name" | "last_login" | "role" | "created_at" | undefined) ??
      undefined,
    sort_direction: sortDirection ?? undefined,
  };
  const usersQuery = useQuery({
    queryKey: queryKeys.users.list(listParams),
    queryFn: ({ signal }) => userApi.list(listParams, signal),
    placeholderData: keepPreviousData,
  });
  const statsQuery = useQuery<UserStats>({
    queryKey: queryKeys.users.stats(),
    queryFn: userApi.stats,
    staleTime: 60_000,
  });
  const users = usersQuery.data?.data ?? [];
  const lastPage = usersQuery.data?.meta?.last_page ?? 1;
  const totalItems = usersQuery.data?.meta?.total ?? 0;
  const loading = usersQuery.isFetching;
  const initialLoading = usersQuery.isPending;
  const stats = statsQuery.data ?? null;
  const statsLoading = statsQuery.isPending;

  // reset ไปหน้าแรกเมื่อ filter, page size, หรือ sort เปลี่ยน
  useResetPageOnChange(
    setPage,
    JSON.stringify([filters, pageSize, sortKey, sortDirection]),
    () => setSelectedIds([]),
  );

  const invalidateUsers = async () => {
    await Promise.all([
      queryClient.invalidateQueries({ queryKey: queryKeys.users.lists() }),
      queryClient.invalidateQueries({ queryKey: queryKeys.users.stats() }),
    ]);
  };

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
      await invalidateUsers();
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
      await invalidateUsers();
    } catch {
      toast.error("ดำเนินการไม่สำเร็จ กรุณาลองใหม่");
    } finally {
      setToggling(false);
    }
  };

  const handleBulkBan = async () => {
    setBulkLoading(true);
    try {
      const results = await Promise.allSettled(selectedIds.map((id) => userApi.ban(id)));
      const succeeded = results.filter((result) => result.status === "fulfilled").length;
      const failed = results.length - succeeded;
      if (succeeded) toast.success(`ปิดใช้งานผู้ใช้สำเร็จ ${succeeded} คน`);
      if (failed) toast.error(`ปิดใช้งานไม่สำเร็จ ${failed} คน`);
      setBulkConfirmOpen(false);
      setSelectedIds([]);
      await invalidateUsers();
    } catch {
      toast.error("ปิดใช้งานผู้ใช้ไม่สำเร็จ");
    } finally {
      setBulkLoading(false);
    }
  };

  const activePercent = stats?.total
    ? Math.round(((stats.active ?? 0) / stats.total) * 100)
    : 0;

  return (
    <div className="space-y-5">
      {/* Header */}
      <section className="relative overflow-hidden rounded-2xl bg-[var(--color-primary)] px-5 py-6 text-white shadow-[0_12px_30px_rgba(47,39,206,0.18)] sm:px-7">
        <div className="pointer-events-none absolute -right-16 -top-24 h-56 w-56 rounded-full bg-white/10" />
        <div className="pointer-events-none absolute -bottom-20 right-24 h-40 w-40 rounded-full bg-white/5" />
        <div className="relative flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
          <div className="flex items-center gap-4">
            <div>
              <h1 className="text-2xl font-semibold tracking-tight">
                จัดการผู้ใช้งาน
              </h1>
              {/* <p className="mt-1 text-sm text-white/70">
                ดูแลบัญชี บทบาท และสิทธิ์การเข้าใช้งานจากที่เดียว
              </p> */}
            </div>
          </div>

          {selectedIds.length > 0 && (
            <Button
              variant="danger"
              size="sm"
              onClick={() => setBulkConfirmOpen(true)}
              className="shadow-lg shadow-black/10"
            >
              <Ban className="h-4 w-4" />
              ปิดใช้งานที่เลือก ({selectedIds.length})
            </Button>
          )}
        </div>
      </section>

      {/* Stat cards */}
      <div className="grid grid-cols-1 gap-3 sm:grid-cols-3">
        {statsLoading ? (
          <>
            <StatCardSkeleton />
            <StatCardSkeleton />
            <StatCardSkeleton />
          </>
        ) : (
          <>
            <Card className="group flex-row items-center gap-4 overflow-hidden p-4 shadow-sm transition-all hover:-translate-y-0.5 hover:shadow-md">
              <div className="flex h-11 w-11 items-center justify-center rounded-xl bg-[var(--color-primary-light)] transition-transform group-hover:scale-105">
                <UsersIcon className="h-5 w-5 text-[var(--color-primary)]" />
              </div>
              <div className="min-w-0 flex-1">
                <p className="text-xs text-[var(--color-text-secondary)]">
                  ผู้ใช้ทั้งหมด
                </p>
                <p className="mt-0.5 text-2xl font-semibold tracking-tight text-[var(--color-text-primary)]">
                  {stats?.total ?? 0}
                </p>
              </div>
            </Card>
            <Card className="group flex-row items-center gap-4 overflow-hidden p-4 shadow-sm transition-all hover:-translate-y-0.5 hover:shadow-md">
              <div className="flex h-11 w-11 items-center justify-center rounded-xl bg-green-100 transition-transform group-hover:scale-105">
                <UserCheck className="h-5 w-5 text-green-600" />
              </div>
              <div className="min-w-0 flex-1">
                <p className="text-xs text-[var(--color-text-secondary)]">
                  ใช้งานได้
                </p>
                <p className="mt-0.5 text-2xl font-semibold tracking-tight text-[var(--color-text-primary)]">
                  {stats?.active ?? 0}
                </p>
                <p className="text-[11px] text-green-600">{activePercent}% ของทั้งหมด</p>
              </div>
            </Card>
            <Card className="group flex-row items-center gap-4 overflow-hidden p-4 shadow-sm transition-all hover:-translate-y-0.5 hover:shadow-md">
              <div className="flex h-11 w-11 items-center justify-center rounded-xl bg-red-100 transition-transform group-hover:scale-105">
                <UserX className="h-5 w-5 text-red-600" />
              </div>
              <div className="min-w-0 flex-1">
                <p className="text-xs text-[var(--color-text-secondary)]">
                  ปิดใช้งาน
                </p>
                <p className="mt-0.5 text-2xl font-semibold tracking-tight text-[var(--color-text-primary)]">
                  {stats?.banned ?? 0}
                </p>
              </div>
            </Card>
          </>
        )}
      </div>

      {/* Filters */}
      <FilterBar
        sortKey={sortKey}
        direction={sortDirection}
        dateSortKey="created_at"
        nameSortKey="name"
        onSortChange={(key, direction) => {
          setSortKey(key);
          setSortDirection(direction);
          setPage(1);
        }}
      >
        <UserFilters value={filters} onChange={setFilters} />
      </FilterBar>

      {/* Table */}

      <Card className="mt-4 gap-0 overflow-hidden p-0 shadow-sm">
        <div className="flex items-center justify-between border-b border-[var(--color-border)] px-4 py-4 sm:px-5">
          <div className="flex items-center gap-3">
            <div>
              <h2 className="text-sm font-semibold text-[var(--color-text-primary)]">รายชื่อผู้ใช้งาน</h2>
              {/* <p className="text-xs text-[var(--color-text-secondary)]">
                {totalItems.toLocaleString("th-TH")} บัญชีในระบบ
              </p> */}
            </div>
          </div>
          {selectedIds.length > 0 && (
            <span className="rounded-full bg-[var(--color-primary-light)] px-3 py-1 text-xs font-medium text-[var(--color-primary)]">
              เลือกแล้ว {selectedIds.length} รายการ
            </span>
          )}
        </div>
        {(initialLoading || loading) && users.length === 0 ? (
          <TableSkeleton
            columns={6}
            columnWidths={["w-5", "w-40", "w-24", "w-20", "w-24", "w-16"]}
          />
        ) : usersQuery.isError && users.length === 0 ? (
          <DataLoadError
            description="ไม่สามารถโหลดข้อมูลผู้ใช้งานได้"
            onRetry={() => void usersQuery.refetch()}
          />
        ) : (
          <div className={loading ? "opacity-50 transition-opacity" : ""}>
            <UserTable
              data={withRowNumbers(users, (page - 1) * pageSize + 1)}
              loading={false}
              selectedIds={selectedIds}
              onSelectedIdsChange={setSelectedIds}
              onEdit={openEdit}
              onToggleStatus={setToggleTarget}
              actionUserId={
                toggling
                  ? toggleTarget?.user_id
                  : saving
                    ? editUser?.user_id
                    : null
              }
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
