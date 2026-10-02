const graphemeSegmenter =
  typeof Intl !== "undefined" && "Segmenter" in Intl
    ? new Intl.Segmenter("th", { granularity: "grapheme" })
    : null;

export function normalizeSearchText(value: string): string {
  return value
    .normalize("NFKC")
    .replace(/[\u200B-\u200D\u2060\uFEFF]/gu, "")
    .trim()
    .replace(/\s+/gu, " ")
    .toLocaleLowerCase("th");
}

function graphemes(value: string): string[] {
  const normalized = normalizeSearchText(value);
  return graphemeSegmenter
    ? Array.from(graphemeSegmenter.segment(normalized), ({ segment }) => segment)
    : Array.from(normalized);
}

const graphemeBase = (value: string): string => Array.from(value)[0] ?? "";

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
  const minimumLength = Math.max(1, needle.length - maximum);
  const maximumLength = needle.length + maximum;
  for (let length = minimumLength; length <= maximumLength; length += 1) {
    for (let start = 0; start + length <= haystack.length; start += 1) {
      if (
        needle.length === 2 &&
        (length !== needle.length ||
          haystack
            .slice(start, start + length)
            .some((grapheme, index) =>
              graphemeBase(grapheme) !== graphemeBase(needle[index])))
      ) continue;
      if (damerauLevenshtein(haystack.slice(start, start + length), needle, maximum) <= maximum) {
        return true;
      }
    }
  }
  return false;
}

function searchTerms(query: string): string[] {
  const terms: string[] = [];
  const pattern = /["“”']([^"“”']+)["“”']|([^\s]+)/gu;

  for (const match of query.matchAll(pattern)) {
    const term = normalizeSearchText(match[1] ?? match[2] ?? "");
    if (term) terms.push(term);
  }

  return terms;
}

function matchesTerm(haystack: string, tokens: string[], term: string): boolean {
  if (haystack.includes(term)) return true;

  if (graphemes(term).length <= 1) return false;

  return tokens.some((token) => approximatelyContains(token, term)) ||
    approximatelyContains(haystack, term);
}

export function fuzzyIncludes(value: string, query: string): boolean {
  const haystack = normalizeSearchText(value);
  const needle = normalizeSearchText(query);
  if (!needle || haystack.includes(needle)) return true;

  const tokens = haystack.split(" ").filter(Boolean);
  const compactThaiNeedle = /^[\p{Script=Thai}\s]+$/u.test(needle)
    ? needle.replace(/\s+/gu, "")
    : needle;
  if (
    compactThaiNeedle !== needle &&
    (haystack.includes(compactThaiNeedle) ||
      matchesTerm(haystack, tokens, compactThaiNeedle))
  ) {
    return true;
  }
  const terms = searchTerms(needle);

  // Space-separated terms use AND matching in any order. For example,
  // "ไข้ สูง" matches a value containing both "ไข้" and "สูง", even when
  // other words appear between them. Quoted text remains a single phrase.
  return terms.length > 0 && terms.every((term) => matchesTerm(haystack, tokens, term));
}
