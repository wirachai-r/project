import { useState } from "react";
import {
  CheckCircle2,
  ChevronDown,
  ChevronUp,
  Eye,
  EyeOff,
  Flag,
  MoreHorizontal,
  ThumbsUp,
  Trash2,
} from "lucide-react";
import { Badge } from "@/components/ui/Badge";
import { Button } from "@/components/ui/Button";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "@/components/ui/DropdownMenu";
import {
  Tooltip,
  TooltipContent,
  TooltipTrigger,
} from "@/components/ui/Tooltip";
import type { ReportAction } from "./ArticleCommentReportTable";

export type CommentUser = {
  user_id: string;
  first_name: string;
  last_name: string;
  email: string;
  profile_image?: string | null;
};

export type CommentReply = {
  id: number;
  content: string;
  hidden_at: string | null;
  created_at: string;
  likes_count: number;
  pending_reports_count: number;
  user?: CommentUser;
};

export type ArticleComment = CommentReply & {
  article_id: string;
  replies_count: number;
  replies: CommentReply[];
  article?: {
    article_id: string;
    title: string;
    content: string;
    thumbnail?: string | null;
  };
};

function formatDate(value: string) {
  return new Date(value).toLocaleString("th-TH", {
    dateStyle: "medium",
    timeStyle: "short",
  });
}

function UserAvatar({ user }: { user?: CommentUser }) {
  const initials =
    `${user?.first_name?.[0] ?? ""}${user?.last_name?.[0] ?? ""}` || "U";
  return (
    <div className="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-full bg-[var(--color-primary-light)] font-semibold text-[var(--color-primary)]">
      {user?.profile_image ? (
        <img
          src={user.profile_image}
          alt=""
          loading="lazy"
          decoding="async"
          className="h-full w-full object-cover"
        />
      ) : (
        initials.toUpperCase()
      )}
    </div>
  );
}

function CommentCard({
  comment,
  reply = false,
  busy,
  onVisibilityChange,
  onDelete,
  onResolveReports,
}: {
  comment: CommentReply;
  reply?: boolean;
  busy: boolean;
  onVisibilityChange: (comment: CommentReply) => void;
  onDelete: (comment: CommentReply) => void;
  onResolveReports: (comment: CommentReply, action: ReportAction) => void;
}) {
  const name =
    `${comment.user?.first_name ?? ""} ${comment.user?.last_name ?? ""}`.trim() ||
    "ไม่พบผู้ใช้";
  return (
    <div
      className={`flex gap-3 ${reply ? "ml-8 border-l-2 border-[var(--color-primary-light)] pl-4" : ""}`}
    >
      <UserAvatar user={comment.user} />
      <div
        className={`min-w-0 flex-1 rounded-2xl border p-4 ${comment.pending_reports_count > 0 ? "border-red-300 bg-red-50/70 shadow-sm" : "border-transparent bg-[var(--color-surface)]"}`}
      >
        <div className="flex flex-wrap items-start justify-between gap-2">
          <div>
            <div className="font-semibold text-[var(--color-text-primary)]">
              {name}
            </div>
            <div className="text-xs text-[var(--color-text-secondary)]">
              {comment.user?.email} · {formatDate(comment.created_at)}
            </div>
          </div>
          <div className="flex items-center gap-2">
            {comment.pending_reports_count > 0 && (
              <Badge variant="danger">
                <Flag className="mr-1" />
                {reply ? "ข้อความตอบกลับ" : "ความคิดเห็นหลัก"}ถูกรายงาน{" "}
                {comment.pending_reports_count} ครั้ง
              </Badge>
            )}
            {comment.hidden_at && <Badge variant="warning">ถูกซ่อน</Badge>}
          </div>
        </div>
        <p className="mt-3 whitespace-pre-wrap break-words text-[var(--color-text-primary)]">
          {comment.content}
        </p>
        <div className="mt-4 flex items-center gap-3 text-sm font-medium text-[var(--color-text-secondary)]">
          <span className="flex items-center gap-1.5">
            <ThumbsUp className="h-4 w-4" /> {comment.likes_count}
          </span>
          {reply && <span>ข้อความตอบกลับ</span>}
          <span className="ml-auto flex items-center gap-1">
            {comment.pending_reports_count > 0 && (
              <DropdownMenu>
                <DropdownMenuTrigger asChild>
                  <Button size="sm" variant="outline" disabled={busy}>
                    <MoreHorizontal /> จัดการรายงาน
                  </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end" className="min-w-52">
                  <DropdownMenuItem
                    onClick={() => onResolveReports(comment, "dismiss")}
                  >
                    <CheckCircle2 /> ไม่พบการละเมิด
                  </DropdownMenuItem>
                  <DropdownMenuItem
                    onClick={() => onResolveReports(comment, "hide")}
                  >
                    <EyeOff /> ซ่อนความคิดเห็น
                  </DropdownMenuItem>
                  <DropdownMenuSeparator />
                  <DropdownMenuItem
                    variant="danger"
                    onClick={() => onResolveReports(comment, "delete")}
                  >
                    <Trash2 /> ลบความคิดเห็นถาวร
                  </DropdownMenuItem>
                </DropdownMenuContent>
              </DropdownMenu>
            )}
            {comment.pending_reports_count === 0 && (
              <>
                <Tooltip>
                  <TooltipTrigger asChild>
                    <span>
                      <button
                        type="button"
                        onClick={() => onVisibilityChange(comment)}
                        disabled={busy}
                        className="rounded-lg p-2 hover:bg-white disabled:opacity-50"
                        aria-label={
                          comment.hidden_at
                            ? "แสดงความคิดเห็น"
                            : "ซ่อนความคิดเห็น"
                        }
                      >
                        {comment.hidden_at ? (
                          <Eye className="h-4 w-4" />
                        ) : (
                          <EyeOff className="h-4 w-4" />
                        )}
                      </button>
                    </span>
                  </TooltipTrigger>
                  <TooltipContent>
                    {comment.hidden_at ? "แสดงความคิดเห็น" : "ซ่อนความคิดเห็น"}
                  </TooltipContent>
                </Tooltip>
                <Tooltip>
                  <TooltipTrigger asChild>
                    <span>
                      <button
                        type="button"
                        onClick={() => onDelete(comment)}
                        disabled={busy}
                        className="rounded-lg p-2 text-red-600 hover:bg-red-100 disabled:opacity-50"
                        aria-label="ลบความคิดเห็น"
                      >
                        <Trash2 className="h-4 w-4" />
                      </button>
                    </span>
                  </TooltipTrigger>
                  <TooltipContent>ลบความคิดเห็น</TooltipContent>
                </Tooltip>
              </>
            )}
          </span>
        </div>
      </div>
    </div>
  );
}

