import { useCallback, useEffect, useMemo, useState } from "react";
import { CheckCheck } from "lucide-react";
import { toast } from "sonner";
import { Button } from "@/components/ui/Button";
import { Card } from "@/components/ui/Card";
import { TableSkeleton } from "@/components/ui/TableSkeleton";
import { DataLoadError } from "@/components/ui/DataLoadError";
import { accountApi } from "@/lib/api/account";
import { getErrorMessage } from "@/lib/getErrorMessage";
import type { PersonalNotification } from "@/types/notification";
import { NotificationList } from "../components/NotificationList";
import { PersonalNotificationDialog } from "../components/PersonalNotificationDialog";

type Filter = "all" | "system" | "personal";

export function MyNotificationsPage() {
  const [items, setItems] = useState<PersonalNotification[]>([]);
  const [loading, setLoading] = useState(true);
  const [loadError, setLoadError] = useState<string | null>(null);
  const [busyId, setBusyId] = useState<number | null>(null);
  const [markingAll, setMarkingAll] = useState(false);
  const [filter, setFilter] = useState<Filter>("all");
  const [selected, setSelected] = useState<PersonalNotification | null>(null);

  const load = useCallback(async () => {
    setLoading(true);
    setLoadError(null);
    try { setItems(await accountApi.notifications()); }
    catch (error) {
      const message = getErrorMessage(error);
      setLoadError(message);
    }
    finally { setLoading(false); }
  }, []);

  useEffect(() => { void load(); }, [load]);

  const visible = useMemo(() => items.filter((item) => filter === "all" || (filter === "system" ? item.type !== "U" : item.type === "U")), [filter, items]);
  const unreadCount = items.filter((item) => item.is_read === "N").length;
  const systemUnreadCount = items.filter((item) => item.type !== "U" && item.is_read === "N").length;
  const personalUnreadCount = items.filter((item) => item.type === "U" && item.is_read === "N").length;

  const read = async (item: PersonalNotification) => {
    setSelected(item);
    if (item.is_read === "Y") return;
    setBusyId(item.id);
    try {
      await accountApi.markAsRead(item.id);
      setItems((current) => current.map((entry) => entry.id === item.id ? { ...entry, is_read: "Y" } : entry));
      setSelected((current) => current?.id === item.id ? { ...current, is_read: "Y" } : current);
    } catch (error) { toast.error(getErrorMessage(error)); }
    finally { setBusyId(null); }
  };

  const dismiss = async (id: number) => {
    setBusyId(id);
    try {
      await accountApi.dismiss(id);
      setItems((current) => current.filter((item) => item.id !== id));
      toast.success("ลบการแจ้งเตือนแล้ว");
    } catch (error) { toast.error(getErrorMessage(error)); }
    finally { setBusyId(null); }
  };

  const readAll = async () => {
    setMarkingAll(true);
    try {
      await accountApi.markAllAsRead();
      setItems((current) => current.map((item) => ({ ...item, is_read: "Y" })));
      toast.success("อ่านการแจ้งเตือนทั้งหมดแล้ว");
    } catch (error) { toast.error(getErrorMessage(error)); }
    finally { setMarkingAll(false); }
  };

  return (
    <div className="mx-auto max-w-4xl space-y-5">
      <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div><h1 className="text-xl font-semibold">การแจ้งเตือน</h1><p className="mt-1 text-sm text-[var(--color-text-secondary)]">ติดตามข่าวสารและกิจกรรมที่เกี่ยวข้องกับบัญชีของคุณ</p></div>
        <Button variant="outline" loading={markingAll} disabled={unreadCount === 0} onClick={() => void readAll()}><CheckCheck />อ่านทั้งหมด</Button>
      </div>
      <div className="flex gap-2" role="group" aria-label="กรองการแจ้งเตือน">
        {([['all', `ทั้งหมด${unreadCount ? ` (${unreadCount})` : ""}`], ['system', `ระบบ${systemUnreadCount ? ` (${systemUnreadCount})` : ""}`], ['personal', `ส่วนตัว${personalUnreadCount ? ` (${personalUnreadCount})` : ""}`]] as const).map(([value, label]) => (
          <Button key={value} size="sm" variant={filter === value ? "primary" : "outline"} onClick={() => setFilter(value)}>{label}</Button>
        ))}
      </div>
      <Card className="overflow-hidden p-0">
        {loading && items.length === 0 ? <div className="p-5"><TableSkeleton columns={1} /></div> : loadError && items.length === 0 ? (
          <DataLoadError description={loadError} onRetry={() => void load()} />
        ) : <div className={loading ? "opacity-50 transition-opacity" : ""}><NotificationList items={visible} busyId={busyId} onRead={(item) => void read(item)} onDismiss={(id) => void dismiss(id)} /></div>}
      </Card>
      <PersonalNotificationDialog item={selected} onClose={() => setSelected(null)} />
    </div>
  );
}
