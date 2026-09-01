import {
  CheckCircle2,
  Eye,
  EyeOff,
  MoreHorizontal,
  Trash2,
  UserRound,
} from "lucide-react";
import { Badge } from "@/components/ui/Badge";
import { Button } from "@/components/ui/Button";
import { DataTable, type Column } from "@/components/ui/DataTable";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "@/components/ui/DropdownMenu";
import {
  ARTICLE_COMMENT_REPORT_REASONS,
  type ArticleCommentReport,
} from "@/types/articleComment";

export type ReportAction = "dismiss" | "hide" | "delete";

type Props = {
  data: ArticleCommentReport[];
  busyId: number | null;
  onView: (report: ArticleCommentReport) => void;
  onResolve: (report: ArticleCommentReport, action: ReportAction) => void;
};

function formatDate(value: string) {
  return new Date(value).toLocaleString("th-TH", {
    dateStyle: "medium",
    timeStyle: "short",
  });
}

function fullName(user?: { first_name?: string; last_name?: string }) {
  return `${user?.first_name ?? ""} ${user?.last_name ?? ""}`.trim();
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
      className: "min-w-52",
      render: (item) => (
        <div className="space-y-1">
          <p className="font-medium text-[var(--color-text-primary)]">
            {item.comment?.article?.title ?? "ไม่พบบทความ"}
          </p>
          <p className="text-xs text-[var(--color-text-secondary)]">
            รายงานเมื่อ {formatDate(item.created_at)}
          </p>
        </div>
      ),
    },
    {
      key: "comment",
      label: "ความคิดเห็นที่ถูกรายงาน",
      className: "min-w-64 max-w-lg",
      render: (item) => (
        <div className="space-y-1.5">
          <p className="line-clamp-3 whitespace-pre-wrap text-[var(--color-text-primary)]">
            {item.comment?.content ?? "ความคิดเห็นนี้ถูกลบแล้ว"}
          </p>
          {item.comment && (
            <p className="flex items-center gap-1 text-xs text-[var(--color-text-secondary)]">
              <UserRound className="h-3.5 w-3.5" /> ผู้แสดงความคิดเห็น:{" "}
              {fullName(item.comment.user) || "ไม่พบข้อมูลผู้ใช้"}
            </p>
          )}
        </div>
      ),
    },
    {
      key: "report",
      label: "ข้อมูลการรายงาน",
      className: "min-w-52",
      render: (item) => (
        <div className="space-y-1.5">
          <Badge variant="danger">
            {ARTICLE_COMMENT_REPORT_REASONS[item.reason] ?? item.reason}
          </Badge>
          <p className="text-xs text-[var(--color-text-secondary)]">
            ผู้รายงาน: {fullName(item.reporter) || "ไม่พบข้อมูลผู้ใช้"}
          </p>
          {item.details && (
            <p className="line-clamp-2 text-xs" title={item.details}>
              รายละเอียด: {item.details}
            </p>
          )}
        </div>
      ),
    },
    {
      key: "status",
      label: "สถานะรายงาน",
      render: (item) => (
        <Badge
          variant={
            item.status === "pending"
              ? "warning"
              : item.status === "resolved"
                ? "success"
                : "default"
          }
        >
          {item.status === "pending"
            ? "รอตรวจสอบ"
            : item.status === "resolved"
              ? "ดำเนินการแล้ว"
              : "ไม่พบการละเมิด"}
        </Badge>
      ),
    },
    {
      key: "actions",
      label: "การจัดการ",
      className: "min-w-52 text-right",
      render: (item) => (
        <div className="flex justify-end gap-2">
          <Button
            size="sm"
            variant="outline"
            disabled={!item.comment}
            onClick={() => onView(item)}
          >
            <Eye /> ดูรายละเอียด
          </Button>
          {item.status === "pending" && (
            <DropdownMenu>
              <DropdownMenuTrigger asChild>
                <Button
                  size="icon"
                  variant="ghost"
                  disabled={busyId === item.id}
                  aria-label="ตัวเลือกจัดการรายงาน"
                >
                  <MoreHorizontal />
                </Button>
              </DropdownMenuTrigger>
              <DropdownMenuContent align="end" className="min-w-52">
                <DropdownMenuItem onClick={() => onResolve(item, "dismiss")}>
                  <CheckCircle2 /> ไม่พบการละเมิด
                </DropdownMenuItem>
                <DropdownMenuItem onClick={() => onResolve(item, "hide")}>
                  <EyeOff /> ซ่อนความคิดเห็น
                </DropdownMenuItem>
                <DropdownMenuSeparator />
                <DropdownMenuItem
                  variant="danger"
                  onClick={() => onResolve(item, "delete")}
                >
                  <Trash2 /> ลบความคิดเห็นถาวร
                </DropdownMenuItem>
              </DropdownMenuContent>
            </DropdownMenu>
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
      emptyMessage="ไม่พบรายงานที่ตรงกับตัวกรอง"
    />
  );
}
