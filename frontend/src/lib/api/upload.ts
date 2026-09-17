import { api } from "@/lib/api";

export type UploadFolder =
  | "diseases"
  | "articles"
  | "first_aids"
  | "notifications"
  | "profiles";

export const uploadApi = {
  uploadImage: (file: File, folder: UploadFolder) => {
    const formData = new FormData();
    formData.append("image", file);
    formData.append("folder", folder);
    // Do not set Content-Type here. The browser must add the multipart boundary;
    // forcing the header can make PHP receive an empty/invalid uploaded file.
    return api
      .post<{ url: string; relative_url: string; path: string }>("/uploads/image", formData)
      .then((r) => r.data);
  },

  deleteImage: (path: string) =>
    api.delete("/uploads/image", { data: { path } }),
};
