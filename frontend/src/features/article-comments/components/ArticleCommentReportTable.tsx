import { Eye } from "lucide-react";
import { Badge } from "@/components/ui/Badge";
import { Button } from "@/components/ui/Button";
import { DataTable, type Column } from "@/components/ui/DataTable";
import {
  ARTICLE_COMMENT_REPORT_REASONS,
  type ArticleCommentReport,
} from "@/types/articleComment";

type Props = {
  data: ArticleCommentReport[];
  busyId: number | null;
  onView: (report: ArticleCommentReport) => void;
  onResolve: (
    id: number,
    action: "dismiss" | "hide" | "delete",
  ) => void;
};

function formatDate(value: string) {
  return new Date(value).toLocaleString("th-TH", {
    dateStyle: "medium",
    timeStyle: "short",
  });
}

export function ArticleCommentReportTable({
  data,
  busyId,
  onView,
  onResolve,
}: Props) {
  const columns: Column<ArticleCommentReport>[] = [
    {
      key: "sequence",
      label: "ลำดับ",
      render: (item) =>
        String(
          (item as ArticleCommentReport & { __rowNumber?: number })
            .__rowNumber ?? "-",
        ),
    },
    {
      key: "article",
      label: "บทความ",
      className: "min-w-56",
      render: (item) => (
        <div>
          <div className="font-medium text-[var(--color-text-primary)]">
            {item.comment?.article?.title ?? "ไม่พบบทความ"}
          </div>
          <div className="mt-1 text-xs text-[var(--color-text-secondary)]">
            {formatDate(item.created_at)}
          </div>
        </div>
      ),
    },
    {
      key: "comment",
      label: "ความคิดเห็น",
      className: "min-w-64 max-w-lg",
      render: (item) => (
        <p className="line-clamp-3 whitespace-pre-wrap">
          {item.comment?.content ?? "ความคิดเห็นถูกลบแล้ว"}
        </p>
      ),
    },
    {
      key: "reason",
      label: "เหตุผล/ผู้รายงาน",
      className: "min-w-52",
      render: (item) => {
        const reporter = `${item.reporter?.first_name ?? ""} ${item.reporter?.last_name ?? ""}`.trim();
        return (
          <div>
            <Badge variant="danger">
              {ARTICLE_COMMENT_REPORT_REASONS[item.reason] ?? item.reason}
            </Badge>
            <div className="mt-2 text-xs text-[var(--color-text-secondary)]">
              โดย {reporter || "ไม่พบผู้ใช้"}
            </div>
            {item.details && <p className="mt-1 text-xs">{item.details}</p>}
          </div>
        );
      },
    },
    {
      key: "status",
      label: "สถานะ",
      render: (item) => (
        <Badge variant={item.status === "pending" ? "warning" : "success"}>
          {item.status === "pending"
            ? "รอตรวจสอบ"
            : item.status === "resolved"
              ? "จัดการแล้ว"
              : "ยกเลิกรายงาน"}
        </Badge>
      ),
    },
    {
      key: "actions",
      label: "การจัดการ",
      className: "min-w-80 text-right",
      render: (item) => (
        <div className="flex flex-wrap justify-end gap-2">
          <Button
            size="sm"
            variant="outline"
            disabled={!item.comment?.article_id}
            onClick={() => onView(item)}
          >
            <Eye className="h-4 w-4" /> ดูความคิดเห็น
          </Button>
          {item.status === "pending" && (
            <>
              <Button size="sm" variant="outline" disabled={busyId === item.id} onClick={() => onResolve(item.id, "dismiss")}>
                ยกเลิกรายงาน
              </Button>
              <Button size="sm" variant="outline" disabled={busyId === item.id} onClick={() => onResolve(item.id, "hide")}>
                ซ่อน
              </Button>
              <Button size="sm" variant="danger" disabled={busyId === item.id} onClick={() => onResolve(item.id, "delete")}>
                ลบ
              </Button>
            </>
          )}
        </div>
      ),
    },
  ];

  return (
    <DataTable
      columns={columns}
      data={data}
      keyExtractor={(item) => item.id}
      emptyMessage="ไม่มีรายงานที่ตรงกับตัวกรอง"
    />
  );
}
