import { useMemo, useState } from "react";
import { Search, X, Plus, GripVertical } from "lucide-react";
import type { Symptom } from "@/types/symptom";
import { Tooltip, TooltipContent, TooltipTrigger } from "@/components/ui/Tooltip";
import { fuzzyIncludes } from "@/lib/fuzzySearch";

interface RelatedSymptomsPickerProps {
  symptoms?: Symptom[];
  items?: Array<{ id: string; name: string; nameEn?: string | null }>;
  selectedIds: string[];
  onChange: (ids: string[]) => void;
  availableTitle?: string;
  selectedTitle?: string;
  searchPlaceholder?: string;
  itemNoun?: string;
  compactOnMobile?: boolean;
}

type DragPayload = { id: string; from: "available" | "selected" };
type DragZone = "available" | "selected" | null;

export function RelatedSymptomsPicker({
  symptoms = [],
  items,
  selectedIds = [],
  onChange,
  availableTitle = "อาการทั้งหมด",
  selectedTitle = "อาการที่เลือก",
  searchPlaceholder = "ค้นหาอาการ...",
  itemNoun = "อาการ",
  compactOnMobile = false,
}: RelatedSymptomsPickerProps) {
  const [search, setSearch] = useState("");
  const [dragOverZone, setDragOverZone] = useState<DragZone>(null);

  const pickerItems = useMemo(
    () => items ?? symptoms.map((s) => ({ id: s.symptom_id, name: s.symptom_name, nameEn: s.symptom_name_en })),
    [items, symptoms],
  );

  const symptomMap = useMemo(() => {
    const map = new Map<string, { id: string; name: string; nameEn?: string | null }>();
    pickerItems.forEach((item) => map.set(item.id, item));
    return map;
  }, [pickerItems]);

  const availableList = useMemo(() => {
    const q = search.trim();
    return pickerItems.filter((item) => {
      if (selectedIds.includes(item.id)) return false;
      if (!q) return true;
      return fuzzyIncludes(`${item.id} ${item.name} ${item.nameEn ?? ""}`, q);
    });
  }, [pickerItems, selectedIds, search]);

  const selectedList = useMemo(
    () =>
      selectedIds
        .map((id) => symptomMap.get(id))
        .filter((item): item is { id: string; name: string; nameEn?: string | null } => !!item),
    [selectedIds, symptomMap],
  );

  const addSymptom = (id: string) => {
    if (!selectedIds.includes(id)) onChange([...selectedIds, id]);
  };

  const removeSymptom = (id: string) => {
    onChange(selectedIds.filter((s) => s !== id));
  };

  const handleDragStart = (
    e: React.DragEvent,
    id: string,
    from: "available" | "selected",
  ) => {
    const payload: DragPayload = { id, from };
    e.dataTransfer.setData("text/plain", JSON.stringify(payload));
    e.dataTransfer.effectAllowed = "move";
  };

  const handleDrop = (e: React.DragEvent, zone: "available" | "selected") => {
    e.preventDefault();
    setDragOverZone(null);
    try {
      const data = JSON.parse(
        e.dataTransfer.getData("text/plain"),
      ) as DragPayload;
      if (zone === "selected" && data.from === "available") {
        addSymptom(data.id);
      }
      if (zone === "available" && data.from === "selected") {
        removeSymptom(data.id);
      }
    } catch {
      // ข้อมูล drag ไม่ถูกต้อง ไม่ต้องทำอะไร
    }
  };

  return (
    <div className={`grid min-w-0 sm:grid-cols-2 ${compactOnMobile ? "gap-2 sm:gap-4" : "gap-4"}`}>
      {/* ฝั่งซ้าย: อาการทั้งหมด */}
      <div
        onDragOver={(e) => {
          e.preventDefault();
          setDragOverZone("available");
        }}
        onDragLeave={() => setDragOverZone(null)}
        onDrop={(e) => handleDrop(e, "available")}
        className={`min-w-0 rounded-lg border transition-colors ${
          dragOverZone === "available"
            ? "border-[var(--color-primary)] bg-[var(--color-primary-light)]"
            : "border-[var(--color-border)]"
        }`}
      >
        <div className={`border-b border-[var(--color-border)] ${compactOnMobile ? "p-2 sm:p-3" : "p-3"}`}>
          <p className="mb-2 text-sm font-medium text-[var(--color-text-primary)]">
            {availableTitle}
          </p>
          <div className="relative">
            <Search className="pointer-events-none absolute left-2.5 top-1/2 h-4 w-4 -translate-y-1/2 text-[var(--color-text-secondary)]" />
            <input
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              placeholder={searchPlaceholder}
              className="w-full rounded-md border border-[var(--color-border)] bg-[var(--color-surface)] py-1.5 pl-8 pr-2 text-sm text-[var(--color-text-primary)] outline-hidden focus:border-[var(--color-primary)]"
            />
          </div>
        </div>
        <div className={`${compactOnMobile ? "max-h-28 sm:max-h-72" : "max-h-72"} overflow-y-auto p-2`}>
          {availableList.length === 0 ? (
            <p className="p-4 text-center text-sm text-[var(--color-text-secondary)]">
              {search ? `ไม่พบ${itemNoun}ที่ค้นหา` : `เลือกครบทุก${itemNoun}แล้ว`}
            </p>
          ) : (
            <ul className="space-y-1">
              {availableList.map((item) => (
                <li
                  key={item.id}
                  draggable
                  onDragStart={(e) =>
                    handleDragStart(e, item.id, "available")
                  }
                  onClick={() => addSymptom(item.id)}
                  className="group flex cursor-grab items-center gap-2 rounded-md px-2 py-1.5 text-sm text-[var(--color-text-primary)] hover:bg-[var(--color-primary-light)] active:cursor-grabbing"
                >
                  <GripVertical className="h-4 w-4 shrink-0 text-[var(--color-text-secondary)] opacity-0 group-hover:opacity-100" />
                  <span className="flex-1 truncate">{item.name}</span>
                  <Tooltip>
                    <TooltipTrigger asChild>
                      <button
                        type="button"
                        onClick={(event) => {
                          event.stopPropagation();
                          addSymptom(item.id);
                        }}
                        className="shrink-0 rounded p-0.5 text-[var(--color-primary)] hover:bg-white"
                      >
                        <Plus className="h-4 w-4" />
                      </button>
                    </TooltipTrigger>
                    <TooltipContent>เพิ่ม{itemNoun}</TooltipContent>
                  </Tooltip>
                </li>
              ))}
            </ul>
          )}
        </div>
      </div>

      {/* ฝั่งขวา: อาการที่เลือก */}
      <div
        onDragOver={(e) => {
          e.preventDefault();
          setDragOverZone("selected");
        }}
        onDragLeave={() => setDragOverZone(null)}
        onDrop={(e) => handleDrop(e, "selected")}
        className={`min-w-0 rounded-lg border transition-colors ${
          dragOverZone === "selected"
            ? "border-[var(--color-primary)] bg-[var(--color-primary-light)]"
            : "border-[var(--color-border)]"
        }`}
      >
        <div className={`flex items-center justify-between border-b border-[var(--color-border)] ${compactOnMobile ? "p-2 sm:p-3" : "p-3"}`}>
          <p className="text-sm font-medium text-[var(--color-text-primary)]">
            {selectedTitle} ({selectedList.length})
          </p>
          {selectedList.length > 0 && (
            <button
              type="button"
              onClick={() => onChange([])}
              className="text-xs text-[var(--color-text-secondary)] hover:text-red-500"
            >
              ล้างทั้งหมด
            </button>
          )}
        </div>
        <div className={`${compactOnMobile ? "max-h-28 sm:max-h-72" : "max-h-72"} overflow-y-auto p-2`}>
          {selectedList.length === 0 ? (
            <p className="p-4 text-center text-sm text-[var(--color-text-secondary)]">
              ลาก{itemNoun}จากด้านซ้ายมาวางที่นี่ หรือคลิกเพื่อเพิ่ม
            </p>
          ) : (
            <ul className="space-y-1">
              {selectedList.map((item) => (
                <li
                  key={item.id}
                  draggable
                  onDragStart={(e) =>
                    handleDragStart(e, item.id, "selected")
                  }
                  className="group flex cursor-grab items-center gap-2 rounded-md bg-[var(--color-primary-light)] px-2 py-1.5 text-sm text-[var(--color-primary)] active:cursor-grabbing"
                >
                  <GripVertical className="h-4 w-4 shrink-0 opacity-60" />
                  <span className="flex-1 truncate">{item.name}</span>
                  <Tooltip>
                    <TooltipTrigger asChild>
                      <button
                        type="button"
                        onClick={() => removeSymptom(item.id)}
                        className="shrink-0 rounded p-0.5 hover:bg-white/50"
                        aria-label={`นำ${item.name}ออก`}
                      >
                        <X className="h-3.5 w-3.5" />
                      </button>
                    </TooltipTrigger>
                    <TooltipContent>นำ{itemNoun}ออก</TooltipContent>
                  </Tooltip>
                </li>
              ))}
            </ul>
          )}
        </div>
      </div>
    </div>
  );
}
