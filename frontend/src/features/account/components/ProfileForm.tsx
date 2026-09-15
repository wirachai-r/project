import { useEffect, useRef, useState } from "react";
import { Camera, Trash2, UserRound } from "lucide-react";
import { toast } from "sonner";
import { Button } from "@/components/ui/Button";
import { Input } from "@/components/ui/Input";
import { SimpleSelect } from "@/components/ui/SimpleSelect";
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
  AlertDialogTrigger,
} from "@/components/ui/AlertDialog";
import { uploadApi } from "@/lib/api/upload";
import { getErrorMessage } from "@/lib/getErrorMessage";
import type { ProfilePayload } from "@/lib/api/account";
import type { User } from "@/types/user";

interface ProfileFormProps {
  user: User;
  saving: boolean;
  onSubmit: (payload: ProfilePayload) => Promise<void>;
}

const SEX_OPTIONS = [
  { value: "M", label: "ชาย" },
  { value: "F", label: "หญิง" },
];

function calculateAge(dateOfBirth: string | null) {
  if (!dateOfBirth) return "";
  const birthDate = new Date(`${dateOfBirth}T00:00:00`);
  if (Number.isNaN(birthDate.getTime())) return "";
  const today = new Date();
  let age = today.getFullYear() - birthDate.getFullYear();
  const monthDifference = today.getMonth() - birthDate.getMonth();
  if (monthDifference < 0 || (monthDifference === 0 && today.getDate() < birthDate.getDate())) age -= 1;
  return age >= 0 ? String(age) : "";
}

