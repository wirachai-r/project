import Hashids from "hashids";

const SALT = import.meta.env.VITE_ID_HASH_SALT ?? "checkup-disease-salt";
const MIN_LENGTH = 10;

const hashids = new Hashids(SALT, MIN_LENGTH);

export function encodeId(id: string): string {
  const numeric = Number(id);
  if (Number.isNaN(numeric)) return id;
  return hashids.encode(numeric);
}

export function decodeId(encoded: string, length = 10): string | null {
  const decoded = hashids.decode(encoded);
  if (!decoded.length) return null;
  return String(decoded[0]).padStart(length, "0");
}