export function ArticleCommentThread({
  comment,
  busyId,
  onVisibilityChange,
  onDelete,
  onResolveReports,
}: {
  comment: ArticleComment;
  busyId: number | null;
  onVisibilityChange: (comment: CommentReply) => void;
  onDelete: (comment: CommentReply) => void;
  onResolveReports: (comment: CommentReply, action: ReportAction) => void;
}) {
  const replyReportCount = comment.replies.reduce(
    (sum, reply) => sum + reply.pending_reports_count,
    0,
  );
  const [expanded, setExpanded] = useState(replyReportCount > 0);
  return (
    <div
      className={`space-y-3 rounded-2xl ${replyReportCount > 0 ? "ring-2 ring-red-200 ring-offset-4" : ""}`}
    >
      <CommentCard
        comment={comment}
        busy={busyId === comment.id}
        onVisibilityChange={onVisibilityChange}
        onDelete={onDelete}
        onResolveReports={onResolveReports}
      />
      {comment.replies.length > 0 && (
        <button
          type="button"
          onClick={() => setExpanded((value) => !value)}
          className={`ml-14 flex items-center gap-1.5 text-sm font-semibold hover:underline ${replyReportCount > 0 ? "text-red-600" : "text-[var(--color-primary)]"}`}
        >
          {expanded ? (
            <ChevronUp className="h-4 w-4" />
          ) : (
            <ChevronDown className="h-4 w-4" />
          )}
          {replyReportCount > 0 && <Flag className="h-4 w-4 fill-red-100" />}
          {expanded
            ? "ซ่อนข้อความตอบกลับ"
            : `ดูการตอบกลับ ${comment.replies_count} รายการ`}
          {replyReportCount > 0 && (
            <span className="rounded-full bg-red-100 px-2 py-0.5 text-xs text-red-700">
              มีรายงาน {replyReportCount} ครั้ง
            </span>
          )}
        </button>
      )}
      {expanded && (
        <div className="space-y-3">
          {comment.replies.map((reply) => (
            <CommentCard
              key={reply.id}
              comment={reply}
              reply
              busy={busyId === reply.id}
              onVisibilityChange={onVisibilityChange}
              onDelete={onDelete}
              onResolveReports={onResolveReports}
            />
          ))}
        </div>
      )}
    </div>
  );
}
