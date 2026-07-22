import { forwardRef, useId } from "react";
import { cn } from "../../lib/utils";

export interface InputProps extends React.InputHTMLAttributes<HTMLInputElement> {
  label?: string;
  error?: string;
  leftIcon?: React.ReactNode;
  rightIcon?: React.ReactNode;
}

export const Input = forwardRef<HTMLInputElement, InputProps>(
  ({ label, error, leftIcon, rightIcon, id, className, ...props }, ref) => {
    const generatedId = useId();
    const inputId = id ?? generatedId;

    return (
      <div className="flex flex-col gap-1.5">
        {label && (
          <label
            htmlFor={inputId}
            className="text-sm font-medium text-[var(--color-text-primary)]"
          >
            {label}
          </label>
        )}

        <div className="relative">
          {leftIcon && (
            <div className="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-[var(--color-text-secondary)] [&_svg]:size-4">
              {leftIcon}
            </div>
          )}

          <input
            ref={ref}
            id={inputId}
            data-slot="input"
            aria-invalid={!!error}
            
            className={cn(
              "flex h-9 w-full min-w-0 rounded-md border border-[var(--color-border)] bg-transparent px-3 py-1 text-base transition-colors md:text-sm",
              "text-[var(--color-text-primary)] placeholder:text-[var(--color-text-secondary)]",
              "selection:bg-[var(--color-primary)] selection:text-white",
              "file:inline-flex file:h-7 file:border-0 file:bg-transparent file:text-sm file:font-medium file:text-[var(--color-text-primary)]",
              "disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50",
              "focus-visible:border-[var(--color-primary)] focus-visible:ring-[3px] focus-visible:ring-[var(--color-primary)]/20",
              error && "border-red-500 focus-visible:border-red-500",
              leftIcon && "pl-9",
              rightIcon && "pr-9",
              className,
            )}
            style={{ outline: "none" }}
            {...props}
          />

          {rightIcon && (
            <div className="absolute right-3 top-1/2 -translate-y-1/2 text-[var(--color-text-secondary)] [&_svg]:size-4">
              {rightIcon}
            </div>
          )}
        </div>

        {error && <p className="text-xs text-red-500">{error}</p>}
      </div>
    );
  },
);

Input.displayName = "Input";
