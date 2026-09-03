// components/ui/Button.tsx
import { forwardRef } from "react";
import { Slot } from "@radix-ui/react-slot";
import { cva, type VariantProps } from "class-variance-authority";
import { Loader2 } from "lucide-react";
import { cn } from "../../lib/utils";

const buttonVariants = cva(
  "inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-lg font-medium transition-colors disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:shrink-0",
  {
    variants: {
      variant: {
        primary:
          "bg-[var(--color-primary)] text-white shadow-sm hover:opacity-90",
        outline:
          "border border-[var(--color-border)] bg-transparent text-[var(--color-text-primary)] shadow-sm hover:bg-[var(--color-surface)]",
        ghost:
          "text-[var(--color-text-secondary)] hover:bg-[var(--color-surface)] hover:text-[var(--color-text-primary)]",
        danger:
          "bg-red-600 text-white shadow-sm hover:bg-red-700",
        link:
          "text-[var(--color-primary)] underline-offset-4 hover:underline",
      },
      size: {
        sm: "h-8 px-3 text-xs [&_svg]:size-3.5",
        md: "h-9 px-4 text-sm [&_svg]:size-4",
        lg: "h-10 px-5 text-sm [&_svg]:size-4",
        icon: "h-9 w-9 [&_svg]:size-4",
      },
    },
    defaultVariants: {
      variant: "primary",
      size: "md",
    },
  }
);

export interface ButtonProps
  extends React.ButtonHTMLAttributes<HTMLButtonElement>,
    VariantProps<typeof buttonVariants> {
  loading?: boolean;
  asChild?: boolean;
}

export const Button = forwardRef<HTMLButtonElement, ButtonProps>(
  (
    { className, variant, size, loading = false, disabled, asChild = false, children, ...props },
    ref
  ) => {
    const Comp = asChild ? Slot : "button";

    if (asChild && !loading) {
      return (
        <Comp
          ref={ref}
          className={cn(buttonVariants({ variant, size, className }))}
          {...props}
        >
          {children}
        </Comp>
      );
    }

    return (
      <button
        ref={ref}
        type={props.type ?? "button"}
        disabled={disabled || loading}
        className={cn(buttonVariants({ variant, size, className }))}
        style={{ outline: "none" }}
        {...props}
      >
        {loading && <Loader2 className="animate-spin" />}
        {children}
      </button>
    );
  }
);

Button.displayName = "Button";

// eslint-disable-next-line react-refresh/only-export-components
export { buttonVariants };
