interface SpinnerProps {
  fullscreen?: boolean;
  label?: string;
  size?: "sm" | "md" | "lg";
}

export function Spinner({ fullscreen = false, label, size = "md" }: SpinnerProps) {
  const sizeClass = {
    sm: "h-5 w-5 border-2",
    md: "h-8 w-8 border-2",
    lg: "h-12 w-12 border-[3px]",
  }[size];

  const spinner = (
    <div className="flex flex-col items-center gap-3">
      <div
        className={`${sizeClass} animate-spin rounded-full border-[var(--color-primary)] border-t-transparent`}
      />
      {label && (
        <p className="text-sm text-[var(--color-text-secondary)]">{label}</p>
      )}
    </div>
  );

  if (fullscreen) {
    return (
      <div className="flex min-h-screen w-full items-center justify-center">
        {spinner}
      </div>
    );
  }

  return spinner;
}