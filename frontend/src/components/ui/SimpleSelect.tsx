import { useId } from "react";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "./Select";

interface SelectOption {
  value: string;
  label: string;
}

interface SimpleSelectProps {
  value: string;
  onChange: (value: string) => void;
  options: SelectOption[];
  label?: string;
  error?: string;
  placeholder?: string;
  className?: string;
  disabled?: boolean;
}

// Radix reserves the empty string for clearing a Select, so filter options
// such as "ทุกสถานะ" need a non-empty internal value.
const EMPTY_OPTION_VALUE = "__all__";

export function SimpleSelect({
  value,
  onChange,
  options,
  label,
  error,
  placeholder = "เลือก...",
  className = "",
  disabled = false,
}: SimpleSelectProps) {
  const selectId = useId();
  const selectedLabel = options.find((opt) => opt.value === value)?.label;
  const visibleLabel =
    label ??
    (placeholder !== "เลือก..."
      ? placeholder.startsWith("ทุก")
        ? placeholder.slice(3)
        : placeholder
      : undefined);

  return (
    <div className={["flex flex-col gap-1.5", className].join(" ")}>
      {visibleLabel && (
        <label htmlFor={selectId} className="text-sm font-medium text-[var(--color-text-primary)]">
          {visibleLabel}
        </label>
      )}
      <Select
        value={value === "" ? EMPTY_OPTION_VALUE : value}
        onValueChange={(nextValue) =>
          onChange(nextValue === EMPTY_OPTION_VALUE ? "" : nextValue)
        }
        disabled={disabled}
      >
        <SelectTrigger id={selectId} error={!!error}>
          <SelectValue placeholder={placeholder}>
            {selectedLabel ?? placeholder}
          </SelectValue>
        </SelectTrigger>
        <SelectContent>
          {options.map((opt) => (
            <SelectItem
              key={opt.value || EMPTY_OPTION_VALUE}
              value={opt.value === "" ? EMPTY_OPTION_VALUE : opt.value}
            >
              {opt.label}
            </SelectItem>
          ))}
        </SelectContent>
      </Select>
      {error && <p className="text-xs text-red-500">{error}</p>}
    </div>
  );
}
