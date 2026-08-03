import { useMemo, useState } from "react";
import { Search, X, ArrowUp, ArrowDown, Stethoscope } from "lucide-react";
import { Input } from "../../../components/ui/Input";

interface DiseaseOption {
  disease_id: string;
  disease_name: string;
}

interface DiseaseMultiPickerProps {
  diseases: DiseaseOption[];
  selectedIds: string[];
  onChange: (ids: string[]) => void;
}

export function DiseaseMultiPicker({
  diseases,
  selectedIds,
  onChange,
}: DiseaseMultiPickerProps) {
  const [search, setSearch] = useState("");

  const nameOf = useMemo(() => {
    const m = new Map(diseases.map((d) => [d.disease_id, d.disease_name]));
    return (id: string) => m.get(id) ?? id;
  }, [diseases]);

  const available = useMemo(
    () =>
      diseases.filter(
        (d) =>
          !selectedIds.includes(d.disease_id) &&
          d.disease_name.toLowerCase().includes(search.toLowerCase()),
      ),
    [diseases, selectedIds, search],
  );

  function add(id: string) {
    onChange([...selectedIds, id]);
  }
  function remove(id: string) {
    onChange(selectedIds.filter((x) => x !== id));
  }
  function move(index: number, dir: -1 | 1) {
    const target = index + dir;
    if (target < 0 || target >= selectedIds.length) return;
    const next = [...selectedIds];
    [next[index], next[target]] = [next[target], next[index]];
    onChange(next);
  }

  return (
    <div className="grid gap-4 sm:grid-cols-2">
      {/* รายการโรคทั้งหมด */}
      <div className="rounded-lg border border-[var(--color-border)] p-3">
        <div className="relative mb-2">
          <Search className="pointer-events-none absolute left-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-[var(--color-text-secondary)]" />
          <Input
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            placeholder="ค้นหาโรค..."
            className="h-8 pl-8 text-xs"
          />
        </div>
        <ul className="max-h-64 space-y-1 overflow-y-auto">
          {available.length === 0 ? (
            <p className="py-4 text-center text-xs text-[var(--color-text-secondary)]">
              ไม่พบโรคที่ค้นหา
            </p>
          ) : (
            available.map((d) => (
              <li key={d.disease_id}>
                <button
                  type="button"
                  onClick={() => add(d.disease_id)}
                  className="flex w-full items-center gap-2 rounded-md px-2 py-1.5 text-left text-xs text-[var(--color-text-primary)] hover:bg-[var(--color-bg-subtle,#f1f5f9)]"
                >
                  <Stethoscope className="h-3.5 w-3.5 shrink-0 text-[var(--color-text-secondary)]" />
                  <span className="truncate">{d.disease_name}</span>
                </button>
              </li>
            ))
          )}
        </ul>
      </div>

      {/* โรคที่เลือก + ลำดับการแสดงผล */}
      <div className="rounded-lg border border-[var(--color-border)] p-3">
        <div className="mb-2 text-xs font-semibold text-[var(--color-text-primary)]">
          โรคที่เลือก ({selectedIds.length})
        </div>
        {selectedIds.length === 0 ? (
          <p className="py-4 text-center text-xs text-[var(--color-text-secondary)]">
            คลิกเลือกโรคจากรายการด้านซ้าย
          </p>
        ) : (
          <ul className="max-h-64 space-y-1 overflow-y-auto">
            {selectedIds.map((id, i) => (
              <li
                key={id}
                className="flex items-center justify-between gap-1.5 rounded-md border border-[var(--color-border)] px-2 py-1.5"
              >
                <span className="truncate text-xs font-medium text-[var(--color-text-primary)]">
                  {i + 1}. {nameOf(id)}
                </span>
                <div className="flex shrink-0 items-center gap-0.5">
                  <button
                    type="button"
                    onClick={() => move(i, -1)}
                    disabled={i === 0}
                    className="rounded p-1 text-[var(--color-text-secondary)] hover:bg-[var(--color-bg-subtle,#f1f5f9)] disabled:opacity-30"
                  >
                    <ArrowUp className="h-3.5 w-3.5" />
                  </button>
                  <button
                    type="button"
                    onClick={() => move(i, 1)}
                    disabled={i === selectedIds.length - 1}
                    className="rounded p-1 text-[var(--color-text-secondary)] hover:bg-[var(--color-bg-subtle,#f1f5f9)] disabled:opacity-30"
                  >
                    <ArrowDown className="h-3.5 w-3.5" />
                  </button>
                  <button
                    type="button"
                    onClick={() => remove(id)}
                    className="rounded p-1 text-red-500 hover:bg-red-50"
                  >
                    <X className="h-3.5 w-3.5" />
                  </button>
                </div>
              </li>
            ))}
          </ul>
        )}
      </div>
    </div>
  );
}