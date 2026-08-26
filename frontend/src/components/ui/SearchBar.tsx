// components/ui/SearchBar.tsx
import { Search, X } from "lucide-react";
import { Input } from "./Input";
import { Tooltip, TooltipTrigger, TooltipContent } from "./Tooltip";
import { cn } from "../../lib/utils";

interface SearchBarProps {
  value: string;
  onChange: (value: string) => void;
  placeholder?: string;
  label?: string;
  showLabel?: boolean;
  className?: string;
}

export function SearchBar({
  value,
  onChange,
  placeholder = "ค้นหา...",
  label = "ค้นหา",
  showLabel = true,
  className = "",
}: SearchBarProps) {
  return (
    <div className={cn("flex flex-col gap-1.5", className)}>
      <div className="relative">
        <Input
          label={showLabel ? label : undefined}
          value={value}
          onChange={(e) => onChange(e.target.value)}
          placeholder={placeholder}
          aria-label={label}
          type="text"
          leftIcon={<Search className="h-4 w-4" style={{ outline: "none" }} />}
          rightIcon={
            value ? (
              <Tooltip>
                <TooltipTrigger asChild>
                  <button
                    onClick={() => onChange("")}
                    aria-label="ล้างคำค้นหา"
                    className="pointer-events-auto text-[var(--color-text-secondary)] hover:text-[var(--color-text-primary)]"
                    style={{ outline: "none" }}
                  >
                    <X className="h-3.5 w-3.5" />
                  </button>
                </TooltipTrigger>
                <TooltipContent>ล้างคำค้นหา</TooltipContent>
              </Tooltip>
            ) : undefined
          }
        />
      </div>
    </div>
  );
}
