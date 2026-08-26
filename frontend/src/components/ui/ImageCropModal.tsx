import { useState, useRef, useEffect, useCallback } from "react";
import { Dialog, DialogContent, DialogHeader, DialogTitle } from "./Dialog";
import { Button } from "./Button";

export type CropAspect = {
  label: string;
  value: number; // width / height
};

const DEFAULT_ASPECTS: CropAspect[] = [
  { label: "1:1", value: 1 },
  { label: "4:3", value: 4 / 3 },
  { label: "16:9", value: 16 / 9 },
];

interface ImageCropModalProps {
  open: boolean;
  file: File | null;
  aspects?: CropAspect[];
  defaultAspectIndex?: number;
  outputWidth?: number;
  outputType?: "image/png" | "image/jpeg" | "image/webp";
  onCancel: () => void;
  onConfirm: (blob: Blob) => void;
}

const MIN_BOX_SIZE = 40;
// สัดส่วนพื้นที่แสดงรูป (viewport ของตัวครอบตัด) ไม่ใช่สัดส่วนผลลัพธ์ที่ครอบตัด
const VIEWPORT_RATIO = 4 / 3;

export function ImageCropModal({
  open,
  file,
  aspects = DEFAULT_ASPECTS,
  defaultAspectIndex = 0,
  outputWidth = 1200,
  outputType = "image/webp",
  onCancel,
  onConfirm,
}: ImageCropModalProps) {
  return (
    <Dialog open={open} onOpenChange={(o) => !o && onCancel()}>
      {/* มือถือ: เต็มความสูงจอแบบ IconPicker / md ขึ้นไป: auto height + จำกัด max-width */}
      <DialogContent className="flex h-full flex-col md:h-auto md:max-w-xl lg:max-w-2xl">
        <DialogHeader className="shrink-0">
          <DialogTitle>ครอบตัดรูปภาพ</DialogTitle>
        </DialogHeader>

        {file && (
          <CropperBody
            key={`${file.name}-${file.lastModified}-${file.size}`}
            file={file}
            aspects={aspects}
            defaultAspectIndex={defaultAspectIndex}
            outputWidth={outputWidth}
            outputType={outputType}
            onCancel={onCancel}
            onConfirm={onConfirm}
          />
        )}
      </DialogContent>
    </Dialog>
  );
}

interface Box {
  x: number;
  y: number;
  width: number;
  height: number;
}

type Corner = "tl" | "tr" | "bl" | "br";

// dirX/dirY = +1 ถ้าลากมุมนั้นแล้วขยับออกทางบวก(ขวา/ล่าง)ทำให้กล่องใหญ่ขึ้น, -1 ถ้าตรงข้าม
// ใช้กำหนดว่า anchor (มุมตรงข้ามที่ fix อยู่กับที่) อยู่ตรงไหน และ dx ควรตีความเป็นขยาย/หดยังไง
const CORNER_CONFIG: Record<Corner, { dirX: 1 | -1; dirY: 1 | -1; cursor: string }> = {
  br: { dirX: 1, dirY: 1, cursor: "cursor-nwse-resize" },
  tl: { dirX: -1, dirY: -1, cursor: "cursor-nwse-resize" },
  tr: { dirX: 1, dirY: -1, cursor: "cursor-nesw-resize" },
  bl: { dirX: -1, dirY: 1, cursor: "cursor-nesw-resize" },
};

interface CropperBodyProps {
  file: File;
  aspects: CropAspect[];
  defaultAspectIndex: number;
  outputWidth: number;
  outputType: "image/png" | "image/jpeg" | "image/webp";
  onCancel: () => void;
  onConfirm: (blob: Blob) => void;
}

