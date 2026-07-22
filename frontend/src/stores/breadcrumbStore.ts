import { create } from "zustand";

interface BreadcrumbState {
  extra: string[] | null;
  setExtra: (crumbs: string[]) => void;
  clearExtra: () => void;
}

export const useBreadcrumbStore = create<BreadcrumbState>((set) => ({
  extra: null,
  setExtra: (crumbs) => set({ extra: crumbs }),
  clearExtra: () => set({ extra: null }),
}));