export function ProfileForm({ user, saving, onSubmit }: ProfileFormProps) {
  const fileRef = useRef<HTMLInputElement>(null);
  const stagedImageRef = useRef<string | null>(null);
  const [uploading, setUploading] = useState(false);
  const [deleting, setDeleting] = useState(false);
  const [deleteDialogOpen, setDeleteDialogOpen] = useState(false);
  const [avatarPreview, setAvatarPreview] = useState(user.profile_image);
  const [form, setForm] = useState<ProfilePayload>({
    first_name: user.first_name,
    last_name: user.last_name,
    sex: user.sex,
    date_of_birth: user.date_of_birth,
    profile_image: user.system_profile_image,
  });
  const showingGoogleAvatar = Boolean(
    avatarPreview && !form.profile_image && user.avatar,
  );

  useEffect(() => {
    if (stagedImageRef.current === user.system_profile_image) {
      stagedImageRef.current = null;
    }
    setAvatarPreview(user.profile_image);
    setForm({
      first_name: user.first_name,
      last_name: user.last_name,
      sex: user.sex,
      date_of_birth: user.date_of_birth,
      profile_image: user.system_profile_image,
    });
  }, [user]);

  useEffect(
    () => () => {
      if (stagedImageRef.current) {
        void uploadApi.deleteImage(stagedImageRef.current).catch(() => undefined);
      }
    },
    [],
  );

  const update = (field: keyof ProfilePayload, value: string | null) =>
    setForm((current) => ({ ...current, [field]: value }));

  const uploadAvatar = async (file?: File) => {
    if (!file) return;
    setUploading(true);
    try {
      const uploaded = await uploadApi.uploadImage(file, "profiles");
      if (stagedImageRef.current) {
        await uploadApi.deleteImage(stagedImageRef.current).catch(() => undefined);
      }
      stagedImageRef.current = uploaded.path;
      update("profile_image", uploaded.path);
      setAvatarPreview(uploaded.url);
      toast.success("อัปโหลดรูปโปรไฟล์แล้ว กรุณากดบันทึกการเปลี่ยนแปลง");
    } catch (error) {
      toast.error(getErrorMessage(error));
    } finally {
      setUploading(false);
      if (fileRef.current) fileRef.current.value = "";
    }
  };

  const removeAvatar = async () => {
    setDeleting(true);
    try {
      if (stagedImageRef.current) {
        await uploadApi.deleteImage(stagedImageRef.current).catch(() => undefined);
        stagedImageRef.current = null;
      }
      await onSubmit({
        first_name: user.first_name,
        last_name: user.last_name,
        sex: user.sex,
        date_of_birth: user.date_of_birth,
        profile_image: null,
      });

      setAvatarPreview(user.avatar);
      setDeleteDialogOpen(false);
    } catch {
      update("profile_image", user.system_profile_image);
      setAvatarPreview(user.profile_image);
    } finally {
      setDeleting(false);
    }
  };

  return (
    <form
      className="space-y-6"
      onSubmit={(event) => {
        event.preventDefault();
        void onSubmit(form)
          .then(() => {
            stagedImageRef.current = null;
          })
          .catch(() => undefined);
      }}
    >
      <div className="flex flex-col gap-4 sm:flex-row sm:items-center">
        <div className="flex h-24 w-24 shrink-0 items-center justify-center overflow-hidden rounded-full bg-[var(--color-primary-light)] ring-4 ring-white shadow-sm">
          {avatarPreview ? (
            <img src={avatarPreview} alt="รูปโปรไฟล์" className="h-full w-full object-cover" />
          ) : (
            <UserRound className="h-10 w-10 text-[var(--color-primary)]" />
          )}
        </div>
        <div>
          <input ref={fileRef} type="file" accept="image/jpeg,image/png,image/webp" className="hidden" onChange={(event) => void uploadAvatar(event.target.files?.[0])} />
          <div className="flex flex-wrap gap-2">
            <Button type="button" variant="outline" loading={uploading} onClick={() => fileRef.current?.click()}>
              <Camera /> เปลี่ยนรูปโปรไฟล์
            </Button>
            {avatarPreview && form.profile_image && (
              <AlertDialog open={deleteDialogOpen} onOpenChange={setDeleteDialogOpen}>
                <AlertDialogTrigger asChild>
                  <Button
                    type="button"
                    variant="outline"
                    disabled={uploading || saving || deleting}
                    className="border-red-200 text-red-600 shadow-none hover:border-red-300 hover:bg-red-50 hover:text-red-700"
                  >
                    <Trash2 /> ลบรูปโปรไฟล์
                  </Button>
                </AlertDialogTrigger>
                <AlertDialogContent>
                  <AlertDialogHeader>
                    <AlertDialogTitle>ยืนยันการลบรูปโปรไฟล์</AlertDialogTitle>
                    <AlertDialogDescription>
                      รูปโปรไฟล์ปัจจุบันจะถูกลบออกจากบัญชีและพื้นที่จัดเก็บ การดำเนินการนี้ไม่สามารถย้อนกลับได้
                    </AlertDialogDescription>
                  </AlertDialogHeader>
                  <AlertDialogFooter>
                    <AlertDialogCancel disabled={deleting}>ยกเลิก</AlertDialogCancel>
                    <AlertDialogAction loading={deleting} onClick={() => void removeAvatar()}>
                      <Trash2 /> ยืนยันการลบ
                    </AlertDialogAction>
                  </AlertDialogFooter>
                </AlertDialogContent>
              </AlertDialog>
            )}
            {showingGoogleAvatar && (
              <span className="inline-flex h-9 items-center gap-2 rounded-lg bg-blue-50 px-3 text-sm font-medium text-blue-700">
                <span className="flex h-5 w-5 items-center justify-center rounded-full bg-white text-xs font-semibold shadow-sm">G</span>
                รูปจาก Google
              </span>
            )}
          </div>
          <p className="mt-2 text-xs text-[var(--color-text-secondary)]">รองรับ JPG, PNG หรือ WebP</p>
        </div>
      </div>

      <div className="grid gap-x-4 gap-y-5 sm:grid-cols-2">
        <Input label="ชื่อ" required value={form.first_name} onChange={(event) => update("first_name", event.target.value)} />
        <Input label="นามสกุล" required value={form.last_name} onChange={(event) => update("last_name", event.target.value)} />
        <div className="sm:col-span-2">
          <Input label="อีเมล" type="email" value={user.email} disabled aria-describedby="profile-email-help" />
          <p id="profile-email-help" className="mt-1.5 text-xs text-[var(--color-text-secondary)]">อีเมลเป็นข้อมูลสำหรับเข้าสู่ระบบและไม่สามารถเปลี่ยนได้</p>
        </div>
      </div>

      <div className="border-t border-[var(--color-border)] pt-5">
        <p className="mb-4 text-sm font-medium text-[var(--color-text-primary)]">ข้อมูลเพิ่มเติม</p>
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-[1fr_1.4fr_0.7fr]">
          <SimpleSelect
            label="เพศ"
            value={form.sex ?? ""}
            options={SEX_OPTIONS}
            placeholder="เลือกเพศ"
            onChange={(value) => update("sex", value)}
          />
          <Input
            label="วันเดือนปีเกิด"
            type="date"
            max={new Date().toISOString().slice(0, 10)}
            value={form.date_of_birth ?? ""}
            onChange={(event) => update("date_of_birth", event.target.value || null)}
          />
          <Input label="อายุ" value={calculateAge(form.date_of_birth)} disabled rightIcon={<span className="text-xs">ปี</span>} />
        </div>
        <p className="mt-2 text-xs text-[var(--color-text-secondary)]">อายุจะคำนวณอัตโนมัติจากวันเดือนปีเกิด</p>
      </div>

      <div className="flex justify-end">
        <Button type="submit" loading={saving}>บันทึกการเปลี่ยนแปลง</Button>
      </div>
    </form>
  );
}
