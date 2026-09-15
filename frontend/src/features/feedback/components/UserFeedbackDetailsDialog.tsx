import { useEffect, useState } from "react";
import { ExternalLink, ImageIcon } from "lucide-react";
import { Link } from "react-router-dom";
import { Badge } from "@/components/ui/Badge";
import { Button } from "@/components/ui/Button";
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from "@/components/ui/Dialog";
import { SimpleSelect } from "@/components/ui/SimpleSelect";
import { Textarea } from "@/components/ui/Textarea";
import { api } from "@/lib/api";
import { formatAdminDateTime } from "@/lib/formatDate";
import { encodeId } from "@/lib/idCodec";
import { FEEDBACK_TYPE_LABELS, type UserFeedback } from "@/types/userFeedback";

export type FeedbackMutableStatus = "in_review" | "resolved" | "dismissed";

const statusOptions = [
  { label: "กำลังตรวจสอบ", value: "in_review" },
  { label: "ดำเนินการแล้ว", value: "resolved" },
  { label: "ปิดรายงาน", value: "dismissed" },
];

const statusLabels: Record<UserFeedback["status"], string> = {
  pending: "รอตรวจสอบ",
  in_review: "กำลังตรวจสอบ",
  resolved: "ดำเนินการแล้ว",
  dismissed: "ปิดรายงาน",
};

const categoryLabels: Record<string, string> = {
  inaccurate: "ข้อมูลไม่ถูกต้อง",
  outdated: "ข้อมูลล้าสมัย",
  unclear: "ข้อมูลไม่ชัดเจน",
  unsafe: "ข้อมูลอาจไม่ปลอดภัย",
  suggestion: "ข้อเสนอแนะ",
  bug: "ปัญหาการใช้งาน",
  content_error: "ข้อมูลไม่ถูกต้อง",
  other: "อื่น ๆ",
};

const targetTypeLabels: Record<string, string> = {
  article: "บทความ",
  disease: "ข้อมูลโรค",
  symptom: "อาการ",
  first_aid: "ปฐมพยาบาล",
  assessment: "ผลประเมิน",
};

function targetEditPath(item: UserFeedback) {
  if (!item.target_id) return null;
  const id = encodeId(item.target_id);
  if (item.target_type === "article") return `/articles/edit/${id}`;
  if (item.target_type === "disease") return `/diseases/edit/${id}`;
  if (item.target_type === "first_aid") return `/first-aids/edit/${id}`;
  return null;
}

function AttachmentImage({ feedbackId, index, onPreview }: {
  feedbackId: number;
  index: number;
  onPreview: (url: string) => void;
}) {
  const [url, setUrl] = useState<string>();

  useEffect(() => {
    let objectUrl: string | undefined;
    void api
      .get(`/admin/feedback/${feedbackId}/attachments/${index}`, { responseType: "blob" })
      .then((response) => {
        objectUrl = URL.createObjectURL(response.data);
        setUrl(objectUrl);
      });
    return () => {
      if (objectUrl) URL.revokeObjectURL(objectUrl);
    };
  }, [feedbackId, index]);

  if (!url) {
    return <div className="flex aspect-[4/3] w-full items-center justify-center rounded-xl bg-[var(--color-surface)]"><ImageIcon /></div>;
  }

  return (
    <button type="button" className="aspect-[4/3] w-full overflow-hidden rounded-xl border border-[var(--color-border)]" onClick={() => onPreview(url)}>
      <img src={url} alt={`รูปแนบ ${index + 1}`} className="h-full w-full object-cover transition-transform hover:scale-105" />
    </button>
  );
}

