import { Plus, Trash2 } from "lucide-react";
import { Button } from "./Button";
import { Input } from "./Input";
import { Label } from "./Label";

interface ReferenceLinksInputProps {
  value: string[];
  onChange: (value: string[]) => void;
}

export function ReferenceLinksInput({ value, onChange }: ReferenceLinksInputProps) {
  const links = value.length > 0 ? value : [""];

  return (
    <div className="space-y-3">
      <div>
        <Label>แหล่งอ้างอิง</Label>
        <p className="mt-1 text-xs text-[var(--color-text-secondary)]">
          ใส่ URL ที่ขึ้นต้นด้วย http:// หรือ https:// ได้สูงสุด 20 ลิงก์
        </p>
      </div>
      {links.map((link, index) => (
        <div className="flex items-center gap-2" key={index}>
          <Input
            type="url"
            value={link}
            onChange={(event) => {
              const next = [...links];
              next[index] = event.target.value;
              onChange(next);
            }}
            placeholder="https://example.com/source"
            aria-label={`แหล่งอ้างอิงลำดับที่ ${index + 1}`}
          />
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
      {links.length < 20 && (
        <Button type="button" variant="outline" onClick={() => onChange([...links, ""])}>
          <Plus className="h-4 w-4" />
          เพิ่มลิงก์
        </Button>
      )}
    </div>
  );
}
