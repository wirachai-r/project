const SUPABASE_PUBLIC_UPLOAD_MARKER = "v1/object/public/uploads/";

/** Converts a public image URL into the object path expected by the API. */
export function toStoragePath(value: string | null | undefined): string {
  const normalized = value?.trim();
  if (!normalized) return "";

  // The last marker also repairs values saved with a duplicated Supabase URL.
  const supabaseMarkerIndex = normalized.lastIndexOf(
    SUPABASE_PUBLIC_UPLOAD_MARKER,
  );
  if (supabaseMarkerIndex >= 0) {
    return normalized.slice(
      supabaseMarkerIndex + SUPABASE_PUBLIC_UPLOAD_MARKER.length,
    );
  }

  const laravelStorageMarker = "/storage/";
  const storageMarkerIndex = normalized.lastIndexOf(laravelStorageMarker);
  if (storageMarkerIndex >= 0) {
    return normalized.slice(storageMarkerIndex + laravelStorageMarker.length);
  }

  return normalized.replace(/^\/+/, "");
}
