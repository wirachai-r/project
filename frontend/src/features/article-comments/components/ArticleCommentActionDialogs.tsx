import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from "@/components/ui/AlertDialog";
import type { CommentReply } from "./ArticleCommentThread";
import type { ReportAction } from "./ArticleCommentReportTable";

export type ReportActionTarget = {
  comment: CommentReply;
  action: ReportAction;
};

const actionCopy: Record<
  ReportAction,
  { title: string; description: string; confirm: string }
> = {
  dismiss: {
    title: "ยืนยันว่าไม่พบการละเมิด",
    description:
      "รายงานที่รอตรวจสอบทั้งหมดของความคิดเห็นนี้จะถูกปิด และความคิดเห็นยังแสดงตามปกติ",
    confirm: "ยืนยันผลตรวจสอบ",
  },
  hide: {
    title: "ยืนยันการซ่อนความคิดเห็น",
    description:
      "ความคิดเห็นจะไม่แสดงต่อผู้ใช้งาน และรายงานที่รอตรวจสอบทั้งหมดจะถือว่าดำเนินการแล้ว",
    confirm: "ซ่อนความคิดเห็น",
  },
  delete: {
    title: "ยืนยันการลบความคิดเห็น",
    description:
      "ความคิดเห็นและข้อความตอบกลับที่เกี่ยวข้องจะถูกลบถาวร และรายงานทั้งหมดจะถือว่าดำเนินการแล้ว",
    confirm: "ลบความคิดเห็นถาวร",
  },
};

export function ArticleCommentActionDialogs({
  deleteTarget,
  reportTarget,
  busyId,
  onCloseDelete,
  onConfirmDelete,
  onCloseReport,
  onConfirmReport,
}: {
  deleteTarget: CommentReply | null;
  reportTarget: ReportActionTarget | null;
  busyId: number | null;
  onCloseDelete: () => void;
  onConfirmDelete: () => void;
  onCloseReport: () => void;
  onConfirmReport: () => void;
}) {
  return (
    <>
      <AlertDialog
        open={!!deleteTarget}
        onOpenChange={(open) => !open && onCloseDelete()}
      >
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>ยืนยันการลบความคิดเห็น</AlertDialogTitle>
            <AlertDialogDescription>
              ความคิดเห็นนี้และข้อความตอบกลับที่เกี่ยวข้องจะถูกลบอย่างถาวร
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel disabled={busyId === deleteTarget?.id}>
              ยกเลิก
            </AlertDialogCancel>
            <AlertDialogAction
              loading={busyId === deleteTarget?.id}
              onClick={onConfirmDelete}
            >
              ลบความคิดเห็น
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
      <AlertDialog
        open={!!reportTarget}
        onOpenChange={(open) => !open && onCloseReport()}
      >
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>
              {reportTarget && actionCopy[reportTarget.action].title}
            </AlertDialogTitle>
            <AlertDialogDescription>
              {reportTarget && actionCopy[reportTarget.action].description}
            </AlertDialogDescription>
          </AlertDialogHeader>
          {reportTarget && (
            <div className="rounded-lg bg-[var(--color-surface)] p-3 text-sm">
              <p className="line-clamp-3 whitespace-pre-wrap">
                {reportTarget.comment.content}
              </p>
              <p className="mt-2 text-xs text-[var(--color-text-secondary)]">
                มีรายงานรอตรวจสอบ {reportTarget.comment.pending_reports_count}{" "}
                รายงาน
              </p>
            </div>
          )}
          <AlertDialogFooter>
            <AlertDialogCancel disabled={busyId !== null}>
              ยกเลิก
            </AlertDialogCancel>
            <AlertDialogAction
              loading={busyId !== null}
              onClick={onConfirmReport}
            >
              {reportTarget && actionCopy[reportTarget.action].confirm}
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </>
  );
}
