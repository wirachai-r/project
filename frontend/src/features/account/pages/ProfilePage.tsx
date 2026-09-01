import { useEffect, useState } from "react";
import { LockKeyhole, UserRound } from "lucide-react";
import { toast } from "sonner";
import { Card, CardDescription, CardHeader, CardTitle } from "@/components/ui/Card";
import { FormSkeleton } from "@/components/ui/FormSkeleton";
import { accountApi, type PasswordPayload, type ProfilePayload } from "@/lib/api/account";
import { getErrorMessage } from "@/lib/getErrorMessage";
import { useAuthStore } from "@/stores/authStore";
import { PasswordForm } from "../components/PasswordForm";
import { ProfileForm } from "../components/ProfileForm";

export function ProfilePage() {
  const user = useAuthStore((state) => state.user);
  const setUser = useAuthStore((state) => state.setUser);
  const [loading, setLoading] = useState(true);
  const [savingProfile, setSavingProfile] = useState(false);
  const [savingPassword, setSavingPassword] = useState(false);

  useEffect(() => {
    accountApi.getProfile()
      .then(setUser)
      .catch((error) => toast.error(getErrorMessage(error)))
      .finally(() => setLoading(false));
  }, [setUser]);

  const saveProfile = async (payload: ProfilePayload) => {
    setSavingProfile(true);
    try {
      const updated = await accountApi.updateProfile(payload);
      setUser(updated);
      toast.success("บันทึกข้อมูลโปรไฟล์สำเร็จ");
    } catch (error) {
      toast.error(getErrorMessage(error));
    } finally {
      setSavingProfile(false);
    }
  };

  const savePassword = async (payload: PasswordPayload) => {
    setSavingPassword(true);
    try {
      await accountApi.changePassword(payload);
      toast.success("เปลี่ยนรหัสผ่านสำเร็จ");
    } catch (error) {
      toast.error(getErrorMessage(error));
      throw error;
    } finally {
      setSavingPassword(false);
    }
  };

  return (
    <div className="mx-auto max-w-4xl space-y-5">
      <div><h1 className="text-xl font-semibold">โปรไฟล์ของฉัน</h1><p className="mt-1 text-sm text-[var(--color-text-secondary)]">จัดการข้อมูลส่วนตัว รูปโปรไฟล์ และรหัสผ่าน</p></div>
      {loading || !user ? <Card><FormSkeleton fields={4} /></Card> : (
        <>
          <Card>
            <CardHeader><div className="flex items-center gap-2"><UserRound className="h-5 w-5 text-[var(--color-primary)]" /><CardTitle>ข้อมูลส่วนตัว</CardTitle></div><CardDescription>ข้อมูลนี้ใช้แสดงในระบบผู้ดูแล</CardDescription></CardHeader>
            <ProfileForm user={user} saving={savingProfile} onSubmit={saveProfile} />
          </Card>
          <Card>
            <CardHeader><div className="flex items-center gap-2"><LockKeyhole className="h-5 w-5 text-[var(--color-primary)]" /><CardTitle>เปลี่ยนรหัสผ่าน</CardTitle></div><CardDescription>รหัสผ่านใหม่ต้องมีอย่างน้อย 8 ตัวอักษร</CardDescription></CardHeader>
            <PasswordForm saving={savingPassword} onSubmit={savePassword} />
          </Card>
        </>
      )}
    </div>
  );
}
