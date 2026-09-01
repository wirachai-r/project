import { Badge } from "@/components/ui/Badge";
import { Card } from "@/components/ui/Card";
import { Skeleton } from "@/components/ui/Skeleton";
import { ArticleCommentThread, type ArticleComment, type CommentReply } from "./ArticleCommentThread";
import type { ReportAction } from "./ArticleCommentReportTable";

function plainText(html: string) {
  const document = new DOMParser().parseFromString(html, "text/html");
  return document.body.textContent?.trim() ?? "";
}

export function ArticleCommentsDetailSkeleton() {
  return (
    <Card className="overflow-hidden p-0" aria-label="กำลังโหลดความคิดเห็น">
      <div className="border-b border-[var(--color-border)] bg-[var(--color-surface)]/50 p-5 sm:p-6"><div className="flex gap-4"><Skeleton className="h-20 w-28 shrink-0 rounded-xl" /><div className="min-w-0 flex-1"><Skeleton className="h-6 w-20 rounded-full" /><Skeleton className="mt-3 h-6 w-1/2" /><Skeleton className="mt-3 h-3.5 w-full" /><Skeleton className="mt-2 h-3.5 w-4/5" /></div></div></div>
      <div className="space-y-6 p-5 sm:p-6"><div className="flex items-center justify-between"><Skeleton className="h-6 w-28" /><Skeleton className="h-4 w-32" /></div>{[1, 2, 3].map((key) => <div key={key} className="flex gap-3"><Skeleton className="h-10 w-10 shrink-0 rounded-full" /><Skeleton className="h-36 min-w-0 flex-1 rounded-2xl" /></div>)}</div>
    </Card>
  );
}

export function ArticleCommentsDetail({ groups, busyId, onVisibilityChange, onDelete, onResolveReports }: {
  groups: ArticleComment[][];
  busyId: number | null;
  onVisibilityChange: (comment: CommentReply) => void;
  onDelete: (comment: CommentReply) => void;
  onResolveReports: (comment: CommentReply, action: ReportAction) => void;
}) {
  return groups.map((comments) => {
    const article = comments[0].article;
    return (
      <Card key={comments[0].article_id} className="overflow-hidden p-0">
        <div className="border-b border-[var(--color-border)] bg-[var(--color-surface)]/50 p-5 sm:p-6">
          <div className="flex gap-4">
            {article?.thumbnail && <img src={article.thumbnail} alt="" loading="lazy" decoding="async" className="h-20 w-28 shrink-0 rounded-xl object-cover" />}
            <div className="min-w-0"><Badge variant="primary">บทความ</Badge><h2 className="mt-2 text-lg font-semibold text-[var(--color-text-primary)]">{article?.title ?? "ไม่พบบทความ"}</h2><p className="mt-2 line-clamp-3 text-sm leading-6 text-[var(--color-text-secondary)]">{plainText(article?.content ?? "")}</p></div>
          </div>
        </div>
        <div className="space-y-6 p-5 sm:p-6">
          <div className="flex items-center justify-between"><h3 className="text-lg font-semibold text-[var(--color-text-primary)]">ความคิดเห็น</h3><span className="text-sm text-[var(--color-text-secondary)]">{comments.length} ความคิดเห็นหลัก</span></div>
          {comments.map((comment) => <ArticleCommentThread key={comment.id} comment={comment} busyId={busyId} onVisibilityChange={onVisibilityChange} onDelete={onDelete} onResolveReports={onResolveReports} />)}
        </div>
      </Card>
    );
  });
}
