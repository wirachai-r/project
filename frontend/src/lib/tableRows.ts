export function withRowNumbers<T>(data: T[], start: number): T[] {
  return data.map((row, index) =>
    Object.assign({}, row, { __rowNumber: start + index }),
  );
}
