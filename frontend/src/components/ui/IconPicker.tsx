import { useMemo, useState } from "react";
import * as Icons from "lucide-react";
import { Search, Check, ImageOff, X } from "lucide-react";
import { Input } from "./Input";
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
} from "./Dialog";
import { cn } from "../../lib/utils";

// ชุดไอคอนที่เกี่ยวข้องกับสุขภาพ/การแพทย์ที่ใช้บ่อย
// (คัดมาบางส่วนจาก lucide-react เพื่อไม่ต้อง render ทั้งพันไอคอน)
const ICON_NAMES = [
  "Activity", "Stethoscope", "Heart", "HeartPulse", "HeartHandshake",
  "Pill", "Syringe", "Thermometer", "ThermometerSun", "ThermometerSnowflake",
  "Brain", "Bone", "Eye", "EyeOff", "Ear", "Baby", "Ambulance", "Hospital",
  "Droplet", "Droplets", "Wind", "Zap", "AlertTriangle", "ShieldAlert",
  "ShieldCheck", "ShieldPlus", "Flame", "Bug", "Microscope", "TestTube",
  "TestTube2", "FlaskConical", "Waves", "Sun", "Moon", "CloudRain",
  "Snowflake", "Utensils", "Apple", "Salad", "Dumbbell", "Bed", "Frown",
  "Smile", "Meh", "Users", "User", "UserRound", "Accessibility", "Watch",
  "Clock", "Calendar", "ClipboardList", "FileText", "Folder", "FolderOpen",
  "Tag", "Star", "Siren", "LifeBuoy", "Cross",
] as const;

interface IconPickerProps {
  value: string;
  onChange: (name: string) => void;
}

export function IconPicker({ value, onChange }: IconPickerProps) {
  const [open, setOpen] = useState(false);
  const [search, setSearch] = useState("");

  const SelectedIcon =
    value && (Icons as Record<string, unknown>)[value]
      ? ((Icons as Record<string, unknown>)[value] as typeof Icons.Activity)
      : null;

  const filtered = useMemo(
    () =>
      ICON_NAMES.filter((n) =>
        n.toLowerCase().includes(search.trim().toLowerCase()),
      ),
    [search],
  );

  return (
    <>
      <button
        type="button"
        onClick={() => setOpen(true)}
        className="flex h-10 w-full items-center gap-2 rounded-lg border border-[var(--color-border)] px-3 text-sm text-[var(--color-text-primary)] transition-colors hover:bg-[var(--color-surface-hover)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-primary)]"
      >
        <span className="flex h-6 w-6 shrink-0 items-center justify-center rounded bg-[var(--color-primary-light)]">
          {SelectedIcon ? (
            <SelectedIcon className="h-4 w-4 text-[var(--color-primary)]" />
          ) : (
            <ImageOff className="h-4 w-4 text-[var(--color-text-secondary)]" />
          )}
        </span>
        <span className={cn(!value && "text-[var(--color-text-secondary)]")}>
          {value || "เลือกไอคอน"}
        </span>
      </button>

      <Dialog
        open={open}
        onOpenChange={(next) => {
          setOpen(next);
          if (!next) setSearch("");
        }}
      >
        <DialogContent className="flex h-full flex-col md:h-auto md:max-h-[32rem]">
          <DialogHeader className="shrink-0">
            <DialogTitle>เลือกไอคอน</DialogTitle>
          </DialogHeader>

          {/* Search — sticky ด้านบน ไม่เลื่อนตามกริด */}
          <div className="relative mb-4 shrink-0">
            <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-[var(--color-text-secondary)]" />
            <Input
              autoFocus
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              placeholder="ค้นหาไอคอน..."
              className="pl-9 pr-9"
            />
            {search && (
              <button
                type="button"
                onClick={() => setSearch("")}
                aria-label="ล้างการค้นหา"
                className="absolute right-2 top-1/2 flex h-6 w-6 -translate-y-1/2 items-center justify-center rounded-full text-[var(--color-text-secondary)] hover:bg-[var(--color-surface-hover)]"
              >
                <X className="h-3.5 w-3.5" />
              </button>
            )}
          </div>

          {/* Grid ไอคอน — ยืดเต็มพื้นที่ที่เหลือ, scroll ในตัวเอง */}
          <div className="min-h-0 flex-1 overflow-y-auto">
            {filtered.length > 0 ? (
              <div className="grid grid-cols-4 gap-2 pb-1 xs:grid-cols-5 sm:grid-cols-6">
                {filtered.map((name) => {
                  const IconComp = (Icons as Record<string, unknown>)[
                    name
                  ] as typeof Icons.Activity | undefined;
                  if (!IconComp) return null;
                  const selected = value === name;
                  return (
                    <button
                      key={name}
                      type="button"
                      onClick={() => {
                        onChange(name);
                        setOpen(false);
                        setSearch("");
                      }}
                      title={name}
                      className={cn(
                        "group relative flex aspect-square w-full flex-col items-center justify-center rounded-lg border transition-colors",
                        selected
                          ? "border-[var(--color-primary)] bg-[var(--color-primary-light)]"
                          : "border-[var(--color-border)] hover:border-[var(--color-primary)]/40 hover:bg-[var(--color-surface-hover)]",
                      )}
                    >
                      <IconComp
                        className={cn(
                          "h-5 w-5 transition-colors",
                          selected
                            ? "text-[var(--color-primary)]"
                            : "text-[var(--color-text-primary)] group-hover:text-[var(--color-primary)]",
                        )}
                      />
                      {selected && (
                        <Check className="absolute -right-1 -top-1 h-4 w-4 rounded-full bg-[var(--color-primary)] p-0.5 text-white" />
                      )}
                    </button>
                  );
                })}
              </div>
            ) : (
              <div className="flex h-full min-h-[10rem] flex-col items-center justify-center gap-2 text-center">
                <ImageOff className="h-8 w-8 text-[var(--color-text-secondary)]" />
                <p className="text-sm text-[var(--color-text-secondary)]">
                  ไม่พบไอคอนที่ค้นหา
                </p>
              </div>
            )}
          </div>

          {/* Footer เล็กๆ บอกจำนวนผลลัพธ์ */}
          {/* <div className="mt-3 shrink-0 text-center text-xs text-[var(--color-text-secondary)]">
            พบ {filtered.length} ไอคอน
          </div> */}
        </DialogContent>
      </Dialog>
    </>
  );
}