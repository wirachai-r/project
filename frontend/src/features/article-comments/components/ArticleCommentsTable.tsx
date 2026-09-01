import { Eye, Flag } from "lucide-react";
import { Badge } from "@/components/ui/Badge";
import { Button } from "@/components/ui/Button";
import { Card } from "@/components/ui/Card";
import { DataTable, type Column } from "@/components/ui/DataTable";
import type { ArticleComment } from "./ArticleCommentThread";

type Row = { comments: ArticleComment[]; rowNumber: number };

function formatDate(value: string) {
  return new Date(value).toLocaleString("th-TH", {
    dateStyle: "medium",
    timeStyle: "short",
  });
}

export function ArticleCommentsTable({
  groups,
  firstRow,
  onView,
}: {
  groups: ArticleComment[][];
  firstRow: number;
  onView: (articleId: string) => void;
}) {
  const columns: Column<Row>[] = [
    {
      key: "sequence",
      label: "ลำดับ",
      className: "w-20 text-center",
      render: (row) => row.rowNumber,
    },
    {
      key: "article",
      label: "บทความ",
      className: "min-w-72",
      render: ({ comments }) => {
        const article = comments[0].article;
        return (
          <div className="flex items-center gap-3">
            {article?.thumbnail ? (
              <img
                src={article.thumbnail}
                alt=""
                loading="lazy"
                decoding="async"
                className="h-12 w-16 shrink-0 rounded-lg object-cover"
              />
            ) : (
              <div className="h-12 w-16 shrink-0 rounded-lg bg-[var(--color-surface)]" />
            )}
            <span className="font-medium text-[var(--color-text-primary)]">
              {article?.title ?? "ไม่พบบทความ"}
            </span>
          </div>
        );
      },
    },
    {
      key: "comments",
      label: "ความคิดเห็น",
      render: ({ comments }) => (
        <Badge>
          {comments.reduce(
            (sum, comment) => sum + 1 + comment.replies_count,
            0,
          )}{" "}
          ความคิดเห็น
        </Badge>
      ),
    },
    {
      key: "reports",
      label: "รายงาน",
      render: ({ comments }) => {
        const count = comments.reduce(
          (sum, comment) =>
            sum +
            comment.pending_reports_count +
            comment.replies.reduce(
              (replySum, reply) => replySum + reply.pending_reports_count,
              0,
            ),
          0,
        );
        return count > 0 ? (
          <Badge variant="danger">
            <Flag className="mr-1" /> {count} รอตรวจสอบ
          </Badge>
        ) : (
          <Badge variant="success">ไม่มีรายงาน</Badge>
        );
      },
    },
    {
      key: "latest_activity",
      label: "กิจกรรมล่าสุด",
      className: "min-w-44",
      render: ({ comments }) => {
        const latest = comments
          .flatMap((comment) => [
            comment.created_at,
            ...comment.replies.map((reply) => reply.created_at),
          ])
          .reduce(
            (current, date) =>
              new Date(date) > new Date(current) ? date : current,
            comments[0].created_at,
          );
        return (
          <span className="text-[var(--color-text-secondary)]">
            {formatDate(latest)}
          </span>
        );
      },
    },
    {
      key: "actions",
      label: "การจัดการ",
      className: "w-32 text-right",
      render: ({ comments }) => (
        <Button
          size="sm"
          variant="outline"
          onClick={() => onView(comments[0].article_id)}
        >
          <Eye /> ดูความคิดเห็น
        </Button>
      ),
    },
  ];
  const rows = groups.map((comments, index) => ({
    comments,
    rowNumber: firstRow + index,
  }));
  return (
    <Card className="overflow-hidden p-0">
      <DataTable
        columns={columns}
        data={rows}
        keyExtractor={(row) => row.comments[0].article_id}
        emptyMessage="ไม่พบความคิดเห็นบทความ"
      />
    </Card>
  );
}
