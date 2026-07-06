interface BadgeProps {
  children: React.ReactNode;
  variant?: "default" | "success" | "danger" | "warning" | "primary";
  className?: string;
}

const variantStyles = {
  default:  "bg-[var(--color-surface)] text-[var(--color-text-secondary)]",
  success:  "bg-green-100 text-green-700",
  danger:   "bg-red-100 text-red-600",
  warning:  "bg-yellow-100 text-yellow-700",
  primary:  "bg-[var(--color-primary-light)] text-[var(--color-primary)]",
};

export function Badge({ children, variant = "default", className = "" }: BadgeProps) {
  return (
    <span
      className={[
        "inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium",
        variantStyles[variant],
        className,
      ].join(" ")}
    >
      {children}
    </span>
  );
}