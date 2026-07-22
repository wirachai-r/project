// ResizableImage.tsx
import Image from "@tiptap/extension-image";
import { ReactNodeViewRenderer } from "@tiptap/react";
import { ResizableImageView } from "./ResizableImageView";

export type ImageAlign = "left" | "center" | "right" | "none";

// ขนาดคงที่ที่อนุญาต (% ของความกว้าง container) — ใช้ค่าเดียวกันได้ทั้ง React admin และ Flutter
export const IMAGE_SIZE_PRESETS = [25, 50, 75, 100] as const;
export type ImageSizePreset = (typeof IMAGE_SIZE_PRESETS)[number];
export const DEFAULT_IMAGE_SIZE: ImageSizePreset = 100;

export const ResizableImage = Image.extend({
  inline: false,
  group: "block",
  draggable: true,

  addAttributes() {
    return {
      ...this.parent?.(),
      align: {
        default: "center",
        parseHTML: (el) =>
          (el.getAttribute("data-align") as ImageAlign) || "center",
        renderHTML: (attrs) => ({ "data-align": attrs.align || "center" }),
      },
      // width เก็บเป็น % (25/50/75/100) ไม่ใช่ px แล้ว เพื่อให้ responsive ข้ามอุปกรณ์
      width: {
        default: DEFAULT_IMAGE_SIZE,
        parseHTML: (el) => {
          const styleWidth = el.style.width;
          if (styleWidth && styleWidth.includes("%")) {
            const parsed = parseInt(styleWidth, 10);
            return Number.isNaN(parsed) ? DEFAULT_IMAGE_SIZE : parsed;
          }
          const attrWidth = el.getAttribute("data-width-percent");
          return attrWidth ? parseInt(attrWidth, 10) : DEFAULT_IMAGE_SIZE;
        },
        renderHTML: (attrs) => {
          const percent = attrs.width || DEFAULT_IMAGE_SIZE;
          return {
            "data-width-percent": percent,
            style: `width: ${percent}%`,
          };
        },
      },
    };
  },

  addNodeView() {
    return ReactNodeViewRenderer(ResizableImageView);
  },
});