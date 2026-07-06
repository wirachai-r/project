interface StatusToggleProps {
  active: boolean;
  onChange: () => void;
  disabled?: boolean;
}

export function StatusToggle({ active, onChange, disabled = false }: StatusToggleProps) {
  return (
    <button
      type="button"
      role="switch"
      aria-checked={active}
      onClick={onChange}
      disabled={disabled}
      className={[
        "relative inline-flex h-5 w-9 shrink-0 cursor-pointer items-center rounded-full transition-colors duration-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-primary)] focus-visible:ring-offset-1 disabled:cursor-not-allowed disabled:opacity-50",
        active ? "bg-[var(--color-primary)]" : "bg-[var(--color-border)]",
      ].join(" ")}
    >
      <span
        className={[
          "inline-block h-3.5 w-3.5 transform rounded-full bg-white shadow transition-transform duration-200",
          active ? "translate-x-4" : "translate-x-0.5",
        ].join(" ")}
      />
    </button>
  );
}