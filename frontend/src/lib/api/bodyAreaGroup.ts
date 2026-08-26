import { api } from "@/lib/api";
import type { BodyAreaGroup, BodyAreaGroupForm } from "@/types/bodyAreaGroup";

const toFormData = (form: BodyAreaGroupForm, isUpdate = false) => {
  const data = new FormData();
  data.append("name", form.name);
  data.append("name_en", form.name_en);
  data.append("description", form.description);
  data.append("display_order", String(form.display_order));
  data.append("status", form.status);
  form.symptom_ids.forEach((id) => data.append("symptom_ids[]", id));
  if (form.image) data.append("image", form.image);
  if (isUpdate) data.append("_method", "PUT");
  return data;
};

export const bodyAreaGroupApi = {
  list: () => api.get<{ data: BodyAreaGroup[] }>("/admin/body-area-groups").then((r) => r.data.data),
  create: (form: BodyAreaGroupForm) => api.post("/admin/body-area-groups", toFormData(form)),
  update: (id: number, form: BodyAreaGroupForm) => api.post(`/admin/body-area-groups/${id}`, toFormData(form, true)),
  delete: (id: number) => api.delete(`/admin/body-area-groups/${id}`),
  reorder: (ids: number[]) => api.patch("/admin/body-area-groups/reorder", { ids }),
};
