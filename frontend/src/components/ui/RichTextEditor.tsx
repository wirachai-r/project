import { useEditor, EditorContent } from "@tiptap/react";
import StarterKit from "@tiptap/starter-kit";
import Placeholder from "@tiptap/extension-placeholder";
import Underline from "@tiptap/extension-underline";
import Link from "@tiptap/extension-link";
import TextAlign from "@tiptap/extension-text-align";
import { useCallback, useEffect, useRef, useState } from "react";
import { toast } from "sonner";
import {
  Bold,
  Italic,
  UnderlineIcon,
  Strikethrough,
  List,
  ListOrdered,
  Minus,
  AlignLeft,
  AlignCenter,
  AlignRight,
  AlignJustify,
  LinkIcon,
  FileSearch,
  Search,
  Loader2,
  ImagePlus,
  Undo,
  Redo,
} from "lucide-react";
import { uploadApi, type UploadFolder } from "@/lib/api/upload";
import { diseaseApi } from "@/lib/api/disease";
import { articleApi } from "@/lib/api/article";
import { firstAidApi } from "@/lib/api/firstaid";
import { ResizableImage } from "./rich-text/ResizableImage";
import { ImageCropModal, type CropAspect } from "./ImageCropModal";

interface RichTextEditorProps {
  value: string;
  onChange: (html: string) => void;
  placeholder?: string;
  folder?: UploadFolder;
}

// สัดส่วนที่เหมาะกับรูปในเนื้อหาบทความ (มี "อิสระ" ให้ครอบตัดตามใจ)
const CONTENT_IMAGE_ASPECTS: CropAspect[] = [
  { label: "1:1", value: 1 },
  { label: "4:3", value: 4 / 3 },
  { label: "16:9", value: 16 / 9 },
];

// ประเภทเนื้อหาในระบบที่ลิงก์เข้าถึงได้ + prefix ของ href ที่แทรกลงใน editor
// (ฝั่ง Flutter/เว็บที่แสดงผลเนื้อหานี้ ต้อง intercept ลิงก์ที่ขึ้นต้นด้วย prefix เหล่านี้เอง
//  แล้ว navigate ไปหน้ารายละเอียดที่ตรงกัน — ปรับ prefix ให้ตรงกับ routing จริงของโปรเจกต์ได้)
const INTERNAL_LINK_TYPES = [
  { key: "disease", label: "โรค", hrefPrefix: "disease" },
  { key: "article", label: "บทความ", hrefPrefix: "article" },
  { key: "first_aid", label: "ปฐมพยาบาล", hrefPrefix: "first_aid" },
] as const;

type InternalLinkType = (typeof INTERNAL_LINK_TYPES)[number]["key"];

interface InternalLinkItem {
  id: string;
  label: string;
}

