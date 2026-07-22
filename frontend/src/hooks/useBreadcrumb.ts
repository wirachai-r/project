import { useEffect } from "react";
import { useBreadcrumbStore } from "../stores/breadcrumbStore";

/**
 * ให้หน้า (page) เพิ่ม breadcrumb ต่อท้าย path เดิมที่ derive จาก route
 * ตัวอย่าง: useBreadcrumb(disease ? ["แก้ไข", disease.disease_name] : null)
 * ผลลัพธ์: ข้อมูลการวินิจฉัย / โรค / รายการโรค / แก้ไข / ไข้หวัด
 *
 * ส่ง null/undefined = ยังไม่พร้อม (เช่นรอโหลดข้อมูล) จะไม่เพิ่มอะไร
 * component unmount จะ clear ให้อัตโนมัติ กัน breadcrumb ค้างข้ามหน้า
 */
export function useBreadcrumb(crumbs: string[] | null | undefined) {
  const setExtra = useBreadcrumbStore((s) => s.setExtra);
  const clearExtra = useBreadcrumbStore((s) => s.clearExtra);

  useEffect(() => {
    if (crumbs && crumbs.length > 0) {
      setExtra(crumbs);
    }
    return () => clearExtra();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [JSON.stringify(crumbs)]);
}