export function UserFeedbackDetailsDialog({ item, busy, onClose, onSave }: {
  item: UserFeedback | null;
  busy: boolean;
  onClose: () => void;
  onSave: (id: number, status: FeedbackMutableStatus, adminNote: string) => Promise<void>;
}) {
  const [status, setStatus] = useState<FeedbackMutableStatus>("in_review");
  const [adminNote, setAdminNote] = useState("");
  const [noteError, setNoteError] = useState("");
  const [previewUrl, setPreviewUrl] = useState<string>();

  useEffect(() => {
    if (!item) return;
    setStatus(item.status === "pending" ? "in_review" : item.status);
    setAdminNote(item.admin_note ?? "");
    setNoteError("");
    setPreviewUrl(undefined);
  }, [item]);

  if (!item) return null;
  const editPath = targetEditPath(item);

  return <>
    <Dialog open onOpenChange={(open) => !open && !busy && onClose()}>
      <DialogContent maxWidth="2xl" className="overflow-y-auto md:max-h-[92vh]">
        <DialogHeader>
          <DialogTitle className="text-xl">รายละเอียดข้อเสนอแนะ</DialogTitle>
          <DialogDescription>ตรวจสอบข้อมูลทั้งหมดและบันทึกผลการดำเนินการ</DialogDescription>
        </DialogHeader>
        <div className="grid gap-5 md:grid-cols-[minmax(0,1fr)_280px]">
          <div className="space-y-4">
            <section className="rounded-2xl border border-[var(--color-border)] p-5">
              <div className="flex flex-wrap items-start justify-between gap-3">
                <div>
                  <p className="font-semibold">{`${item.user?.first_name ?? ""} ${item.user?.last_name ?? ""}`.trim() || "ผู้ใช้งาน"}</p>
                  <p className="text-sm text-[var(--color-text-secondary)]">{item.user?.email}</p>
                </div>
                <Badge variant={item.status === "pending" ? "warning" : item.status === "resolved" ? "success" : "primary"}>{statusLabels[item.status]}</Badge>
              </div>
              <div className="mt-5 flex flex-wrap gap-2">
                <Badge variant="primary">{FEEDBACK_TYPE_LABELS[item.feedback_type]}</Badge>
                {item.category && <Badge variant="outline">{categoryLabels[item.category] ?? item.category}</Badge>}
              </div>
              <p className="mt-5 whitespace-pre-wrap break-words">{item.message}</p>
            </section>
            {!!item.attachments?.length && (
              <section>
                <h3 className="mb-2 font-semibold">รูปที่แนบ ({item.attachments.length})</h3>
                <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                  {item.attachments.map((_, index) => (
                    <AttachmentImage key={index} feedbackId={item.id} index={index} onPreview={setPreviewUrl} />
                  ))}
                </div>
              </section>
            )}
          </div>
          <aside className="space-y-4 rounded-2xl border border-[var(--color-border)] bg-white p-4">
            <div><p className="text-xs text-[var(--color-text-secondary)]">ส่งเมื่อ</p><p className="mt-1 text-sm">{formatAdminDateTime(item.created_at)}</p></div>
            <div>
              <p className="text-xs text-[var(--color-text-secondary)]">รายงานเรื่อง</p>
              <p className="mt-1 font-medium">{item.target_type ? targetTypeLabels[item.target_type] ?? item.target_type : "ไม่ระบุรายการ"}</p>
              <p className="break-words text-sm text-[var(--color-text-secondary)]">{item.target_name ?? item.target_id}</p>
              {editPath && <Button asChild variant="link" size="sm" className="px-0"><Link to={editPath}><ExternalLink />ดูและแก้ไข</Link></Button>}
            </div>
            <SimpleSelect label="สถานะ" value={status} options={statusOptions} disabled={busy} onChange={(value) => setStatus(value as FeedbackMutableStatus)} />
            {status !== "in_review" && (
              <Textarea
                label={status === "resolved" ? "ข้อความแจ้งผลถึงผู้ใช้" : "เหตุผลที่ปิดรายงาน"}
                rows={5}
                value={adminNote}
                disabled={busy}
                error={noteError}
                placeholder={status === "resolved" ? "อธิบายผลการตรวจสอบหรือสิ่งที่แก้ไข" : "อธิบายเหตุผลที่ปิดรายงาน"}
                onChange={(event) => {
                  setAdminNote(event.target.value);
                  if (event.target.value.trim()) setNoteError("");
                }}
              />
            )}
          </aside>
        </div>
        <DialogFooter>
          <Button variant="outline" disabled={busy} onClick={onClose}>ปิด</Button>
          <Button loading={busy} onClick={() => {
            const note = adminNote.trim();
            if (status !== "in_review" && !note) {
              setNoteError("กรุณากรอกข้อความถึงผู้ใช้");
              return;
            }
            void onSave(item.id, status, status === "in_review" ? "" : note);
          }}>บันทึกผล</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
    <Dialog open={Boolean(previewUrl)} onOpenChange={(open) => !open && setPreviewUrl(undefined)}>
      <DialogContent maxWidth="2xl" className="md:max-h-[94vh]">
        <DialogHeader>
          <DialogTitle>รูปที่แนบ</DialogTitle>
          <DialogDescription>ภาพขนาดเต็มจากข้อเสนอแนะของผู้ใช้</DialogDescription>
        </DialogHeader>
        {previewUrl && <div className="flex min-h-64 items-center justify-center overflow-hidden rounded-xl bg-black/5 p-2"><img src={previewUrl} alt="รูปแนบขนาดเต็ม" className="max-h-[76vh] max-w-full object-contain" /></div>}
        <DialogFooter><Button variant="outline" onClick={() => setPreviewUrl(undefined)}>ปิด</Button></DialogFooter>
      </DialogContent>
    </Dialog>
  </>;
}