function CropperBody({
  file,
  aspects,
  defaultAspectIndex,
  outputWidth,
  outputType,
  onCancel,
  onConfirm,
}: CropperBodyProps) {
  const [imageUrl, setImageUrl] = useState<string | null>(null);
  const [naturalSize, setNaturalSize] = useState({ width: 0, height: 0 });
  const [aspectIndex, setAspectIndex] = useState(defaultAspectIndex);
  const [box, setBox] = useState<Box | null>(null);
  const [processing, setProcessing] = useState(false);
  const [dragging, setDragging] = useState(false);

  // ความกว้างจริงของพื้นที่ครอบตัด วัดจาก wrapper ref แทนค่าคงที่
  // ทำให้ responsive ตามขนาด dialog/หน้าจอจริง (มือถือก็พอดีจอ ไม่ล้น)
  const wrapperRef = useRef<HTMLDivElement>(null);
  const [containerW, setContainerW] = useState(0);

  useEffect(() => {
    const el = wrapperRef.current;
    if (!el) return;
    const observer = new ResizeObserver((entries) => {
      const width = entries[0]?.contentRect.width;
      if (width) setContainerW(width);
    });
    observer.observe(el);
    return () => observer.disconnect();
  }, []);

  const containerH = containerW / VIEWPORT_RATIO;

  const imgRef = useRef<HTMLImageElement>(null);
  const dragRef = useRef<{
    mode: "move" | "resize";
    corner?: Corner;
    startX: number;
    startY: number;
    origBox: Box;
  } | null>(null);

  useEffect(() => {
    let cancelled = false;
    const reader = new FileReader();
    reader.onload = () => {
      if (!cancelled && typeof reader.result === "string") {
        setImageUrl(reader.result);
      }
    };
    reader.readAsDataURL(file);
    return () => {
      cancelled = true;
    };
  }, [file]);

  const aspect = aspects[aspectIndex].value;

  const displayScale =
    naturalSize.width && containerW
      ? Math.min(
          containerW / naturalSize.width,
          containerH / naturalSize.height,
        )
      : 1;
  const displayW = naturalSize.width * displayScale;
  const displayH = naturalSize.height * displayScale;
  const offsetX = (containerW - displayW) / 2;
  const offsetY = (containerH - displayH) / 2;

  const resetBoxForAspect = useCallback(
    (a: number) => {
      if (!displayW || !displayH) return;
      let w = displayW;
      let h = w / a;
      if (h > displayH) {
        h = displayH;
        w = h * a;
      }
      setBox({
        x: offsetX + (displayW - w) / 2,
        y: offsetY + (displayH - h) / 2,
        width: w,
        height: h,
      });
    },
    [displayW, displayH, offsetX, offsetY],
  );

  const handleImgLoad = () => {
    if (!imgRef.current) return;
    setNaturalSize({
      width: imgRef.current.naturalWidth,
      height: imgRef.current.naturalHeight,
    });
  };

  // ตั้งกรอบเริ่มต้นใหม่เมื่อรู้ natural size, เปลี่ยนสัดส่วน, หรือ container ถูก resize
  useEffect(() => {
    if (naturalSize.width && containerW) resetBoxForAspect(aspect);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [naturalSize.width, naturalSize.height, aspectIndex, containerW]);

  const clampBox = (b: Box): Box => {
    const width = Math.min(b.width, displayW);
    const height = Math.min(b.height, displayH);
    const x = Math.min(Math.max(b.x, offsetX), offsetX + displayW - width);
    const y = Math.min(Math.max(b.y, offsetY), offsetY + displayH - height);
    return { x, y, width, height };
  };

  const handleBoxPointerDown = (e: React.PointerEvent) => {
    if (!box) return;
    (e.target as HTMLElement).setPointerCapture(e.pointerId);
    setDragging(true);
    dragRef.current = {
      mode: "move",
      startX: e.clientX,
      startY: e.clientY,
      origBox: box,
    };
  };

  const handleHandlePointerDown = (corner: Corner) => (e: React.PointerEvent) => {
    if (!box) return;
    e.stopPropagation();
    (e.target as HTMLElement).setPointerCapture(e.pointerId);
    setDragging(true);
    dragRef.current = {
      mode: "resize",
      corner,
      startX: e.clientX,
      startY: e.clientY,
      origBox: box,
    };
  };

  const handlePointerMove = (e: React.PointerEvent) => {
    if (!dragRef.current || !box) return;
    const dx = e.clientX - dragRef.current.startX;
    const orig = dragRef.current.origBox;

    if (dragRef.current.mode === "move") {
      const dy = e.clientY - dragRef.current.startY;
      setBox(
        clampBox({
          ...orig,
          x: orig.x + dx,
          y: orig.y + dy,
        }),
      );
    } else {
      const corner = dragRef.current.corner!;
      const { dirX, dirY } = CORNER_CONFIG[corner];

      // anchor คือมุมตรงข้ามที่ fix อยู่กับที่ระหว่างการ resize
      const anchorX = dirX === 1 ? orig.x : orig.x + orig.width;
      const anchorY = dirY === 1 ? orig.y : orig.y + orig.height;

      // ขอบเขตสูงสุดที่ขยายได้จากขอบ container ทั้งด้านกว้าง(x)และสูง(y, แปลงผ่าน aspect)
      const maxWidthByX =
        dirX === 1 ? offsetX + displayW - anchorX : anchorX - offsetX;
      const maxHeightByY =
        dirY === 1 ? offsetY + displayH - anchorY : anchorY - offsetY;
      const maxWidthByY = maxHeightByY * aspect;

      let newWidth = orig.width + dx * dirX;
      newWidth = Math.max(
        MIN_BOX_SIZE,
        Math.min(newWidth, maxWidthByX, maxWidthByY),
      );
      const newHeight = newWidth / aspect;

      const newX = dirX === 1 ? anchorX : anchorX - newWidth;
      const newY = dirY === 1 ? anchorY : anchorY - newHeight;

      setBox({ x: newX, y: newY, width: newWidth, height: newHeight });
    }
  };

  const handlePointerUp = () => {
    dragRef.current = null;
    setDragging(false);
  };

  const handleAspectChange = (index: number) => {
    setAspectIndex(index);
  };

  const handleConfirm = async () => {
    if (!imgRef.current || !box || !naturalSize.width) return;
    setProcessing(true);
    try {
      const sourceX = (box.x - offsetX) / displayScale;
      const sourceY = (box.y - offsetY) / displayScale;
      const sourceW = box.width / displayScale;
      const sourceH = box.height / displayScale;

      const outputHeight = Math.round(outputWidth / aspect);
      const canvas = document.createElement("canvas");
      canvas.width = outputWidth;
      canvas.height = outputHeight;
      const ctx = canvas.getContext("2d");
      if (!ctx) {
        setProcessing(false);
        return;
      }

      ctx.drawImage(
        imgRef.current,
        sourceX,
        sourceY,
        sourceW,
        sourceH,
        0,
        0,
        outputWidth,
        outputHeight,
      );

      canvas.toBlob(
        (blob) => {
          setProcessing(false);
          if (blob) onConfirm(blob);
        },
        outputType,
        outputType === "image/png" ? undefined : 0.9,
      );
    } catch {
      setProcessing(false);
    }
  };

  const handlePositionClass: Record<Corner, string> = {
    tl: "-left-1.5 -top-1.5",
    tr: "-right-1.5 -top-1.5",
    bl: "-left-1.5 -bottom-1.5",
    br: "-right-1.5 -bottom-1.5",
  };

  return (
    <div className="flex min-h-0 flex-1 flex-col">
      {/* Aspect buttons — sticky ด้านบน ไม่เลื่อนตามพื้นที่ครอบตัด */}
      {aspects.length > 1 && (
        <div className="mb-4 flex shrink-0 gap-2">
          {aspects.map((a, i) => (
            <button
              key={a.label}
              type="button"
              onClick={() => handleAspectChange(i)}
              className={`rounded-lg border px-3 py-1.5 text-sm transition-colors ${
                i === aspectIndex
                  ? "border-[var(--color-primary)] bg-[var(--color-primary)] text-white"
                  : "border-[var(--color-border)] text-[var(--color-text-secondary)] hover:border-[var(--color-primary)]"
              }`}
            >
              {a.label}
            </button>
          ))}
        </div>
      )}

      {/* พื้นที่ครอบตัด — ยืดเต็มพื้นที่ที่เหลือ, scroll ในตัวเองถ้าจำเป็น */}
      <div className="min-h-0 flex-1 overflow-y-auto">
        <div
          ref={wrapperRef}
          className="relative w-full touch-none select-none overflow-hidden rounded-lg"
          style={{
            height: containerH || 300,
            backgroundColor: "var(--color-border, #e5e7eb)",
          }}
        >
          {imageUrl && containerW > 0 && (
            <img
              ref={imgRef}
              src={imageUrl}
              alt="crop-preview"
              onLoad={handleImgLoad}
              draggable={false}
              className="pointer-events-none absolute select-none"
              style={{
                left: offsetX,
                top: offsetY,
                width: displayW || undefined,
                height: displayH || undefined,
              }}
            />
          )}

          {box && (
            <div
              onPointerDown={handleBoxPointerDown}
              onPointerMove={handlePointerMove}
              onPointerUp={handlePointerUp}
              onPointerLeave={handlePointerUp}
              className={`absolute cursor-move border-2 border-white ${
                dragging
                  ? ""
                  : "transition-[left,top,width,height] duration-150 ease-out"
              }`}
              style={{
                left: box.x,
                top: box.y,
                width: box.width,
                height: box.height,
                boxShadow: "0 0 0 9999px rgba(17,24,39,0.55)",
              }}
            >
              {(Object.keys(CORNER_CONFIG) as Corner[]).map((corner) => (
                <div
                  key={corner}
                  onPointerDown={handleHandlePointerDown(corner)}
                  onPointerMove={handlePointerMove}
                  onPointerUp={handlePointerUp}
                  onPointerLeave={handlePointerUp}
                  className={`absolute h-4 w-4 rounded-full border-2 border-[var(--color-primary)] bg-white shadow-sm ${CORNER_CONFIG[corner].cursor} ${handlePositionClass[corner]}`}
                />
              ))}
            </div>
          )}
        </div>

        <p className="mt-3 text-center text-xs text-[var(--color-text-secondary)]">
          ลากกรอบเพื่อย้ายตำแหน่ง ลากจุดที่มุมเพื่อปรับขนาด
        </p>
      </div>

      {/* Footer ปุ่ม — shrink-0 ติดล่างเสมอเหมือน footer จำนวนไอคอนใน IconPicker */}
      <div className="mt-3 flex shrink-0 justify-end gap-2 border-t border-[var(--color-border)] pt-3">
        <button
          type="button"
          onClick={onCancel}
          disabled={processing}
          className="rounded-lg px-4 py-2 text-sm text-[var(--color-text-secondary)] hover:bg-black/5 disabled:opacity-50"
        >
          ยกเลิก
        </button>
        <Button onClick={handleConfirm} loading={processing}>
          ยืนยันการครอบตัด
        </Button>
      </div>
    </div>
  );
}