export function RichTextEditor({
  value,
  onChange,
  placeholder = "เริ่มพิมพ์เนื้อหา...",
  folder = "diseases",
}: RichTextEditorProps) {
  const fileInputRef = useRef<HTMLInputElement>(null);

  // คิวไฟล์ที่รอครอบตัด + ไฟล์ที่กำลังครอบตัดอยู่ตอนนี้
  const queueRef = useRef<File[]>([]);
  const [currentFile, setCurrentFile] = useState<File | null>(null);
  const [cropOpen, setCropOpen] = useState(false);

  // panel เลือกลิงก์เนื้อหาในระบบ (โรค / บทความ / ปฐมพยาบาล)
  const [internalLinkOpen, setInternalLinkOpen] = useState(false);
  const [internalLinkType, setInternalLinkType] =
    useState<InternalLinkType>("disease");
  const [internalLinkSearch, setInternalLinkSearch] = useState("");
  const [internalLinkResults, setInternalLinkResults] = useState<
    InternalLinkItem[]
  >([]);
  const [internalLinkLoading, setInternalLinkLoading] = useState(false);
  const internalLinkPanelRef = useRef<HTMLDivElement>(null);

  const editor = useEditor({
    extensions: [
      StarterKit.configure({
        // ใส่ class ให้ list ตรงๆ เพราะ Tailwind preflight เซ็ต list-style: none ให้ ul/ol
        // ไว้ default ถ้าไม่ใส่ class พวกนี้ บูลเลต/เลขจะไม่ขึ้นให้เห็นเลย
        bulletList: { HTMLAttributes: { class: "list-disc pl-5" } },
        orderedList: { HTMLAttributes: { class: "list-decimal pl-5" } },
      }),
      Underline,
      Link.configure({
        openOnClick: false,
        autolink: true,
        HTMLAttributes: { class: "text-[var(--color-primary)] underline" },
      }),
      TextAlign.configure({
        types: ["heading", "paragraph"],
      }),
      ResizableImage.configure({
        HTMLAttributes: { class: "rounded-lg max-w-full" },
      }),
      Placeholder.configure({ placeholder }),
    ],
    content: value,
    onUpdate: ({ editor }) => onChange(editor.getHTML()),
    editorProps: {
      attributes: {
        class:
          "prose prose-sm max-w-none min-h-[160px] px-4 py-3 focus:outline-none",
      },
    },
  });

  const handleImagePick = useCallback(() => {
    fileInputRef.current?.click();
  }, []);

  // เลือกไฟล์เสร็จ -> เก็บเป็นคิว แล้วเริ่มครอบตัดไฟล์แรก
  const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const files = e.target.files;
    if (!files || files.length === 0) return;

    const list = Array.from(files);
    const [first, ...rest] = list;
    queueRef.current = rest;
    setCurrentFile(first);
    setCropOpen(true);
    e.target.value = "";
  };

  // ครอบตัดเสร็จ 1 รูป -> อัปโหลด -> แทรกเข้า editor -> ไปรูปถัดไปในคิว (ถ้ามี)
  const handleCropConfirm = async (blob: Blob) => {
    if (!editor) return;
    setCropOpen(false);

    const loadingToast = toast.loading("กำลังอัปโหลดรูปภาพ...");
    try {
      const fileToUpload = new File(
        [blob],
        (currentFile?.name ?? "image").replace(/\.\w+$/, "") + ".webp",
        { type: "image/webp" },
      );
      const { url } = await uploadApi.uploadImage(fileToUpload, folder);
      editor.chain().focus().setImage({ src: url }).run();
      toast.success("แทรกรูปภาพสำเร็จ", { id: loadingToast });
    } catch {
      toast.error("อัปโหลดรูปภาพไม่สำเร็จ", { id: loadingToast });
    } finally {
      goToNextInQueue();
    }
  };

  const handleCropCancel = () => {
    setCropOpen(false);
    goToNextInQueue();
  };

  const goToNextInQueue = () => {
    const [next, ...rest] = queueRef.current;
    if (!next) {
      setCurrentFile(null);
      return;
    }
    queueRef.current = rest;
    setCurrentFile(next);
    setCropOpen(true);
  };

  const handleSetLink = useCallback(() => {
    if (!editor) return;
    const previousUrl = editor.getAttributes("link").href as string | undefined;
    const url = window.prompt("ใส่ลิงก์ (เว้นว่างเพื่อลบลิงก์)", previousUrl ?? "");

    if (url === null) return; // ยกเลิก

    if (url === "") {
      editor.chain().focus().extendMarkRange("link").unsetLink().run();
      return;
    }

    editor.chain().focus().extendMarkRange("link").setLink({ href: url }).run();
  }, [editor]);

  // ค้นหารายการเนื้อหาในระบบ ทุกครั้งที่ panel เปิดอยู่ / เปลี่ยนประเภท / เปลี่ยนคำค้นหา
  useEffect(() => {
    if (!internalLinkOpen) return;

    const timer = setTimeout(async () => {
      setInternalLinkLoading(true);
      try {
        if (internalLinkType === "disease") {
          const res = await diseaseApi.list({
            search: internalLinkSearch || undefined,
            per_page: 10,
          });
          setInternalLinkResults(
            res.data.map((d) => ({ id: d.disease_id, label: d.disease_name })),
          );
        } else if (internalLinkType === "article") {
          const res = await articleApi.list({
            search: internalLinkSearch || undefined,
            per_page: 10,
          });
          setInternalLinkResults(
            res.data.map((a) => ({ id: a.article_id, label: a.title })),
          );
        } else {
          const res = await firstAidApi.list({
            search: internalLinkSearch || undefined,
            per_page: 10,
          });
          setInternalLinkResults(
            res.data.map((f) => ({ id: f.first_aid_id, label: f.title })),
          );
        }
      } catch {
        setInternalLinkResults([]);
      } finally {
        setInternalLinkLoading(false);
      }
    }, 300);

    return () => clearTimeout(timer);
  }, [internalLinkOpen, internalLinkType, internalLinkSearch]);

  // ปิด panel เมื่อคลิกข้างนอก
  useEffect(() => {
    if (!internalLinkOpen) return;

    const handleClickOutside = (e: MouseEvent) => {
      if (
        internalLinkPanelRef.current &&
        !internalLinkPanelRef.current.contains(e.target as Node)
      ) {
        setInternalLinkOpen(false);
      }
    };

    document.addEventListener("mousedown", handleClickOutside);
    return () => document.removeEventListener("mousedown", handleClickOutside);
  }, [internalLinkOpen]);

  const handleToggleInternalLink = () => {
    setInternalLinkOpen((v) => !v);
    setInternalLinkSearch("");
  };

  const handleInsertInternalLink = (item: InternalLinkItem) => {
    if (!editor) return;

    const typeConfig = INTERNAL_LINK_TYPES.find(
      (t) => t.key === internalLinkType,
    )!;
    const href = `${typeConfig.hrefPrefix}:${item.id}`;

    const { from, to } = editor.state.selection;
    const hasSelection = from !== to;

    if (hasSelection) {
      editor.chain().focus().extendMarkRange("link").setLink({ href }).run();
    } else {
      // แทรกเป็น text node พร้อม mark link เลย ไม่ผ่าน raw HTML string เพื่อกัน injection
      editor
        .chain()
        .focus()
        .insertContent({
          type: "text",
          text: item.label,
          marks: [{ type: "link", attrs: { href } }],
        })
        .run();
    }

    setInternalLinkOpen(false);
    setInternalLinkSearch("");
  };

  if (!editor) return null;

  const ToolbarButton = ({
    onClick,
    active,
    disabled,
    title,
    children,
  }: {
    onClick: () => void;
    active?: boolean;
    disabled?: boolean;
    title?: string;
    children: React.ReactNode;
  }) => (
    <button
      type="button"
      title={title}
      onClick={onClick}
      disabled={disabled}
      className={`flex h-8 w-8 items-center justify-center rounded-md transition-colors disabled:cursor-not-allowed disabled:opacity-40 ${
        active
          ? "bg-[var(--color-primary-light)] text-[var(--color-primary)]"
          : "text-[var(--color-text-secondary)] hover:bg-gray-100"
      }`}
    >
      {children}
    </button>
  );

  const Divider = () => <div className="mx-1 h-5 w-px bg-[var(--color-border)]" />;

  return (
    <div className="overflow-hidden rounded-xl border border-[var(--color-border)]">
      <div className="flex flex-wrap items-center gap-1 border-b border-[var(--color-border)] bg-gray-50 px-2 py-1.5">
        <ToolbarButton
          title="ย้อนกลับ"
          onClick={() => editor.chain().focus().undo().run()}
          disabled={!editor.can().undo()}
        >
          <Undo className="h-4 w-4" />
        </ToolbarButton>
        <ToolbarButton
          title="ทำซ้ำ"
          onClick={() => editor.chain().focus().redo().run()}
          disabled={!editor.can().redo()}
        >
          <Redo className="h-4 w-4" />
        </ToolbarButton>

        <Divider />

        <ToolbarButton
          title="ตัวหนา"
          onClick={() => editor.chain().focus().toggleBold().run()}
          active={editor.isActive("bold")}
        >
          <Bold className="h-4 w-4" />
        </ToolbarButton>
        <ToolbarButton
          title="ตัวเอียง"
          onClick={() => editor.chain().focus().toggleItalic().run()}
          active={editor.isActive("italic")}
        >
          <Italic className="h-4 w-4" />
        </ToolbarButton>
        <ToolbarButton
          title="ขีดเส้นใต้"
          onClick={() => editor.chain().focus().toggleUnderline().run()}
          active={editor.isActive("underline")}
        >
          <UnderlineIcon className="h-4 w-4" />
        </ToolbarButton>
        <ToolbarButton
          title="ขีดฆ่า"
          onClick={() => editor.chain().focus().toggleStrike().run()}
          active={editor.isActive("strike")}
        >
          <Strikethrough className="h-4 w-4" />
        </ToolbarButton>

        <Divider />

        <ToolbarButton
          title="ชิดซ้าย"
          onClick={() => editor.chain().focus().setTextAlign("left").run()}
          active={editor.isActive({ textAlign: "left" })}
        >
          <AlignLeft className="h-4 w-4" />
        </ToolbarButton>
        <ToolbarButton
          title="กึ่งกลาง"
          onClick={() => editor.chain().focus().setTextAlign("center").run()}
          active={editor.isActive({ textAlign: "center" })}
        >
          <AlignCenter className="h-4 w-4" />
        </ToolbarButton>
        <ToolbarButton
          title="ชิดขวา"
          onClick={() => editor.chain().focus().setTextAlign("right").run()}
          active={editor.isActive({ textAlign: "right" })}
        >
          <AlignRight className="h-4 w-4" />
        </ToolbarButton>
        <ToolbarButton
          title="เต็มบรรทัด"
          onClick={() => editor.chain().focus().setTextAlign("justify").run()}
          active={editor.isActive({ textAlign: "justify" })}
        >
          <AlignJustify className="h-4 w-4" />
        </ToolbarButton>

        <Divider />

        <ToolbarButton
          title="รายการหัวข้อย่อย"
          onClick={() => editor.chain().focus().toggleBulletList().run()}
          active={editor.isActive("bulletList")}
        >
          <List className="h-4 w-4" />
        </ToolbarButton>
        <ToolbarButton
          title="รายการลำดับเลข"
          onClick={() => editor.chain().focus().toggleOrderedList().run()}
          active={editor.isActive("orderedList")}
        >
          <ListOrdered className="h-4 w-4" />
        </ToolbarButton>
        <ToolbarButton
          title="เส้นคั่น"
          onClick={() => editor.chain().focus().setHorizontalRule().run()}
        >
          <Minus className="h-4 w-4" />
        </ToolbarButton>

        <Divider />

        <ToolbarButton
          title="ใส่ลิงก์"
          onClick={handleSetLink}
          active={editor.isActive("link")}
        >
          <LinkIcon className="h-4 w-4" />
        </ToolbarButton>

        <div className="relative">
          <ToolbarButton
            title="ลิงก์เนื้อหาในระบบ (โรค / บทความ / ปฐมพยาบาล)"
            onClick={handleToggleInternalLink}
            active={internalLinkOpen}
          >
            <FileSearch className="h-4 w-4" />
          </ToolbarButton>

          {internalLinkOpen && (
            <div
              ref={internalLinkPanelRef}
              className="absolute left-0 top-full z-20 mt-1 w-72 rounded-lg border border-[var(--color-border)] bg-white p-2 shadow-lg"
            >
              <div className="mb-2 flex gap-1">
                {INTERNAL_LINK_TYPES.map((t) => (
                  <button
                    key={t.key}
                    type="button"
                    onClick={() => {
                      setInternalLinkType(t.key);
                      setInternalLinkSearch("");
                    }}
                    className={`flex-1 rounded-md px-2 py-1 text-xs font-medium transition-colors ${
                      internalLinkType === t.key
                        ? "bg-[var(--color-primary-light)] text-[var(--color-primary)]"
                        : "text-[var(--color-text-secondary)] hover:bg-gray-100"
                    }`}
                  >
                    {t.label}
                  </button>
                ))}
              </div>

              <div className="relative mb-2">
                <Search className="pointer-events-none absolute left-2 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-[var(--color-text-secondary)]" />
                <input
                  autoFocus
                  value={internalLinkSearch}
                  onChange={(e) => setInternalLinkSearch(e.target.value)}
                  placeholder="ค้นหาชื่อ..."
                  className="w-full rounded-md border border-[var(--color-border)] py-1.5 pl-7 pr-2 text-xs focus:border-[var(--color-primary)] focus:outline-none"
                />
              </div>

              <div className="max-h-48 overflow-y-auto">
                {internalLinkLoading ? (
                  <div className="flex items-center justify-center py-4 text-[var(--color-text-secondary)]">
                    <Loader2 className="h-4 w-4 animate-spin" />
                  </div>
                ) : internalLinkResults.length === 0 ? (
                  <p className="py-4 text-center text-xs text-[var(--color-text-secondary)]">
                    ไม่พบข้อมูล
                  </p>
                ) : (
                  internalLinkResults.map((item) => (
                    <button
                      key={item.id}
                      type="button"
                      onClick={() => handleInsertInternalLink(item)}
                      className="block w-full truncate rounded-md px-2 py-1.5 text-left text-xs text-[var(--color-text-primary)] hover:bg-[var(--color-primary-light)] hover:text-[var(--color-primary)]"
                    >
                      {item.label}
                    </button>
                  ))
                )}
              </div>
            </div>
          )}
        </div>

        <ToolbarButton title="แทรกรูปภาพ (เลือกได้หลายรูป)" onClick={handleImagePick}>
          <ImagePlus className="h-4 w-4" />
        </ToolbarButton>
      </div>

      <EditorContent editor={editor} />

      <input
        ref={fileInputRef}
        type="file"
        accept="image/png,image/jpeg,image/webp"
        multiple
        onChange={handleFileChange}
        className="hidden"
      />

      <ImageCropModal
        open={cropOpen}
        file={currentFile}
        aspects={CONTENT_IMAGE_ASPECTS}
        defaultAspectIndex={0}
        outputWidth={900}
        onCancel={handleCropCancel}
        onConfirm={handleCropConfirm}
      />
    </div>
  );
}