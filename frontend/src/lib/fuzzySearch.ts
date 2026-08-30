export function fuzzyIncludes(value: string, query: string): boolean {
  const haystack = value.toLocaleLowerCase("th");
  const needle = query.trim().toLocaleLowerCase("th");
  if (!needle || haystack.includes(needle)) return true;

  const characters = Array.from(needle);
  if (characters.length < 3 || characters.length > 32) return false;

  return characters.some((_, index) => {
    const left = characters.slice(0, index).join("");
    const right = characters.slice(index + 1).join("");
    const leftIndex = haystack.indexOf(left);
    return leftIndex >= 0 && haystack.indexOf(right, leftIndex + left.length) >= 0;
  });
}
