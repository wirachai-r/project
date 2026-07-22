import { api } from "@/lib/api";

export type UploadFolder = "diseases" | "articles" | "first_aids" | "profiles";

export const uploadApi = {
  uploadImage: (file: File, folder: UploadFolder) => {
    const formData = new FormData();
    formData.append("image", file);
    formData.append("folder", folder);
    return api
      .post<{ url: string; path: string }>("/uploads/image", formData, {
        headers: { "Content-Type": "multipart/form-data" },
      })
      .then((r) => r.data);
  },

  deleteImage: (path: string) =>
    api.delete("/uploads/image", { data: { path } }),
};