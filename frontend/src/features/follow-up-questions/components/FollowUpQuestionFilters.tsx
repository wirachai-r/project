import { X } from "lucide-react";
import { Card } from "@/components/ui/Card";
import { FilterBar } from "@/components/ui/FilterBar";
import { SearchBar } from "@/components/ui/SearchBar";
import { MultiSelectFilter } from "@/components/ui/MultiSelectFilter";
import { SimpleSelect } from "@/components/ui/SimpleSelect";
import {
  Tooltip,
  TooltipContent,
  TooltipTrigger,
} from "@/components/ui/Tooltip";
import { FOLLOW_UP_ANSWER_TYPES } from "../constants";

type Props = {
  search: string;
  status: string;
  answerTypes: string[];
  sortKey: string | null;
  sortDirection: "asc" | "desc" | null;
  onSearchChange: (value: string) => void;
  onStatusChange: (value: string) => void;
  onAnswerTypesChange: (values: string[]) => void;
  onSortChange: (key: string | null, direction: "asc" | "desc" | null) => void;
  onClear: () => void;
};

export function FollowUpQuestionFilters(props: Props) {
  const activeCount = [props.search, props.status, props.answerTypes.length > 0].filter(
    Boolean,
  ).length;
  return (
    <FilterBar
      sortKey={props.sortKey}
      direction={props.sortDirection}
      dateSortKey="created_at"
      nameSortKey="question"
      onSortChange={props.onSortChange}
    >
      <Card className="p-3 sm:p-4">
        <div className="flex flex-col gap-2.5 sm:flex-row sm:items-center sm:gap-3">
          <div className="min-w-0 flex-1">
            <SearchBar
              value={props.search}
              onChange={props.onSearchChange}
              placeholder="ค้นหาคำถาม..."
            />
          </div>
          <MultiSelectFilter
            label="รูปแบบคำตอบ"
            values={props.answerTypes}
            onChange={props.onAnswerTypesChange}
            options={FOLLOW_UP_ANSWER_TYPES}
            className="sm:w-52"
          />
          <SimpleSelect
            label="สถานะ"
            placeholder="ทุกสถานะ"
            value={props.status}
            onChange={props.onStatusChange}
            options={[
              { value: "", label: "ทุกสถานะ" },
              { value: "1", label: "ใช้งาน" },
              { value: "0", label: "ปิดใช้งาน" },
            ]}
            className="sm:w-40"
          />
          <Tooltip>
            <TooltipTrigger asChild>
              <button
                type="button"
                onClick={props.onClear}
                disabled={activeCount === 0}
                aria-label="ล้างตัวกรอง"
                className={`filter-clear-button group relative flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-transparent transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-primary)] ${activeCount ? "text-[var(--color-text-secondary)] hover:bg-red-50 hover:text-red-600" : "cursor-not-allowed text-[var(--color-border)]"}`}
              >
                <X className="h-4 w-4" />
                {activeCount > 0 && (
                  <span className="absolute -right-1 -top-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-[var(--color-primary)] px-1 text-[10px] font-semibold text-white">
                    {activeCount}
                  </span>
                )}
              </button>
            </TooltipTrigger>
            <TooltipContent>ล้างตัวกรอง</TooltipContent>
          </Tooltip>
        </div>
      </Card>
    </FilterBar>
  );
}
