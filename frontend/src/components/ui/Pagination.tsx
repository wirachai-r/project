// components/ui/Pagination.tsx
import {
  ChevronLeft,
  ChevronRight,
  ChevronsLeft,
  ChevronsRight,
} from "lucide-react";
import { cn } from "../../lib/utils";
import { SimpleSelect } from "./SimpleSelect";

interface PaginationProps {
  /** หน้าปัจจุบัน (1-indexed) */
  current: number;
  /** จำนวนหน้าทั้งหมด */
  total: number;
  onChange: (page: number) => void;

  /** ถ้าส่งมาครบทั้งคู่ จะแสดง selector "จำนวนแถวต่อหน้า" */
  pageSize?: number;
  onPageSizeChange?: (size: number) => void;
  pageSizeOptions?: number[];

  /** จำนวนรายการทั้งหมด — ถ้ามี จะโชว์ "X รายการ" ต่อท้าย label หน้า */
  totalItems?: number;
  /** จำนวนแถวที่ถูกเลือกไว้ (เช่นใน UsersPage ที่มี selectedIds) */
  selectedCount?: number;

  className?: string;
}

export function Pagination({
  current,
  total,
  onChange,
  pageSize,
  onPageSizeChange,
  pageSizeOptions = [5, 10, 20, 30, 40, 50],
  totalItems,
  selectedCount,
  className,
}: PaginationProps) {
  if (total <= 0) return null;

  // clamp กัน current หลุดกรอบจาก state ภายนอก
  const safeCurrent = Math.min(Math.max(current, 1), total);
  const showPageSize = pageSize !== undefined && !!onPageSizeChange;

  return (
    <div className={cn("w-full", className)}>
      {/* ===== Mobile (< md): เหลือแค่ 2 ปุ่ม prev/next ===== */}
      <div className="flex items-center justify-between gap-4 md:hidden">
        <div className="text-sm font-medium text-[var(--color-text-primary)]">
          หน้า {safeCurrent} จาก {total}
        </div>

        <div className="flex items-center gap-2">
          <button
            onClick={() => onChange(safeCurrent - 1)}
            disabled={safeCurrent === 1}
            aria-label="หน้าก่อนหน้า"
            className="flex h-8 w-8 cursor-pointer items-center justify-center rounded-full border border-[var(--color-border)] text-[var(--color-text-secondary)] transition-colors hover:border-[var(--color-primary)] hover:text-[var(--color-primary)] active:bg-[var(--color-primary)] active:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-primary)] focus-visible:ring-offset-1 disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-40"
          >
            <ChevronLeft className="h-4 w-4" />
          </button>
          <button
            onClick={() => onChange(safeCurrent + 1)}
            disabled={safeCurrent === total}
            aria-label="หน้าถัดไป"
            className="flex h-8 w-8 cursor-pointer items-center justify-center rounded-full border border-[var(--color-border)] text-[var(--color-text-secondary)] transition-colors hover:border-[var(--color-primary)] hover:text-[var(--color-primary)] active:bg-[var(--color-primary)] active:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-primary)] focus-visible:ring-offset-1 disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-40"
          >
            <ChevronRight className="h-4 w-4" />
          </button>
        </div>
      </div>

      {/* ===== Desktop (>= md): layout เดิมครบทุกปุ่ม + selector ===== */}
      <div className="hidden md:flex md:flex-row md:items-center md:justify-between md:gap-4">
        {/* ซ้าย: จำนวนที่เลือก/ทั้งหมด */}
        <div className="flex-1 text-sm text-[var(--color-text-secondary)]">
          {selectedCount !== undefined && totalItems !== undefined
            ? `เลือก ${selectedCount} จาก ${totalItems} รายการ`
            : totalItems !== undefined
              ? `ทั้งหมด ${totalItems} รายการ`
              : null}
        </div>

        <div className="flex w-fit flex-row items-center gap-8">
          {/* จำนวนแถวต่อหน้า */}
          {showPageSize && (
            <div className="flex items-center gap-2">
              <label
                htmlFor="pagination-page-size"
                className="text-sm font-medium text-[var(--color-text-primary)]"
              >
                แถวต่อหน้า
              </label>
              <SimpleSelect
                value={String(pageSize)}
                onChange={(value) => onPageSizeChange?.(Number(value))}
                options={pageSizeOptions.map((size) => ({
                  label: String(size),
                  value: String(size),
                }))}
                className="w-20"
              />
            </div>
          )}

          {/* หน้า X จาก Y */}
          <div className="flex w-fit items-center justify-center text-sm font-medium text-[var(--color-text-primary)]">
            หน้า {safeCurrent} จาก {total}
          </div>

          {/* ปุ่มควบคุม */}
          <div className="flex items-center gap-1.5">
            <button
              onClick={() => onChange(1)}
              disabled={safeCurrent === 1}
              aria-label="หน้าแรก"
              className="flex h-8 w-8 cursor-pointer items-center justify-center rounded-lg text-[var(--color-text-secondary)] transition-colors hover:bg-[var(--color-primary-light)] hover:text-[var(--color-primary)] active:bg-[var(--color-primary)] active:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-primary)] focus-visible:ring-offset-1 disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-40"
            >
              <ChevronsLeft className="h-4 w-4" />
            </button>
            <button
              onClick={() => onChange(safeCurrent - 1)}
              disabled={safeCurrent === 1}
              aria-label="หน้าก่อนหน้า"
              className="flex h-8 w-8 cursor-pointer items-center justify-center rounded-lg text-[var(--color-text-secondary)] transition-colors hover:bg-[var(--color-primary-light)] hover:text-[var(--color-primary)] active:bg-[var(--color-primary)] active:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-primary)] focus-visible:ring-offset-1 disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-40"
            >
              <ChevronLeft className="h-4 w-4" />
            </button>
            <button
              onClick={() => onChange(safeCurrent + 1)}
              disabled={safeCurrent === total}
              aria-label="หน้าถัดไป"
              className="flex h-8 w-8 cursor-pointer items-center justify-center rounded-lg text-[var(--color-text-secondary)] transition-colors hover:bg-[var(--color-primary-light)] hover:text-[var(--color-primary)] active:bg-[var(--color-primary)] active:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-primary)] focus-visible:ring-offset-1 disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-40"
            >
              <ChevronRight className="h-4 w-4" />
            </button>
            <button
              onClick={() => onChange(total)}
              disabled={safeCurrent === total}
              aria-label="หน้าสุดท้าย"
              className="flex h-8 w-8 cursor-pointer items-center justify-center rounded-lg text-[var(--color-text-secondary)] transition-colors hover:bg-[var(--color-primary-light)] hover:text-[var(--color-primary)] active:bg-[var(--color-primary)] active:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-primary)] focus-visible:ring-offset-1 disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-40"
            >
              <ChevronsRight className="h-4 w-4" />
            </button>
          </div>
        </div>
      </div>
    </div>
  );
}