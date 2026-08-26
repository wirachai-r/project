import { useCallback, useEffect, useMemo, useState } from "react";
import {
  ArrowLeft,
  ChevronDown,
  ChevronUp,
  Eye,
  EyeOff,
  Flag,
  ThumbsUp,
  MessageCircle,
  Trash2,
  X,
} from "lucide-react";
import { useNavigate, useParams, useSearchParams } from "react-router-dom";
import { toast } from "sonner";
import { Badge } from "@/components/ui/Badge";
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
import { Button } from "@/components/ui/Button";
import { Card } from "@/components/ui/Card";
import { SearchBar } from "@/components/ui/SearchBar";
import { Pagination } from "@/components/ui/Pagination";
import { SimpleSelect } from "@/components/ui/SimpleSelect";
import { Skeleton } from "@/components/ui/Skeleton";
import { TableSkeleton } from "@/components/ui/TableSkeleton";
import { DataTable, type Column } from "@/components/ui/DataTable";
import { Tooltip, TooltipContent, TooltipTrigger } from "@/components/ui/Tooltip";
import { api } from "@/lib/api";
import { usePersistentTablePagination } from "@/hooks/usePersistentTablePagination";
import { useResetPageOnChange } from "@/hooks/useResetPageOnChange";

type CommentUser = {
  user_id: string;
  first_name: string;
  last_name: string;
  email: string;
  profile_image?: string | null;
};

type CommentReply = {
  id: number;
  content: string;
  hidden_at: string | null;
  created_at: string;
  likes_count: number;
  pending_reports_count: number;
  user?: CommentUser;
};

