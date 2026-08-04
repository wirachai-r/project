import { forwardRef, useId } from "react";
import { cn } from "../../lib/utils";

export interface TextareaProps
  extends React.TextareaHTMLAttributes<HTMLTextAreaElement> {
  label?: string;
  error?: string;
}

export const Textarea = forwardRef<HTMLTextAreaElement, TextareaProps>(
  ({ label, error, rows = 3, id, className, ...props }, ref) => {
    const generatedId = useId();
    const textareaId = id ?? generatedId;

    return (
      <div className="flex flex-col gap-1.5">
        {label && (
          <label
            htmlFor={textareaId}
            className="text-sm font-medium text-[var(--color-text-primary)]"
          >
            {label}
          </label>
        )}

        <textarea
          ref={ref}
          id={textareaId}
          data-slot="textarea"
          rows={rows}
          aria-invalid={!!error}
          className={cn(
            "flex min-h-20 w-full min-w-0 resize-y rounded-md border border-[var(--color-border)] bg-transparent px-3 py-2 text-base transition-colors md:text-sm",
            "text-[var(--color-text-primary)] placeholder:text-[var(--color-text-secondary)]",
            "selection:bg-[var(--color-primary)] selection:text-white",
            "disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50",
            "focus-visible:border-[var(--color-primary)] focus-visible:ring-[3px] focus-visible:ring-[var(--color-primary)]/20",
            error && "border-red-500 focus-visible:border-red-500",
            className,
          )}
          style={{ outline: "none" }}
          {...props}
        />

        {error && <p className="text-xs text-red-500">{error}</p>}
      </div>
    );
  },
);

Textarea.displayName = "Textarea";
