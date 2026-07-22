import { isAxiosError } from "axios";

export function getErrorMessage(err: unknown): string {
  if (isAxiosError(err)) {
    const data = err.response?.data as
      | { message?: string; errors?: Record<string, string[]> }
      | undefined;

    // ถ้ามี field errors ให้เอา error แรกของ field แรกมาโชว์ (ชัดเจนกว่า message รวม)
    if (data?.errors) {
      const firstField = Object.values(data.errors)[0];
      if (firstField?.[0]) return firstField[0];
    }

    if (data?.message) return data.message;

    if (err.response?.status === 422) return "ข้อมูลไม่ถูกต้อง กรุณาตรวจสอบอีกครั้ง";
    if (err.response?.status === 401) return "กรุณาเข้าสู่ระบบใหม่อีกครั้ง";
    if (err.response?.status === 403) return "คุณไม่มีสิทธิ์ทำรายการนี้";
    if (err.response?.status === 404) return "ไม่พบข้อมูลที่ต้องการ";
    if (err.response?.status && err.response.status >= 500)
      return "เซิร์ฟเวอร์มีปัญหา กรุณาลองใหม่ภายหลัง";
    if (err.code === "ERR_NETWORK") return "เชื่อมต่อเซิร์ฟเวอร์ไม่ได้ กรุณาตรวจสอบอินเทอร์เน็ต";
  }

  return "บันทึกข้อมูลไม่สำเร็จ กรุณาลองใหม่";
}