type ArticleComment = CommentReply & {
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

type LaravelPagination<T> = {
  data: T[];
  last_page: number;
  total: number;
  article_total: number;
};

type ArticleGroupRow = {
  comments: ArticleComment[];
  rowNumber: number;
};

const visibilityOptions = [
  { label: "ทุกสถานะ", value: "all" },
  { label: "แสดงอยู่", value: "visible" },
  { label: "ถูกซ่อน", value: "hidden" },
];

const reportOptions = [
  { label: "ทุกรายงาน", value: "all" },
  { label: "มีรายงานรอตรวจสอบ", value: "reported" },
  { label: "ไม่มีรายงาน", value: "unreported" },
];

const sortOptions = [
  { label: "แสดงความคิดเห็นล่าสุด", value: "latest_comment" },
  { label: "รายงานล่าสุด", value: "latest_report" },
];

function plainText(html: string) {
  const document = new DOMParser().parseFromString(html, "text/html");
  return document.body.textContent?.trim() ?? "";
}

function formatDate(value: string) {
  return new Date(value).toLocaleString("th-TH", {
    dateStyle: "medium",
    timeStyle: "short",
  });
}

function CommentSkeleton() {
  return (
    <div className="flex gap-3">
      <Skeleton className="h-10 w-10 shrink-0 rounded-full" />
      <Skeleton className="h-36 min-w-0 flex-1 rounded-2xl" />
    </div>
  );
}

function ArticleDetailSkeleton() {
  return (
    <Card className="overflow-hidden p-0" aria-label="กำลังโหลดความคิดเห็น">
      <div className="border-b border-[var(--color-border)] bg-[var(--color-surface)]/50 p-5 sm:p-6">
        <div className="flex gap-4">
          <Skeleton className="h-20 w-28 shrink-0 rounded-xl" />
          <div className="min-w-0 flex-1">
            <Skeleton className="h-6 w-20 rounded-full" />
            <Skeleton className="mt-3 h-6 w-1/2" />
            <Skeleton className="mt-3 h-3.5 w-full" />
            <Skeleton className="mt-2 h-3.5 w-4/5" />
          </div>
        </div>
      </div>
      <div className="space-y-6 p-5 sm:p-6">
        <div className="flex items-center justify-between">
          <Skeleton className="h-6 w-28" />
          <Skeleton className="h-4 w-32" />
        </div>
        <CommentSkeleton />
        <CommentSkeleton />
        <CommentSkeleton />
      </div>
    </Card>
  );
}

function UserAvatar({ user }: { user?: CommentUser }) {
  const initials = `${user?.first_name?.[0] ?? ""}${user?.last_name?.[0] ?? ""}` || "U";
  return (
    <div className="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-full bg-[var(--color-primary-light)] font-semibold text-[var(--color-primary)]">
      {user?.profile_image ? (
        <img src={user.profile_image} alt="" className="h-full w-full object-cover" />
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
}: {
  comment: CommentReply;
  reply?: boolean;
  busy: boolean;
  onVisibilityChange: (comment: CommentReply) => void;
  onDelete: (comment: CommentReply) => void;
}) {
  const name = `${comment.user?.first_name ?? ""} ${comment.user?.last_name ?? ""}`.trim() || "ไม่พบผู้ใช้";
  return (
    <div className={`flex gap-3 ${reply ? "ml-8 border-l-2 border-[var(--color-primary-light)] pl-4" : ""}`}>
      <UserAvatar user={comment.user} />
      <div
        className={`min-w-0 flex-1 rounded-2xl border p-4 ${
          comment.pending_reports_count > 0
            ? "border-red-300 bg-red-50/70 shadow-sm"
            : "border-transparent bg-[var(--color-surface)]"
        }`}
      >
        <div className="flex flex-wrap items-start justify-between gap-2">
          <div>
            <div className="font-semibold text-[var(--color-text-primary)]">{name}</div>
            <div className="text-xs text-[var(--color-text-secondary)]">
              {comment.user?.email} · {formatDate(comment.created_at)}
            </div>
          </div>
          <div className="flex items-center gap-2">
            {comment.pending_reports_count > 0 && (
              <Badge variant="danger">
                <Flag className="mr-1" />
                {reply ? "ข้อความตอบกลับ" : "ความคิดเห็นหลัก"}ถูกรายงาน {comment.pending_reports_count} ครั้ง
              </Badge>
            )}
            {comment.hidden_at && <Badge variant="warning">ถูกซ่อน</Badge>}
          </div>
        </div>
        <p className="mt-3 whitespace-pre-wrap break-words text-[var(--color-text-primary)]">
          {comment.content}
        </p>
        <div className="mt-4 flex items-center gap-5 text-sm font-medium text-[var(--color-text-secondary)]">
          <span className="flex items-center gap-1.5">
            <ThumbsUp className="h-4 w-4" /> {comment.likes_count}
          </span>
          {reply && <span>ข้อความตอบกลับ</span>}
          <span className="ml-auto flex items-center gap-1">
            <Tooltip>
              <TooltipTrigger asChild>
                <span>
                  <button
                    type="button"
                    onClick={() => onVisibilityChange(comment)}
                    disabled={busy}
                    className="rounded-lg p-2 hover:bg-white disabled:opacity-50"
                    aria-label={comment.hidden_at ? "แสดงความคิดเห็น" : "ซ่อนความคิดเห็น"}
                  >
                    {comment.hidden_at ? <Eye className="h-4 w-4" /> : <EyeOff className="h-4 w-4" />}
                  </button>
                </span>
              </TooltipTrigger>
              <TooltipContent>{comment.hidden_at ? "แสดงความคิดเห็น" : "ซ่อนความคิดเห็น"}</TooltipContent>
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
          </span>
        </div>
      </div>
    </div>
  );
}

function CommentThread({
  comment,
  busyId,
  onVisibilityChange,
  onDelete,
}: {
  comment: ArticleComment;
  busyId: number | null;
  onVisibilityChange: (comment: CommentReply) => void;
  onDelete: (comment: CommentReply) => void;
}) {
  const replyReportCount = comment.replies.reduce(
    (sum, reply) => sum + reply.pending_reports_count,
    0,
  );
  const [expanded, setExpanded] = useState(replyReportCount > 0);
  return (
    <div
      className={`space-y-3 rounded-2xl ${
        replyReportCount > 0 ? "ring-2 ring-red-200 ring-offset-4" : ""
      }`}
    >
      <CommentCard
        comment={comment}
        busy={busyId === comment.id}
        onVisibilityChange={onVisibilityChange}
        onDelete={onDelete}
      />
      {comment.replies.length > 0 && (
        <button
          type="button"
          onClick={() => setExpanded((value) => !value)}
          className={`ml-14 flex items-center gap-1.5 text-sm font-semibold hover:underline ${
            replyReportCount > 0 ? "text-red-600" : "text-[var(--color-primary)]"
          }`}
        >
          {expanded ? <ChevronUp className="h-4 w-4" /> : <ChevronDown className="h-4 w-4" />}
          {replyReportCount > 0 && <Flag className="h-4 w-4 fill-red-100" />}
          {expanded ? "ซ่อนข้อความตอบกลับ" : `ดูการตอบกลับ ${comment.replies_count} รายการ`}
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
            />
          ))}
        </div>
      )}
    </div>
  );
}

