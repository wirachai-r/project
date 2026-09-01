import { api, queryGet } from "@/lib/api";
import { resourceKeys } from "@/lib/queryClient";
import type { BodyAreaGroup, BodyAreaGroupForm } from "@/types/bodyAreaGroup";

const toFormData = (form: BodyAreaGroupForm, isUpdate = false) => {
  const data = new FormData();
  data.append("name", form.name.trim());
  if (form.name_en.trim()) data.append("name_en", form.name_en.trim());
  if (form.description.trim()) data.append("description", form.description.trim());
  data.append("display_order", String(form.display_order));
  data.append("status", form.status);
  form.symptom_ids.forEach((id) => data.append("symptom_ids[]", id));
  form.subgroups.forEach((subgroup, index) => {
    if (subgroup.id) data.append(`subgroups[${index}][id]`, String(subgroup.id));
    data.append(`subgroups[${index}][name]`, subgroup.name.trim());
    if ((subgroup.name_en ?? "").trim()) data.append(`subgroups[${index}][name_en]`, (subgroup.name_en ?? "").trim());
    if ((subgroup.description ?? "").trim()) data.append(`subgroups[${index}][description]`, (subgroup.description ?? "").trim());
    if (subgroup.image) data.append(`subgroups[${index}][image]`, subgroup.image);
    if (subgroup.remove_image) data.append(`subgroups[${index}][remove_image]`, "1");
    data.append(`subgroups[${index}][display_order]`, String(index));
    data.append(`subgroups[${index}][status]`, subgroup.status);
    subgroup.symptom_ids.forEach((id) => data.append(`subgroups[${index}][symptom_ids][]`, id));
  });
  if (form.image) data.append("image", form.image);
  if (isUpdate) data.append("_method", "PUT");
  return data;
};

export const bodyAreaGroupApi = {
  list: () => queryGet<{ data: BodyAreaGroup[] }>(resourceKeys("body-area-groups").lists(), "/admin/body-area-groups", {}, 10 * 60_000).then((r) => r.data),
  create: (form: BodyAreaGroupForm) => api.post("/admin/body-area-groups", toFormData(form)),
  update: (id: number, form: BodyAreaGroupForm) => api.post(`/admin/body-area-groups/${id}`, toFormData(form, true)),
  updateStatus: (id: number, status: "1" | "2") => api.patch(`/admin/body-area-groups/${id}/status`, { status }),
  delete: (id: number) => api.delete(`/admin/body-area-groups/${id}`),
  reorder: (ids: number[]) => api.patch("/admin/body-area-groups/reorder", { ids }),
};
