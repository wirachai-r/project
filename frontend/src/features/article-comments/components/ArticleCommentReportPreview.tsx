import { useState } from "react";
import { ExternalLink, Flag, ThumbsUp } from "lucide-react";
import { Badge } from "@/components/ui/Badge";
import { Button } from "@/components/ui/Button";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/Dialog";
import {
  ARTICLE_COMMENT_REPORT_REASONS,
  type ArticleCommentReport,
} from "@/types/articleComment";

type Props = {
  report: ArticleCommentReport | null;
  onClose: () => void;
  onOpenManagement: (report: ArticleCommentReport) => void;
};

function UserAvatar({ image, initials }: { image?: string | null; initials: string }) {
  const [failedImage, setFailedImage] = useState<string | null>(null);

  return (
    <div className="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-full bg-[var(--color-primary-light)] font-semibold text-[var(--color-primary)]">
      {image && failedImage !== image ? (
        <img
          src={image}
          alt=""
          loading="lazy"
          decoding="async"
          className="h-full w-full object-cover"
          onError={() => setFailedImage(image)}
        />
      ) : (
        initials.toUpperCase()
      )}
    </div>
  );
}

function formatDate(value: string) {
  return new Date(value).toLocaleString("th-TH", {
    dateStyle: "medium",
    timeStyle: "short",
  });
}

export function ArticleCommentReportPreview({
  report,
  onClose,
  onOpenManagement,
}: Props) {
  const comment = report?.comment;
  const name = `${comment?.user?.first_name ?? ""} ${comment?.user?.last_name ?? ""}`.trim() || "ไม่พบผู้ใช้";
  const initials = `${comment?.user?.first_name?.[0] ?? ""}${comment?.user?.last_name?.[0] ?? ""}` || "U";

  return (
    <Dialog open={report !== null} onOpenChange={(open) => !open && onClose()}>
      <DialogContent maxWidth="2xl">
        <DialogHeader>
          <DialogTitle>รายละเอียดความคิดเห็นที่ถูกรายงาน</DialogTitle>
          <DialogDescription>
            {comment?.article?.title ?? "ไม่พบบทความ"}
          </DialogDescription>
        </DialogHeader>

        {comment ? (
          <div className="space-y-4">
            <div className="flex gap-3">
              <UserAvatar image={comment.user?.profile_image} initials={initials} />
              <div className="min-w-0 flex-1 rounded-2xl border border-red-300 bg-red-50/70 p-4 shadow-sm">
                <div className="flex flex-wrap items-start justify-between gap-2">
                  <div>
                    <p className="font-semibold text-[var(--color-text-primary)]">
                      {name}
                    </p>
                    <p className="text-xs text-[var(--color-text-secondary)]">
                      {comment.user?.email || "ไม่พบอีเมล"} · {formatDate(comment.created_at)}
                    </p>
                  </div>
                  <div className="flex flex-wrap items-center gap-2">
                    <Badge variant="danger">
                      <Flag className="mr-1" /> ความคิดเห็นถูกรายงาน {comment.pending_reports_count} ครั้ง
                    </Badge>
                    {comment.hidden_at && <Badge variant="warning">ถูกซ่อน</Badge>}
                  </div>
                </div>
                <p className="mt-3 whitespace-pre-wrap break-words text-[var(--color-text-primary)]">
                  {comment.content}
                </p>
                <div className="mt-4 flex items-center gap-1.5 text-sm font-medium text-[var(--color-text-secondary)]">
                  <ThumbsUp className="h-4 w-4" /> {comment.likes_count}
                </div>
              </div>
            </div>

            <div className="rounded-xl border border-[var(--color-border)] p-4 text-sm">
              <div className="flex flex-wrap items-center gap-2">
                <span className="font-medium">เหตุผลที่รายงาน:</span>
                <Badge variant="danger">
                  {report
                    ? ARTICLE_COMMENT_REPORT_REASONS[report.reason] ?? report.reason
                    : "-"}
                </Badge>
              </div>
              {report?.details && (
                <p className="mt-2 text-[var(--color-text-secondary)]">
                  รายละเอียดเพิ่มเติม: {report.details}
                </p>
              )}
            </div>
          </div>
        ) : (
          <p className="rounded-xl bg-[var(--color-surface)] p-4 text-sm text-[var(--color-text-secondary)]">
            ความคิดเห็นนี้ถูกลบแล้ว
          </p>
        )}

        <DialogFooter>
          <Button variant="ghost" onClick={onClose}>ปิด</Button>
          <Button
            disabled={!comment?.article_id}
            onClick={() => report && onOpenManagement(report)}
          >
            <ExternalLink /> เปิดหน้าจัดการความคิดเห็น
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