export function ArticleCommentsPage() {
  const navigate = useNavigate();
  const { articleId } = useParams<{ articleId: string }>();
  const [searchParams] = useSearchParams();
  const openedFromReports = searchParams.get("from") === "reports";
  const [items, setItems] = useState<ArticleComment[]>([]);
  const [loading, setLoading] = useState(true);
  const [searchInput, setSearchInput] = useState("");
  const [search, setSearch] = useState("");
  const [visibility, setVisibility] = useState("all");
  const [reportStatus, setReportStatus] = useState("all");
  const [sortBy, setSortBy] = useState("latest_comment");
  const { page, setPage, pageSize, setPageSize } =
    usePersistentTablePagination(articleId ? "article-comments-detail" : "article-comments");
  const [lastPage, setLastPage] = useState(1);
  const [total, setTotal] = useState(0);
  const [articleTotal, setArticleTotal] = useState(0);
  const [busyId, setBusyId] = useState<number | null>(null);
  const [deleteTarget, setDeleteTarget] = useState<CommentReply | null>(null);

  useEffect(() => {
    const timer = window.setTimeout(() => {
      setPage(1);
      setSearch(searchInput.trim());
    }, 400);
    return () => window.clearTimeout(timer);
  }, [searchInput, setPage]);

  const load = useCallback(async () => {
    setLoading(true);
    try {
      const response = await api.get<LaravelPagination<ArticleComment>>("/admin/article-comments", {
        params: {
          page,
          per_page: pageSize,
          search: search || undefined,
          visibility: visibility === "all" ? undefined : visibility,
          report_status: reportStatus === "all" ? undefined : reportStatus,
          sort_by: sortBy,
          article_id: articleId,
        },
      });
      setItems(response.data.data);
      setLastPage(response.data.last_page);
      setTotal(response.data.total);
      setArticleTotal(response.data.article_total);
    } catch {
      toast.error("ไม่สามารถโหลดความคิดเห็นได้");
    } finally {
      setLoading(false);
    }
  }, [articleId, page, pageSize, reportStatus, search, sortBy, visibility]);

  useResetPageOnChange(
    setPage,
    JSON.stringify([articleId, pageSize, search, visibility, reportStatus, sortBy]),
  );

  useEffect(() => void load(), [load]);

  const articleGroups = useMemo(() => {
    const groups = new Map<string, ArticleComment[]>();
    for (const comment of items) {
      groups.set(comment.article_id, [...(groups.get(comment.article_id) ?? []), comment]);
    }
    return [...groups.values()];
  }, [items]);

  const activeFilterCount = [
    searchInput.trim(),
    visibility !== "all",
    reportStatus !== "all",
    sortBy !== "latest_comment",
  ].filter(Boolean).length;

  const articleColumns: Column<ArticleGroupRow>[] = [
    { key: "sequence", label: "ลำดับ", className: "w-20 text-center", render: (row) => row.rowNumber },
    {
      key: "article",
      label: "บทความ",
      className: "min-w-72",
      render: ({ comments: group }) => {
        const article = group[0].article;
        return <div className="flex items-center gap-3">{article?.thumbnail ? <img src={article.thumbnail} alt="" className="h-12 w-16 shrink-0 rounded-lg object-cover" /> : <div className="h-12 w-16 shrink-0 rounded-lg bg-[var(--color-surface)]" />}<span className="font-medium text-[var(--color-text-primary)]">{article?.title ?? "ไม่พบบทความ"}</span></div>;
      },
    },
    {
      key: "comments",
      label: "ความคิดเห็น",
      render: ({ comments: group }) => <Badge>{group.reduce((sum, comment) => sum + 1 + comment.replies_count, 0)} ความคิดเห็น</Badge>,
    },
    {
      key: "reports",
      label: "รายงาน",
      render: ({ comments: group }) => {
        const count = group.reduce((sum, comment) => sum + comment.pending_reports_count + comment.replies.reduce((replySum, reply) => replySum + reply.pending_reports_count, 0), 0);
        return count > 0 ? <Badge variant="danger"><Flag className="mr-1" /> {count} รอตรวจสอบ</Badge> : <Badge variant="success">ไม่มีรายงาน</Badge>;
      },
    },
    {
      key: "latest_activity",
      label: "กิจกรรมล่าสุด",
      className: "min-w-44",
      render: ({ comments: group }) => {
        const latest = group.flatMap((comment) => [comment.created_at, ...comment.replies.map((reply) => reply.created_at)]).reduce((current, value) => new Date(value) > new Date(current) ? value : current, group[0].created_at);
        return <span className="text-[var(--color-text-secondary)]">{formatDate(latest)}</span>;
      },
    },
    {
      key: "actions",
      label: "",
      className: "w-24 text-right",
      render: ({ comments: group }) => <Button size="sm" variant="outline" onClick={() => navigate(`/articles/comments/${group[0].article_id}`)}><Eye className="h-4 w-4" /> ดู</Button>,
    },
  ];

  const updateVisibility = async (comment: CommentReply) => {
    setBusyId(comment.id);
    try {
      await api.patch(`/admin/article-comments/${comment.id}/visibility`, {
        hidden: !comment.hidden_at,
      });
      toast.success(comment.hidden_at ? "แสดงความคิดเห็นแล้ว" : "ซ่อนความคิดเห็นแล้ว");
      await load();
    } catch {
      toast.error("จัดการความคิดเห็นไม่สำเร็จ");
    } finally {
      setBusyId(null);
    }
  };

  const deleteComment = async () => {
    if (!deleteTarget) return;
    setBusyId(deleteTarget.id);
    try {
      await api.delete(`/admin/article-comments/${deleteTarget.id}`);
      toast.success("ลบความคิดเห็นแล้ว");
      setDeleteTarget(null);
      await load();
    } catch {
      toast.error("ลบความคิดเห็นไม่สำเร็จ");
    } finally {
      setBusyId(null);
    }
  };

  return (
    <div className="space-y-5">
      <div>
        {articleId && (
          <Button
            variant="ghost"
            size="sm"
            className="mb-3"
            onClick={() =>
              navigate(
                openedFromReports
                  ? "/articles/comment-reports"
                  : "/articles/comments",
              )
            }
          >
            <ArrowLeft className="h-4 w-4" />
            {openedFromReports ? "กลับหน้ารายงาน" : "กลับไปรายการบทความ"}
          </Button>
        )}
        <h1 className="text-xl font-semibold text-[var(--color-text-primary)]">
          {articleId ? "จัดการความคิดเห็น" : "ความคิดเห็นบทความ"}
        </h1>
        {/* <p className="mt-1 text-sm text-[var(--color-text-secondary)]">
          {articleId
            ? "ตรวจสอบบทสนทนาและจัดการความคิดเห็นที่ถูกรายงาน"
            : "เลือกบทความเพื่อดูและจัดการความคิดเห็นทั้งหมด"}
        </p> */}
      </div>

      <Card className="p-3 sm:p-4">
        <div className="flex flex-col gap-2.5 sm:flex-row sm:items-end sm:gap-3">
          <div className="min-w-0 flex-1">
            <SearchBar
              value={searchInput}
              onChange={setSearchInput}
              placeholder="ค้นหาบทความ ความคิดเห็น ชื่อ หรืออีเมล..."
            />
          </div>
          <div className="grid grid-cols-2 items-end gap-2 sm:flex sm:items-end sm:gap-3">
          <SimpleSelect
            label="สถานะการแสดงผล"
            value={visibility}
            onChange={(value) => {
              setVisibility(value);
              setPage(1);
            }}
            options={visibilityOptions}
            placeholder="สถานะการแสดงผล"
            className="min-w-0 flex-1 sm:w-40 sm:flex-initial"
          />
          <SimpleSelect
            label="สถานะรายงาน"
            value={reportStatus}
            onChange={(value) => {
              setReportStatus(value);
              setPage(1);
            }}
            options={reportOptions}
            placeholder="สถานะรายงาน"
            className="min-w-0 flex-1 sm:w-52 sm:flex-initial"
          />
          <div className="col-span-2 flex items-end gap-2 sm:contents">
          <SimpleSelect
            label="เรียงตาม"
            value={sortBy}
            onChange={(value) => {
              setSortBy(value);
              setPage(1);
            }}
            options={sortOptions}
            placeholder="เรียงตาม"
            className="min-w-0 flex-1 sm:w-52 sm:flex-initial"
          />
          <button
            type="button"
            disabled={activeFilterCount === 0}
            onClick={() => {
              setSearchInput("");
              setSearch("");
              setVisibility("all");
              setReportStatus("all");
              setSortBy("latest_comment");
              setPage(1);
            }}
            className="filter-clear-button group relative flex h-9 w-9 shrink-0 items-center justify-center self-end rounded-full border border-transparent text-[var(--color-text-secondary)] transition-colors hover:bg-red-50 hover:text-red-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-primary)] focus-visible:ring-offset-1 disabled:cursor-not-allowed disabled:opacity-30"
            aria-label="ล้างตัวกรอง"
            title="ล้างตัวกรอง"
          >
            <X className="h-4 w-4" />
            {activeFilterCount > 0 && (
              <span className="absolute -right-1 -top-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-[var(--color-primary)] px-1 text-[10px] font-semibold leading-none text-white group-hover:bg-red-600">
                {activeFilterCount}
              </span>
            )}
          </button>
          </div>
          </div>
        </div>
      </Card>

      {loading ? (
        articleId ? (
          <ArticleDetailSkeleton />
        ) : (
          <Card className="p-0">
            <TableSkeleton
              columns={5}
              rows={8}
              columnWidths={["w-48", "w-20", "w-24", "w-36", "w-20"]}
            />
          </Card>
        )
      ) : articleGroups.length === 0 ? (
        <Card className="flex flex-col items-center gap-2 p-10 text-center text-[var(--color-text-secondary)]">
          <MessageCircle className="h-9 w-9" />
          <p>ไม่พบความคิดเห็น</p>
        </Card>
      ) : !articleId ? (
        <Card className="overflow-hidden p-0">
          <DataTable
            columns={articleColumns}
            data={articleGroups.map((comments, index) => ({
              comments,
              rowNumber: (page - 1) * 20 + index + 1,
            }))}
            keyExtractor={(row) => row.comments[0].article_id}
            emptyMessage="ไม่พบความคิดเห็นบทความ"
          />
          <div className="hidden">
            <table className="w-full text-sm">
              <thead className="bg-[var(--color-surface)] text-left">
                <tr>
                  <th className="p-4">บทความ</th>
                  <th className="p-4">ความคิดเห็น</th>
                  <th className="p-4">รายงาน</th>
                  <th className="p-4">กิจกรรมล่าสุด</th>
                  <th className="p-4 text-right">การจัดการ</th>
                </tr>
              </thead>
              <tbody>
                {articleGroups.map((comments) => {
                  const article = comments[0].article;
                  const reportCount = comments.reduce(
                    (sum, comment) =>
                      sum +
                      comment.pending_reports_count +
                      comment.replies.reduce(
                        (replySum, reply) =>
                          replySum + reply.pending_reports_count,
                        0,
                      ),
                    0,
                  );
                  const commentCount = comments.reduce(
                    (sum, comment) => sum + 1 + comment.replies_count,
                    0,
                  );
                  const latestActivity = comments.reduce(
                    (latest, comment) => {
                      const dates = [
                        comment.created_at,
                        ...comment.replies.map((reply) => reply.created_at),
                      ];
                      return dates.reduce(
                        (current, value) =>
                          new Date(value) > new Date(current) ? value : current,
                        latest,
                      );
                    },
                    comments[0].created_at,
                  );
                  return (
                    <tr
                      key={comments[0].article_id}
                      className="border-t border-[var(--color-border)]"
                    >
                      <td className="min-w-72 p-4">
                        <div className="flex items-center gap-3">
                          {article?.thumbnail ? (
                            <img
                              src={article.thumbnail}
                              alt=""
                              className="h-12 w-16 shrink-0 rounded-lg object-cover"
                            />
                          ) : (
                            <div className="h-12 w-16 shrink-0 rounded-lg bg-[var(--color-surface)]" />
                          )}
                          <div>
                            <div className="font-medium text-[var(--color-text-primary)]">
                              {article?.title ?? "ไม่พบบทความ"}
                            </div>
                            <div className="mt-1 font-mono text-xs text-[var(--color-text-secondary)]">
                              {comments[0].article_id}
                            </div>
                          </div>
                        </div>
                      </td>
                      <td className="p-4">
                        <Badge>{commentCount} ความคิดเห็น</Badge>
                      </td>
                      <td className="p-4">
                        {reportCount > 0 ? (
                          <Badge variant="danger">
                            <Flag className="mr-1" /> {reportCount} รอตรวจสอบ
                          </Badge>
                        ) : (
                          <Badge variant="success">ไม่มีรายงาน</Badge>
                        )}
                      </td>
                      <td className="min-w-44 p-4 text-[var(--color-text-secondary)]">
                        {formatDate(latestActivity)}
                      </td>
                      <td className="p-4 text-right">
                        <Button
                          size="sm"
                          variant="outline"
                          onClick={() =>
                            navigate(
                              `/articles/comments/${comments[0].article_id}`,
                            )
                          }
                        >
                          <Eye className="h-4 w-4" /> ดูความคิดเห็น
                        </Button>
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>
        </Card>
      ) : (
        articleGroups.map((comments) => {
          const article = comments[0].article;
          return (
            <Card key={comments[0].article_id} className="overflow-hidden p-0">
              <div className="border-b border-[var(--color-border)] bg-[var(--color-surface)]/50 p-5 sm:p-6">
                <div className="flex gap-4">
                  {article?.thumbnail && (
                    <img src={article.thumbnail} alt="" className="h-20 w-28 shrink-0 rounded-xl object-cover" />
                  )}
                  <div className="min-w-0">
                    <Badge variant="primary">บทความ</Badge>
                    <h2 className="mt-2 text-lg font-semibold text-[var(--color-text-primary)]">
                      {article?.title ?? "ไม่พบบทความ"}
                    </h2>
                    <p className="mt-2 line-clamp-3 text-sm leading-6 text-[var(--color-text-secondary)]">
                      {plainText(article?.content ?? "")}
                    </p>
                  </div>
                </div>
              </div>
              <div className="space-y-6 p-5 sm:p-6">
                <div className="flex items-center justify-between">
                  <h3 className="text-lg font-semibold text-[var(--color-text-primary)]">ความคิดเห็น</h3>
                  <span className="text-sm text-[var(--color-text-secondary)]">{comments.length} ความคิดเห็นหลัก</span>
                </div>
                {comments.map((comment) => (
                  <CommentThread
                    key={comment.id}
                    comment={comment}
                    busyId={busyId}
                    onVisibilityChange={(value) => void updateVisibility(value)}
                    onDelete={setDeleteTarget}
                  />
                ))}
              </div>
            </Card>
          );
        })
      )}

      {!loading && articleGroups.length > 0 && (
        <div className="mt-4">
          <Pagination
            current={page}
            total={lastPage}
            totalItems={articleId ? total : articleTotal}
            itemLabel={articleId ? "ความคิดเห็นหลัก" : "บทความ"}
            onChange={setPage}
            pageSize={pageSize}
            onPageSizeChange={setPageSize}
          />
        </div>
      )}
      <AlertDialog
        open={!!deleteTarget}
        onOpenChange={(open) => !open && setDeleteTarget(null)}
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
              onClick={() => void deleteComment()}
            >
              ลบความคิดเห็น
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </div>
  );
}
