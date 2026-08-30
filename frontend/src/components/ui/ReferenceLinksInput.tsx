import { BookOpen, Plus, Trash2 } from "lucide-react";
import { Button } from "./Button";
import { Input } from "./Input";

interface ReferenceLinksInputProps {
  value?: string[] | null;
  onChange: (value: string[]) => void;
}

export function ReferenceLinksInput({ value, onChange }: ReferenceLinksInputProps) {
  const links = Array.isArray(value) && value.length > 0 ? value : [""];

  return (
    <div className="flex flex-col gap-5">
      <div className="flex items-center gap-2.5 border-b border-[var(--color-border)] pb-3">
        <span className="flex h-7 w-7 shrink-0 items-center justify-center rounded-md bg-[var(--color-primary-light)]">
          <BookOpen className="h-4 w-4 text-[var(--color-primary)]" />
        </span>
        <div>
          <h3 className="font-semibold leading-none text-[var(--color-text-primary)]">
            แหล่งอ้างอิง
          </h3>
          <p className="mt-1.5 text-xs text-[var(--color-text-secondary)]">
            ใส่ชื่อหนังสือ วารสาร หรือ URL และเพิ่มได้ตามต้องการ
          </p>
        </div>
      </div>
      <div className="space-y-3">
        {links.map((link, index) => (
          <div className="flex items-center gap-2" key={index}>
            <div className="min-w-0 flex-1">
              <Input
                type="text"
                value={link}
                onChange={(event) => {
                  const next = [...links];
                  next[index] = event.target.value;
                  onChange(next);
                }}
                placeholder="เช่น คู่มือปฐมพยาบาล หรือ https://example.com/source"
                aria-label={`แหล่งอ้างอิงลำดับที่ ${index + 1}`}
              />
            </div>
            <Button
              type="button"
              variant="outline"
              aria-label={`ลบแหล่งอ้างอิงลำดับที่ ${index + 1}`}
              onClick={() => onChange(links.filter((_, itemIndex) => itemIndex !== index))}
            >
              <Trash2 className="h-4 w-4" />
            </Button>
          </div>
        ))}
        <Button type="button" variant="outline" onClick={() => onChange([...links, ""])}>
          <Plus className="h-4 w-4" />
          เพิ่มแหล่งอ้างอิง
        </Button>
      </div>
    </div>
  );
}
