const graphemeSegmenter =
  typeof Intl !== "undefined" && "Segmenter" in Intl
    ? new Intl.Segmenter("th", { granularity: "grapheme" })
    : null;

export function normalizeSearchText(value: string): string {
  return value.normalize("NFC").trim().replace(/\s+/gu, " ").toLocaleLowerCase("th");
}

function graphemes(value: string): string[] {
  const normalized = normalizeSearchText(value);
  return graphemeSegmenter
    ? Array.from(graphemeSegmenter.segment(normalized), ({ segment }) => segment)
    : Array.from(normalized);
}

function threshold(length: number): number {
  if (length <= 1) return 0;
  if (length <= 5) return 1;
  if (length <= 10) return 2;
  return Math.max(2, Math.floor(length * 0.2));
}

function damerauLevenshtein(left: string[], right: string[], maximum: number): number {
  if (Math.abs(left.length - right.length) > maximum) return maximum + 1;
  let previousPrevious: number[] | undefined;
  let previous = Array.from({ length: right.length + 1 }, (_, index) => index);
  for (let i = 1; i <= left.length; i += 1) {
    const current = [i];
    let rowMinimum = i;
    for (let j = 1; j <= right.length; j += 1) {
      const cost = left[i - 1] === right[j - 1] ? 0 : 1;
      current[j] = Math.min(current[j - 1] + 1, previous[j] + 1, previous[j - 1] + cost);
      if (
        previousPrevious && i > 1 && j > 1 &&
        left[i - 1] === right[j - 2] && left[i - 2] === right[j - 1]
      ) {
        current[j] = Math.min(current[j], previousPrevious[j - 2] + 1);
      }
      rowMinimum = Math.min(rowMinimum, current[j]);
    }
    if (rowMinimum > maximum) return maximum + 1;
    previousPrevious = previous;
    previous = current;
  }
  return previous[right.length];
}

function approximatelyContains(value: string, query: string): boolean {
  const haystack = graphemes(value);
  const needle = graphemes(query);
  const maximum = threshold(needle.length);
  if (maximum === 0 || needle.length > 64) return false;
  if (needle.length === 2) {
    const queryPoints = Array.from(normalizeSearchText(query));
    const valuePoints = Array.from(normalizeSearchText(value));
    return valuePoints.some((point, index) =>
      point === queryPoints[0] && valuePoints.slice(index + 1, index + 3).includes(queryPoints[1]),
    );
  }
  for (let length = Math.max(1, needle.length - maximum); length <= needle.length + maximum; length += 1) {
    for (let start = 0; start + length <= haystack.length; start += 1) {
      if (damerauLevenshtein(haystack.slice(start, start + length), needle, maximum) <= maximum) {
        return true;
      }
    }
  }
  return false;
}

export function fuzzyIncludes(value: string, query: string): boolean {
  const haystack = normalizeSearchText(value);
  const needle = normalizeSearchText(query);
  if (!needle || haystack.includes(needle)) return true;
  return haystack.split(" ").some((token) => approximatelyContains(token, needle)) ||
    approximatelyContains(haystack, needle);
}
