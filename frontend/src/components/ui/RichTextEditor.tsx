import { useEditor, EditorContent, useEditorState } from "@tiptap/react";
import { getMarkRange } from "@tiptap/core";
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
  Check,
  Unlink,
} from "lucide-react";
import { uploadApi, type UploadFolder } from "@/lib/api/upload";
import { diseaseApi } from "@/lib/api/disease";
import { articleApi } from "@/lib/api/article";
import { firstAidApi } from "@/lib/api/firstaid";
import { ResizableImage } from "./rich-text/ResizableImage";
import {
  Tooltip,
  TooltipContent,
  TooltipTrigger,
} from "./Tooltip";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from "./Dialog";

interface RichTextEditorProps {
  value: string;
  onChange: (html: string) => void;
  placeholder?: string;
  folder?: UploadFolder;
}

// ประเภทเนื้อหาในระบบที่ลิงก์เข้าถึงได้ + prefix ของ href ที่แทรกลงใน editor
// (ฝั่ง Flutter/เว็บที่แสดงผลเนื้อหานี้ ต้อง intercept ลิงก์ที่ขึ้นต้นด้วย prefix เหล่านี้เอง
//  แล้ว navigate ไปหน้ารายละเอียดที่ตรงกัน — ปรับ prefix ให้ตรงกับ routing จริงของโปรเจกต์ได้)
const INTERNAL_LINK_TYPES = [
  { key: "disease", label: "โรค", hrefPrefix: "disease" },
  { key: "article", label: "บทความ", hrefPrefix: "article" },
  { key: "first_aid", label: "ปฐมพยาบาล", hrefPrefix: "first-aid" },
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

  // panel เลือกลิงก์เนื้อหาในระบบ (โรค / บทความ / ปฐมพยาบาล)
  const [internalLinkOpen, setInternalLinkOpen] = useState(false);
  const [internalLinkType, setInternalLinkType] =
    useState<InternalLinkType>("disease");
  const [internalLinkSearch, setInternalLinkSearch] = useState("");
  const [internalLinkResults, setInternalLinkResults] = useState<
    InternalLinkItem[]
  >([]);
  const [internalLinkLoading, setInternalLinkLoading] = useState(false);
  const [linkEditorOpen, setLinkEditorOpen] = useState(false);
  const [linkUrl, setLinkUrl] = useState("");
  const internalLinkTargetRef = useRef<{ from: number; to: number } | null>(
    null,
  );

  const editor = useEditor({
    extensions: [
      StarterKit.configure({
        // Link และ Underline ถูกตั้งค่าแยกด้านล่าง จึงปิดตัวที่มากับ StarterKit
        // เพื่อไม่ให้ Tiptap ลงทะเบียน extension ชื่อเดียวกันซ้ำ
        link: false,
        underline: false,
        // ใส่ class ให้ list ตรงๆ เพราะ Tailwind preflight เซ็ต list-style: none ให้ ul/ol
        // ไว้ default ถ้าไม่ใส่ class พวกนี้ บูลเลต/เลขจะไม่ขึ้นให้เห็นเลย
        bulletList: { HTMLAttributes: { class: "list-disc pl-5" } },
        orderedList: { HTMLAttributes: { class: "list-decimal pl-5" } },
      }),
      Underline,
      Link.configure({
        openOnClick: false,
        enableClickSelection: false,
        autolink: true,
        protocols: ["disease", "article", "first-aid"],
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
    onSelectionUpdate: ({ editor, transaction }) => {
      const { selection } = editor.state;

      // การลากคลุมข้อความมีไว้สำหรับจัดรูปแบบผ่าน toolbar โดยไม่เปิด modal
      if (!selection.empty) {
        setLinkEditorOpen(false);
        setInternalLinkOpen(false);
        return;
      }

      const linkMark = editor.state.schema.marks.link;
      const linkBefore = selection.$from.nodeBefore?.marks.some(
        (mark) => mark.type === linkMark,
      );
      const linkAfter = selection.$from.nodeAfter?.marks.some(
        (mark) => mark.type === linkMark,
      );

      // เมื่อลบข้อความลิงก์จนหมด ให้ยกเลิก stored mark เพื่อไม่ให้ข้อความที่พิมพ์ต่อเป็นลิงก์
      if (!linkBefore && !linkAfter) {
        if (editor.state.storedMarks?.some((mark) => mark.type === linkMark)) {
          editor.view.dispatch(editor.state.tr.removeStoredMark(linkMark));
        }
        setLinkEditorOpen(false);
        setInternalLinkOpen(false);
        return;
      }

      // Backspace, Delete และปุ่มลูกศรไม่ควรเปิด popup อัตโนมัติ
      if (!transaction.getMeta("pointer")) {
        setLinkEditorOpen(false);
        setInternalLinkOpen(false);
        return;
      }

      // Caret is immediately after the final character of a link.
      if (linkBefore && !linkAfter) {
        setLinkEditorOpen(false);
        setInternalLinkOpen(false);
        return;
      }

      if (!editor.isActive("link")) return;

      const href =
        (editor.getAttributes("link").href as string | undefined) ?? "";
      const internalType = INTERNAL_LINK_TYPES.find((type) =>
        href.startsWith(`${type.hrefPrefix}:`),
      );

      if (internalType) {
        internalLinkTargetRef.current =
          getMarkRange(selection.$from, linkMark, { href }) ?? null;
        setInternalLinkType(internalType.key);
        setLinkEditorOpen(false);
        setInternalLinkOpen(true);
      } else {
        setInternalLinkOpen(false);
        setLinkUrl(href);
        setLinkEditorOpen(true);
      }
    },
    editorProps: {
      attributes: {
        class:
          "prose prose-sm max-w-none min-h-[160px] px-4 py-3 focus:outline-none [&_hr]:my-2 [&_hr+p]:mt-2",
      },
      handleClick: (_view, _pos, event) => {
        const target = event.target as HTMLElement;
        const link = target.closest("a");
        if (!link) return false;

        event.preventDefault();
        return true;
      },
    },
  });

  // ข้อมูลของหน้าฟอร์มถูกโหลดภายหลัง จึงต้อง sync value กลับเข้า editor
  // โดยปิด emitUpdate เพื่อไม่ให้การโหลดข้อมูลถูกนับเป็นการแก้ไขของผู้ใช้
  useEffect(() => {
    if (!editor || value === editor.getHTML()) return;
    editor.commands.setContent(value || "", { emitUpdate: false });
  }, [editor, value]);

  const toolbarState = useEditorState({
    editor,
    selector: ({ editor: currentEditor }) => {
      if (!currentEditor) {
        return {
          bold: false,
          italic: false,
          underline: false,
          strike: false,
          alignLeft: false,
          alignCenter: false,
          alignRight: false,
          alignJustify: false,
          bulletList: false,
          orderedList: false,
          linkHref: "",
        };
      }

      const { selection } = currentEditor.state;
      const linkMark = currentEditor.state.schema.marks.link;
      const linkAtSelection = selection.empty
        ? selection.$from.nodeAfter?.marks.find((mark) => mark.type === linkMark)
        : currentEditor.isActive("link")
          ? { attrs: currentEditor.getAttributes("link") }
          : undefined;

      return {
        bold: currentEditor.isActive("bold"),
        italic: currentEditor.isActive("italic"),
        underline: currentEditor.isActive("underline"),
        strike: currentEditor.isActive("strike"),
        alignLeft:
          !currentEditor.isActive({ textAlign: "center" }) &&
          !currentEditor.isActive({ textAlign: "right" }) &&
          !currentEditor.isActive({ textAlign: "justify" }),
        alignCenter: currentEditor.isActive({ textAlign: "center" }),
        alignRight: currentEditor.isActive({ textAlign: "right" }),
        alignJustify: currentEditor.isActive({ textAlign: "justify" }),
        bulletList: currentEditor.isActive("bulletList"),
        orderedList: currentEditor.isActive("orderedList"),
        linkHref: (linkAtSelection?.attrs.href as string | undefined) ?? "",
      };
    },
  });

  const handleImagePick = useCallback(() => {
    fileInputRef.current?.click();
  }, []);

  // เลือกไฟล์แล้วอัปโหลดและแทรกรูปต้นฉบับเข้า editor ทันที
  const handleFileChange = async (e: React.ChangeEvent<HTMLInputElement>) => {
    const files = e.target.files;
    if (!files || files.length === 0) return;

    const list = Array.from(files);
    e.target.value = "";

    const loadingToast = toast.loading(
      list.length > 1 ? `กำลังอัปโหลดรูปภาพ ${list.length} รูป...` : "กำลังอัปโหลดรูปภาพ...",
    );
    let uploadedCount = 0;

    for (const file of list) {
      try {
        const { url } = await uploadApi.uploadImage(file, folder);
        editor?.chain().focus().setImage({ src: url }).run();
        uploadedCount += 1;
      } catch {
        // อัปโหลดไฟล์ถัดไปต่อได้ แม้บางไฟล์จะไม่สำเร็จ
      }
    }

    if (uploadedCount === list.length) {
      toast.success(
        list.length > 1 ? `แทรกรูปภาพสำเร็จ ${uploadedCount} รูป` : "แทรกรูปภาพสำเร็จ",
        { id: loadingToast },
      );
    } else if (uploadedCount > 0) {
      toast.warning(`แทรกรูปภาพสำเร็จ ${uploadedCount} จาก ${list.length} รูป`, {
        id: loadingToast,
      });
    } else {
      toast.error("อัปโหลดรูปภาพไม่สำเร็จ", { id: loadingToast });
    }
  };

  const handleSetLink = useCallback(() => {
    if (!editor) return;

    setInternalLinkOpen(false);
    setLinkUrl((editor.getAttributes("link").href as string | undefined) ?? "");
    setLinkEditorOpen(true);
  }, [editor]);

  const handleApplyLink = useCallback(() => {
    if (!editor) return;
    const value = linkUrl.trim();
    if (!value) {
      editor.chain().focus().extendMarkRange("link").unsetLink().run();
      setLinkEditorOpen(false);
      return;
    }

    const href = /^(https?:\/\/|mailto:|tel:|#)/i.test(value)
      ? value
      : `https://${value}`;

    if (editor.state.selection.empty && !editor.isActive("link")) {
      const { $from } = editor.state.selection;
      const textBefore = $from.parent.textContent.slice(0, $from.parentOffset);
      const textAfter = $from.parent.textContent.slice($from.parentOffset);
      const needsSpaceBefore = textBefore.length > 0 && !/\s$/.test(textBefore);
      const needsSpaceAfter = textAfter.length > 0 && !/^\s/.test(textAfter);

      editor
        .chain()
        .focus()
        .insertContent([
          ...(needsSpaceBefore ? [{ type: "text", text: " " }] : []),
          {
            type: "text",
            text: href,
            marks: [{ type: "link", attrs: { href } }],
          },
          ...(needsSpaceAfter ? [{ type: "text", text: " " }] : []),
        ])
        .run();
    } else {
      editor.chain().focus().extendMarkRange("link").setLink({ href }).run();
    }
    setLinkUrl(href);
    setLinkEditorOpen(false);
  }, [editor, linkUrl]);

  const handleRemoveLink = useCallback(() => {
    if (!editor) return;
    editor.chain().focus().extendMarkRange("link").unsetLink().run();
    setLinkUrl("");
    setLinkEditorOpen(false);
  }, [editor]);

  // ค้นหารายการเนื้อหาในระบบ ทุกครั้งที่ panel เปิดอยู่ / เปลี่ยนประเภท / เปลี่ยนคำค้นหา
  useEffect(() => {
    if (!internalLinkOpen) return;

    let cancelled = false;
    setInternalLinkResults([]);
    setInternalLinkLoading(true);

    const timer = setTimeout(async () => {
      try {
        if (internalLinkType === "disease") {
          const res = await diseaseApi.list({
            search: internalLinkSearch || undefined,
            per_page: 10,
          });
          if (!cancelled) {
            setInternalLinkResults(
              res.data.map((d) => ({ id: d.disease_id, label: d.disease_name })),
            );
          }
        } else if (internalLinkType === "article") {
          const res = await articleApi.list({
            search: internalLinkSearch || undefined,
            per_page: 10,
          });
          if (!cancelled) {
            setInternalLinkResults(
              res.data.map((a) => ({ id: a.article_id, label: a.title })),
            );
          }
        } else {
          const res = await firstAidApi.list({
            search: internalLinkSearch || undefined,
            per_page: 10,
          });
          if (!cancelled) {
            setInternalLinkResults(
              res.data.map((f) => ({ id: f.first_aid_id, label: f.title })),
            );
          }
        }
      } catch {
        if (!cancelled) setInternalLinkResults([]);
      } finally {
        if (!cancelled) setInternalLinkLoading(false);
      }
    }, 300);

    return () => {
      cancelled = true;
      clearTimeout(timer);
    };
  }, [internalLinkOpen, internalLinkType, internalLinkSearch]);

  const handleToggleInternalLink = () => {
    if (!editor) return;
    const { selection } = editor.state;
    const href = (editor.getAttributes("link").href as string | undefined) ?? "";
    const internalType = INTERNAL_LINK_TYPES.find((type) =>
      href.startsWith(`${type.hrefPrefix}:`),
    );
    if (internalType) setInternalLinkType(internalType.key);
    internalLinkTargetRef.current = selection.empty
      ? (getMarkRange(selection.$from, editor.state.schema.marks.link) ?? null)
      : { from: selection.from, to: selection.to };
    setLinkEditorOpen(false);
    setInternalLinkOpen(true);
    setInternalLinkSearch("");
  };

  const handleInsertInternalLink = (item: InternalLinkItem) => {
    if (!editor) return;

    const typeConfig = INTERNAL_LINK_TYPES.find(
      (t) => t.key === internalLinkType,
    )!;
    const href = `${typeConfig.hrefPrefix}:${item.id}`;

    const target = internalLinkTargetRef.current;
    const { from, to } = target ?? editor.state.selection;
    const $from = editor.state.doc.resolve(from);
    const $to = editor.state.doc.resolve(to);
    const textBefore = $from.parent.textContent.slice(0, $from.parentOffset);
    const textAfter = $to.parent.textContent.slice($to.parentOffset);
    const needsSpaceBefore = textBefore.length > 0 && !/\s$/.test(textBefore);
    const needsSpaceAfter = textAfter.length > 0 && !/^\s/.test(textAfter);

    editor
      .chain()
      .focus()
      .insertContentAt(
        { from, to },
        [
          ...(needsSpaceBefore ? [{ type: "text", text: " " }] : []),
          {
            type: "text",
            text: item.label,
            marks: [{ type: "link", attrs: { href } }],
          },
          ...(needsSpaceAfter ? [{ type: "text", text: " " }] : []),
        ],
        { updateSelection: true },
      )
      .run();

    internalLinkTargetRef.current = null;
    setInternalLinkOpen(false);
    setInternalLinkSearch("");
  };

  if (!editor) return null;

  const selectedLinkHref = toolbarState.linkHref;
  const selectedInternalLink = INTERNAL_LINK_TYPES.find((type) =>
    selectedLinkHref.startsWith(`${type.hrefPrefix}:`),
  );
  const externalLinkSelected = Boolean(selectedLinkHref && !selectedInternalLink);
  const internalLinkSelected = Boolean(selectedInternalLink);

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
  }) => {
    const button = (
      <button
        type="button"
        aria-label={title}
        onMouseDown={(event) => event.preventDefault()}
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

    if (!title) return button;
    return (
      <Tooltip>
        <TooltipTrigger asChild>{button}</TooltipTrigger>
        <TooltipContent>{title}</TooltipContent>
      </Tooltip>
    );
  };

  const Divider = () => <div className="mx-1 h-5 w-px bg-[var(--color-border)]" />;

  return (
    <div className="rounded-xl border border-[var(--color-border)]">
      <div className="sticky -top-4 z-[9] flex flex-wrap items-center gap-1 rounded-t-xl border-b border-[var(--color-border)] bg-gray-50/95 px-2 py-1.5 shadow-sm backdrop-blur supports-[backdrop-filter]:bg-gray-50/90 lg:-top-6">
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
          active={toolbarState.bold}
        >
          <Bold className="h-4 w-4" />
        </ToolbarButton>
        <ToolbarButton
          title="ตัวเอียง"
          onClick={() => editor.chain().focus().toggleItalic().run()}
          active={toolbarState.italic}
        >
          <Italic className="h-4 w-4" />
        </ToolbarButton>
        <ToolbarButton
          title="ขีดเส้นใต้"
          onClick={() => editor.chain().focus().toggleUnderline().run()}
          active={toolbarState.underline}
        >
          <UnderlineIcon className="h-4 w-4" />
        </ToolbarButton>
        <ToolbarButton
          title="ขีดฆ่า"
          onClick={() => editor.chain().focus().toggleStrike().run()}
          active={toolbarState.strike}
        >
          <Strikethrough className="h-4 w-4" />
        </ToolbarButton>

        <Divider />

        <ToolbarButton
          title="ชิดซ้าย"
          onClick={() => editor.chain().focus().setTextAlign("left").run()}
          active={toolbarState.alignLeft}
        >
          <AlignLeft className="h-4 w-4" />
        </ToolbarButton>
        <ToolbarButton
          title="กึ่งกลาง"
          onClick={() => editor.chain().focus().setTextAlign("center").run()}
          active={toolbarState.alignCenter}
        >
          <AlignCenter className="h-4 w-4" />
        </ToolbarButton>
        <ToolbarButton
          title="ชิดขวา"
          onClick={() => editor.chain().focus().setTextAlign("right").run()}
          active={toolbarState.alignRight}
        >
          <AlignRight className="h-4 w-4" />
        </ToolbarButton>
        <ToolbarButton
          title="เต็มบรรทัด"
          onClick={() => editor.chain().focus().setTextAlign("justify").run()}
          active={toolbarState.alignJustify}
        >
          <AlignJustify className="h-4 w-4" />
        </ToolbarButton>

        <Divider />

        <ToolbarButton
          title="รายการหัวข้อย่อย"
          onClick={() => editor.chain().focus().toggleBulletList().run()}
          active={toolbarState.bulletList}
        >
          <List className="h-4 w-4" />
        </ToolbarButton>
        <ToolbarButton
          title="รายการลำดับเลข"
          onClick={() => editor.chain().focus().toggleOrderedList().run()}
          active={toolbarState.orderedList}
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
          title="ใส่ลิงก์เว็บไซต์"
          onClick={handleSetLink}
          active={linkEditorOpen || externalLinkSelected}
        >
          <LinkIcon className="h-4 w-4" />
        </ToolbarButton>

        <ToolbarButton
          title="เชื่อมโยงไปยังเนื้อหาในระบบ (โรค / บทความ / ปฐมพยาบาล)"
          onClick={handleToggleInternalLink}
          active={internalLinkOpen || internalLinkSelected}
        >
          <FileSearch className="h-4 w-4" />
        </ToolbarButton>

        <ToolbarButton title="แทรกรูปภาพ (เลือกได้หลายรูป)" onClick={handleImagePick}>
          <ImagePlus className="h-4 w-4" />
        </ToolbarButton>
      </div>

      <Dialog
        open={linkEditorOpen || internalLinkOpen}
        onOpenChange={(open) => {
          if (!open) {
            setLinkEditorOpen(false);
            setInternalLinkOpen(false);
            internalLinkTargetRef.current = null;
          }
        }}
      >
        <DialogContent maxWidth="xl">
          <DialogHeader>
            <DialogTitle>
              {linkEditorOpen ? "ใส่ลิงก์เว็บไซต์" : "เชื่อมโยงไปยังเนื้อหาในระบบ"}
            </DialogTitle>
            <DialogDescription>
              {linkEditorOpen
                ? "ระบุ URL สำหรับข้อความที่เลือก"
                : "เลือกโรค บทความ หรือเนื้อหาปฐมพยาบาลที่ต้องการเชื่อมโยง"}
            </DialogDescription>
          </DialogHeader>
        {linkEditorOpen ? (
          <div className="flex w-full items-center gap-1 rounded-xl border border-[var(--color-border)] bg-white p-1.5">
          <LinkIcon className="ml-1 h-4 w-4 shrink-0 text-[var(--color-text-secondary)]" />
          <input
            autoFocus
            value={linkUrl}
            onChange={(event) => setLinkUrl(event.target.value)}
            onKeyDown={(event) => {
              if (event.key === "Enter") {
                event.preventDefault();
                handleApplyLink();
              }
              if (event.key === "Escape") {
                event.preventDefault();
                setLinkEditorOpen(false);
              }
            }}
            placeholder="https://example.com"
            className="min-w-0 flex-1 rounded-lg border-0 bg-[var(--color-surface)] px-2.5 py-1.5 text-sm outline-none focus:ring-2 focus:ring-[var(--color-primary)]/30"
          />
          <Tooltip>
            <TooltipTrigger asChild>
              <button
                type="button"
                onClick={handleApplyLink}
                className="rounded-lg p-2 text-[var(--color-primary)] hover:bg-[var(--color-primary-light)]"
                aria-label="บันทึกลิงก์"
              >
                <Check className="h-4 w-4" />
              </button>
            </TooltipTrigger>
            <TooltipContent>บันทึกลิงก์ (Enter)</TooltipContent>
          </Tooltip>
          {editor.isActive("link") && (
            <Tooltip>
              <TooltipTrigger asChild>
                <button
                  type="button"
                  onClick={handleRemoveLink}
                  className="rounded-lg p-2 text-[var(--color-danger)] hover:bg-red-50"
                  aria-label="ลบลิงก์"
                >
                  <Unlink className="h-4 w-4" />
                </button>
              </TooltipTrigger>
              <TooltipContent>ลบลิงก์</TooltipContent>
            </Tooltip>
          )}
          </div>
        ) : null}
        {internalLinkOpen ? (
          <div
            className="w-full"
          >
            <div className="mb-2 flex gap-1">
              {INTERNAL_LINK_TYPES.map((type) => (
                <button
                  key={type.key}
                  type="button"
                  onClick={() => {
                    setInternalLinkType(type.key);
                    setInternalLinkSearch("");
                  }}
                  className={`flex-1 rounded-md px-2 py-1.5 text-xs font-medium transition-colors ${
                    internalLinkType === type.key
                      ? "bg-[var(--color-primary-light)] text-[var(--color-primary)]"
                      : "text-[var(--color-text-secondary)] hover:bg-gray-100"
                  }`}
                >
                  {type.label}
                </button>
              ))}
            </div>

            <div className="relative mb-2">
              <Search className="pointer-events-none absolute left-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-[var(--color-text-secondary)]" />
              <input
                autoFocus
                value={internalLinkSearch}
                onChange={(event) => setInternalLinkSearch(event.target.value)}
                onKeyDown={(event) => {
                  if (event.key === "Escape") {
                    setInternalLinkOpen(false);
                  }
                }}
                placeholder="ค้นหาชื่อเนื้อหา..."
                className="w-full rounded-lg border border-[var(--color-border)] py-2 pl-8 pr-2.5 text-sm outline-none focus:border-[var(--color-primary)]"
              />
            </div>

            <div className="max-h-52 overflow-y-auto">
              {internalLinkLoading ? (
                <div className="flex items-center justify-center py-5 text-[var(--color-text-secondary)]">
                  <Loader2 className="h-4 w-4 animate-spin" />
                </div>
              ) : internalLinkResults.length === 0 ? (
                <p className="py-5 text-center text-xs text-[var(--color-text-secondary)]">
                  ไม่พบข้อมูล
                </p>
              ) : (
                internalLinkResults.map((item) => (
                  <button
                    key={item.id}
                    type="button"
                    onClick={() => handleInsertInternalLink(item)}
                    className="block w-full truncate rounded-lg px-2.5 py-2 text-left text-sm text-[var(--color-text-primary)] hover:bg-[var(--color-primary-light)] hover:text-[var(--color-primary)]"
                  >
                    {item.label}
                  </button>
                ))
              )}
            </div>
          </div>
        ) : null}
        </DialogContent>
      </Dialog>

      <EditorContent editor={editor} />

      <input
        ref={fileInputRef}
        type="file"
        accept="image/png,image/jpeg,image/webp"
        multiple
        onChange={handleFileChange}
        className="hidden"
      />

    </div>
  );
}
