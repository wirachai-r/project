interface CardProps {
  children: React.ReactNode;
  className?: string;
  padding?: boolean;
}

export function Card({ children, className = "", padding = true }: CardProps) {
  return (
    <div
      className={[
        "rounded-xl border border-[var(--color-border)] bg-white",
        padding ? "p-5" : "",
        className,
      ].join(" ")}
    >
      {children}
    </div>
  );
}