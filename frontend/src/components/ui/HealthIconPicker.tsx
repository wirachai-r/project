import { useMemo, useState } from "react";
import { Check, ImageOff, Search, X } from "lucide-react";
import { HEALTH_ICONS, HEALTH_ICON_NAMES } from "@/lib/healthIconRegistry";

import { fuzzyIncludes } from "@/lib/fuzzySearch";
import { cn } from "@/lib/utils";
import { Dialog, DialogContent, DialogHeader, DialogTitle } from "./Dialog";
import { Input } from "./Input";


export function HealthIconDisplay({ value, className }: { value: string; className?: string }) {
  const name = value.startsWith("health:") ? value.slice(7) : value;
  const Icon = HEALTH_ICONS[name];
  return Icon ? <Icon className={className} aria-hidden="true" /> : null;
}

export function HealthIconPicker({ value, onChange }: { value: string; onChange: (name: string) => void }) {
  const [open, setOpen] = useState(false);
  const [search, setSearch] = useState("");
  const selectedName = value.startsWith("health:") ? value.slice(7) : "";
  const filtered = useMemo(
    () => HEALTH_ICON_NAMES.filter((name) => fuzzyIncludes(name, search)),
    [search],
  );

  return (
    <>
      <button
        type="button"
        onClick={() => setOpen(true)}
        className="flex h-10 w-full items-center gap-2 rounded-lg border border-[var(--color-border)] px-3 text-sm text-[var(--color-text-primary)] transition-colors hover:bg-[var(--color-surface-hover)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-primary)]"
      >
        <span className="flex h-7 w-7 shrink-0 items-center justify-center rounded bg-[var(--color-primary-light)]">
          {selectedName ? (
            <HealthIconDisplay value={`health:${selectedName}`} className="h-5 w-5 text-[var(--color-primary)]" />
          ) : (
            <ImageOff className="h-4 w-4 text-[var(--color-text-secondary)]" />
          )}
        </span>
        <span className={cn(!selectedName && "text-[var(--color-text-secondary)]")}>
          {selectedName || "เลือกไอคอนอาการ"}
        </span>
      </button>

      <Dialog open={open} onOpenChange={(next) => { setOpen(next); if (!next) setSearch(""); }}>
        <DialogContent className="flex h-full flex-col md:h-auto md:max-h-[38rem] md:max-w-2xl">
          <DialogHeader className="shrink-0">
            <DialogTitle>เลือกไอคอนอาการ</DialogTitle>
          </DialogHeader>
          <div className="relative mb-4 shrink-0">
            <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-[var(--color-text-secondary)]" />
            <Input autoFocus value={search} onChange={(event) => setSearch(event.target.value)} placeholder="ค้นหา เช่น cough, fever, heart, lungs..." className="pl-9 pr-9" />
            {search && (
              <button type="button" onClick={() => setSearch("")} aria-label="ล้างการค้นหา" className="absolute right-2 top-1/2 flex h-6 w-6 -translate-y-1/2 items-center justify-center rounded-full text-[var(--color-text-secondary)] hover:bg-[var(--color-surface-hover)]">
                <X className="h-3.5 w-3.5" />
              </button>
            )}
          </div>
          <div className="min-h-0 flex-1 overflow-y-auto">
            {filtered.length ? (
              <div className="grid grid-cols-4 gap-2 pb-1 xs:grid-cols-5 sm:grid-cols-7">
                {filtered.map((name) => {
                  const selected = name === selectedName;
                  return (
                    <button key={name} type="button" title={name} onClick={() => { onChange(`health:${name}`); setOpen(false); setSearch(""); }} className={cn("group relative flex aspect-square items-center justify-center rounded-lg border transition-colors", selected ? "border-[var(--color-primary)] bg-[var(--color-primary-light)]" : "border-[var(--color-border)] hover:border-[var(--color-primary)]/40 hover:bg-[var(--color-surface-hover)]")}>
                      <HealthIconDisplay value={`health:${name}`} className="h-7 w-7 text-[var(--color-primary)]" />
                      {selected && <Check className="absolute -right-1 -top-1 h-4 w-4 rounded-full bg-[var(--color-primary)] p-0.5 text-white" />}
                    </button>
                  );
                })}
              </div>
            ) : (
              <div className="flex min-h-40 flex-col items-center justify-center gap-2">
                <ImageOff className="h-8 w-8 text-[var(--color-text-secondary)]" />
                <p className="text-sm text-[var(--color-text-secondary)]">ไม่พบไอคอนที่ค้นหา</p>
              </div>
            )}
          </div>
        </DialogContent>
      </Dialog>
    </>
  );
}
