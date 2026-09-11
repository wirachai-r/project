import { useState } from "react";
import { Eye, EyeOff } from "lucide-react";
import { Button } from "@/components/ui/Button";
import { Input } from "@/components/ui/Input";
import type { PasswordPayload } from "@/lib/api/account";

interface PasswordFormProps {
  saving: boolean;
  onSubmit: (payload: PasswordPayload) => Promise<void>;
}

const emptyForm: PasswordPayload = { current_password: "", password: "", password_confirmation: "" };

export function PasswordForm({ saving, onSubmit }: PasswordFormProps) {
  const [form, setForm] = useState(emptyForm);
  const [error, setError] = useState<string>();
  const [visible, setVisible] = useState<Record<keyof PasswordPayload, boolean>>({
    current_password: false,
    password: false,
    password_confirmation: false,
  });
  const update = (field: keyof PasswordPayload, value: string) => setForm((current) => ({ ...current, [field]: value }));
  const visibilityButton = (field: keyof PasswordPayload, label: string) => (
    <button
      type="button"
      className="rounded text-[var(--color-text-secondary)] hover:text-[var(--color-text-primary)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-primary)]"
      aria-label={`${visible[field] ? "ซ่อน" : "แสดง"}${label}`}
      title={`${visible[field] ? "ซ่อน" : "แสดง"}${label}`}
      onClick={() => setVisible((current) => ({ ...current, [field]: !current[field] }))}
    >
      {visible[field] ? <Eye /> : <EyeOff />}
    </button>
  );

  return (
    <form
      className="space-y-4"
      onSubmit={(event) => {
        event.preventDefault();
        if (form.password.length < 8) return setError("รหัสผ่านใหม่ต้องมีอย่างน้อย 8 ตัวอักษร");
        if (form.password !== form.password_confirmation) return setError("รหัสผ่านใหม่และการยืนยันรหัสผ่านไม่ตรงกัน");
        setError(undefined);
        void onSubmit(form).then(() => setForm(emptyForm));
      }}
    >
      <Input label="รหัสผ่านปัจจุบัน" type={visible.current_password ? "text" : "password"} required autoComplete="current-password" value={form.current_password} onChange={(event) => update("current_password", event.target.value)} rightIcon={visibilityButton("current_password", "รหัสผ่านปัจจุบัน")} />
      <div className="grid gap-4 sm:grid-cols-2">
        <Input label="รหัสผ่านใหม่" type={visible.password ? "text" : "password"} required autoComplete="new-password" value={form.password} onChange={(event) => update("password", event.target.value)} rightIcon={visibilityButton("password", "รหัสผ่านใหม่")} />
        <Input label="ยืนยันรหัสผ่านใหม่" type={visible.password_confirmation ? "text" : "password"} required autoComplete="new-password" value={form.password_confirmation} onChange={(event) => update("password_confirmation", event.target.value)} error={error} rightIcon={visibilityButton("password_confirmation", "ยืนยันรหัสผ่านใหม่")} />
      </div>
      <div className="flex justify-end"><Button type="submit" loading={saving}>เปลี่ยนรหัสผ่าน</Button></div>
    </form>
  );
}
