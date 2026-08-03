import { useState } from "react";
import { Button } from "@/components/ui/Button";

interface QuickAddPopoverProps {
  left: number;
  top: number;
  placeholder: string;
  submitLabel?: string;
  onSubmit: (text: string) => void;
  onClose: () => void;
}

export function QuickAddPopover({
  left,
  top,
  placeholder,
  submitLabel = "เพิ่ม",
  onSubmit,
  onClose,
}: QuickAddPopoverProps) {
  const [value, setValue] = useState("");

  function handleSubmit() {
    if (!value.trim()) return;
    onSubmit(value.trim());
    setValue("");
  }

  return (
    <div
      className="absolute z-20 w-64 rounded-lg border border-[var(--color-border)] bg-white p-2.5 shadow-lg"
      style={{ left, top }}
      onClick={(e) => e.stopPropagation()}
    >
      <input
        autoFocus
        value={value}
        onChange={(e) => setValue(e.target.value)}
        onKeyDown={(e) => {
          if (e.key === "Enter") handleSubmit();
          if (e.key === "Escape") onClose();
        }}
        placeholder={placeholder}
        className="w-full rounded-md border border-[var(--color-border)] px-2 py-1.5 text-sm"
      />
      <div className="mt-2 flex justify-end gap-1.5">
        <Button variant="outline" size="sm" onClick={onClose}>
          ยกเลิก
        </Button>
        <Button size="sm" onClick={handleSubmit}>
          {submitLabel}
        </Button>
      </div>
    </div>
  );
}