// ResizableImageView.tsx
import {
  NodeViewWrapper,
  type NodeViewProps,
} from "@tiptap/react";
import { AlignLeft, AlignCenter, AlignRight, Trash2 } from "lucide-react";
import type { ImageAlign } from "./ResizableImage";
import { IMAGE_SIZE_PRESETS, type ImageSizePreset } from "./ResizableImage";

const SIZE_LABELS: Record<ImageSizePreset, string> = {
  25: "S",
  50: "M",
  75: "L",
  100: "เต็ม",
};

export function ResizableImageView({
  node,
  updateAttributes,
  selected,
  deleteNode,
}: NodeViewProps) {
  const { src, alt, align, width } = node.attrs as {
    src: string;
    alt: string | null;
    align: ImageAlign;
    width: ImageSizePreset;
  };

  const setAlign = (a: ImageAlign) => updateAttributes({ align: a });
  const setSize = (percent: ImageSizePreset) =>
    updateAttributes({ width: percent });

  // บังคับเป็น block เต็มบรรทัดเสมอ -> 1 แถวต่อ 1 รูป
  const wrapperStyle: React.CSSProperties = {
    display: "block",
    width: "100%",
    margin: "8px 0",
    position: "relative",
    lineHeight: 0,
  };

  const imgAlignStyle: React.CSSProperties =
    align === "left"
      ? { marginRight: "auto" }
      : align === "right"
      ? { marginLeft: "auto" }
      : { marginLeft: "auto", marginRight: "auto" }; // center (default)

  return (
    <NodeViewWrapper
      as="div"
      style={wrapperStyle}
      className={
        selected
          ? "rounded-lg ring-2 ring-[var(--color-primary)]"
          : "rounded-lg"
      }
    >
      <div
        style={{
          position: "relative",
          display: "block",
          ...imgAlignStyle,
          width: `${width}%`,
        }}
      >
        <img
          src={src}
          alt={alt ?? ""}
          draggable={false}
          style={{
            width: "100%",
            height: "auto",
            display: "block",
            borderRadius: "0.5rem",
          }}
        />

        {selected && (
          <span
            contentEditable={false}
            draggable={false}
            className="pointer-events-none absolute bottom-2 left-1/2 z-10 -translate-x-1/2 rounded-full bg-black/70 px-2 py-0.5 text-xs font-medium text-white"
          >
            {width}%
          </span>
        )}

        {selected && (
          <div
            contentEditable={false}
            draggable={false}
            onDragStart={(e) => e.preventDefault()}
            className="absolute -top-9 left-0 z-10 flex h-8 w-max max-w-full items-center gap-0.5 overflow-x-auto whitespace-nowrap rounded-md border border-[var(--color-border)] bg-white p-0.5 leading-none shadow-sm"
          >
            {/* ปุ่มเลือกขนาดคงที่ แทนการลากอิสระ — เก็บเป็น % จึงแสดงผลสัดส่วนเดิมได้ทั้งบนเว็บและมือถือ */}
            {IMAGE_SIZE_PRESETS.map((preset) => (
              <button
                key={preset}
                type="button"
                draggable={false}
                onMouseDown={(e) => e.preventDefault()}
                onClick={() => setSize(preset)}
                title={`${preset}%`}
                className={`flex h-6 min-w-6 items-center justify-center rounded px-1 text-[11px] font-semibold ${
                  width === preset
                    ? "bg-[var(--color-primary-light)] text-[var(--color-primary)]"
                    : "text-[var(--color-text-secondary)] hover:bg-gray-100"
                }`}
              >
                {SIZE_LABELS[preset]}
              </button>
            ))}

            <span className="mx-0.5 h-4 w-px bg-[var(--color-border)]" />

            <button
              type="button"
              draggable={false}
              onMouseDown={(e) => e.preventDefault()}
              onClick={() => setAlign("left")}
              className={`flex h-6 w-6 items-center justify-center rounded ${
                align === "left"
                  ? "bg-[var(--color-primary-light)] text-[var(--color-primary)]"
                  : "text-[var(--color-text-secondary)] hover:bg-gray-100"
              }`}
            >
              <AlignLeft className="h-3.5 w-3.5" />
            </button>
            <button
              type="button"
              draggable={false}
              onMouseDown={(e) => e.preventDefault()}
              onClick={() => setAlign("center")}
              className={`flex h-6 w-6 items-center justify-center rounded ${
                align === "center"
                  ? "bg-[var(--color-primary-light)] text-[var(--color-primary)]"
                  : "text-[var(--color-text-secondary)] hover:bg-gray-100"
              }`}
            >
              <AlignCenter className="h-3.5 w-3.5" />
            </button>
            <button
              type="button"
              draggable={false}
              onMouseDown={(e) => e.preventDefault()}
              onClick={() => setAlign("right")}
              className={`flex h-6 w-6 items-center justify-center rounded ${
                align === "right"
                  ? "bg-[var(--color-primary-light)] text-[var(--color-primary)]"
                  : "text-[var(--color-text-secondary)] hover:bg-gray-100"
              }`}
            >
              <AlignRight className="h-3.5 w-3.5" />
            </button>
            <span className="mx-0.5 h-4 w-px bg-[var(--color-border)]" />
            <button
              type="button"
              draggable={false}
              onMouseDown={(e) => e.preventDefault()}
              onClick={() => deleteNode()}
              className="flex h-6 w-6 items-center justify-center rounded text-red-500 hover:bg-red-50"
            >
              <Trash2 className="h-3.5 w-3.5" />
            </button>
          </div>
        )}
      </div>
    </NodeViewWrapper>
  );
}
