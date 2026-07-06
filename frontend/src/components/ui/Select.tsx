import { ChevronDown } from "lucide-react";

interface SelectOption {
  value: string;
  label: string;
}

interface SelectProps {
  value: string;
  onChange: (value: string) => void;
  options: SelectOption[];
  label?: string;
  error?: string;
  placeholder?: string;
  className?: string;
  disabled?: boolean;
}

export function Select({
  value,
  onChange,
  options,
  label,
  error,
  placeholder,
  className = "",
  disabled = false,
}: SelectProps) {
  return (
    <div className={["flex flex-col gap-1.5", className].join(" ")}>
      {label && (
        <label className="text-sm font-medium text-[var(--color-text-primary)]">
          {label}
        </label>
      )}
      <div className="relative">
        <select
          value={value}
          onChange={(e) => onChange(e.target.value)}
          disabled={disabled}
          className={[
            "h-9 w-full appearance-none rounded-lg border px-3 pr-8 text-sm transition-colors focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]/20",
            error
              ? "border-red-400 text-[var(--color-text-primary)]"
              : "border-[var(--color-border)] text-[var(--color-text-primary)] focus:border-[var(--color-primary)]",
            disabled ? "cursor-not-allowed opacity-50" : "cursor-pointer bg-white",
          ].join(" ")}
        >
          {placeholder && (
            <option value="" disabled>
              {placeholder}
            </option>
          )}
          {options.map((opt) => (
            <option key={opt.value} value={opt.value}>
              {opt.label}
            </option>
          ))}
        </select>
        <ChevronDown className="pointer-events-none absolute right-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-[var(--color-text-secondary)]" />
      </div>
      {error && <p className="text-xs text-red-500">{error}</p>}
    </div>
  );
}