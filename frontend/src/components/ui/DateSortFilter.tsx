import { SimpleSelect } from "./SimpleSelect";

interface DateSortFilterProps {
  sortKey: string | null;
  direction: "asc" | "desc" | null;
  dateSortKey: string;
  nameSortKey?: string;
  onChange: (key: string, direction: "asc" | "desc") => void;
}

const DATE_SORT_OPTIONS = [
  { label: "ใหม่ไปเก่า", value: "desc" },
  { label: "เก่าไปใหม่", value: "asc" },
];

export function DateSortFilter({
  sortKey,
  direction,
  dateSortKey,
  nameSortKey,
  onChange,
}: DateSortFilterProps) {
  const options = nameSortKey
    ? [
        ...DATE_SORT_OPTIONS,
        { label: "ชื่อ ก–ฮ", value: "name_asc" },
        { label: "ชื่อ ฮ–ก", value: "name_desc" },
      ]
    : DATE_SORT_OPTIONS;
  const value =
    nameSortKey && sortKey === nameSortKey
      ? `name_${direction ?? "asc"}`
      : sortKey === dateSortKey && direction === "asc"
        ? "asc"
        : "desc";

  const handleChange = (nextValue: string) => {
    if (nextValue === "name_asc" || nextValue === "name_desc") {
      onChange(nameSortKey ?? dateSortKey, nextValue === "name_asc" ? "asc" : "desc");
      return;
    }
    onChange(dateSortKey, nextValue as "asc" | "desc");
  };

  return (
    <div className="mr-12 flex w-[calc(100%_-_3rem)] shrink-0 justify-end sm:w-auto">
      <SimpleSelect
        label="เรียงตาม"
        value={value}
        onChange={handleChange}
        options={options}
        className="w-full sm:w-44"
      />
    </div>
  );
}
