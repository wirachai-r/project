import { StrictMode } from "react";
import { createRoot } from "react-dom/client";
import { GoogleOAuthProvider } from "@react-oauth/google"; // 1. import เพิ่ม
import App from "./App";
import "./index.css";

// 2. ดึงค่า Client ID จากไฟล์ .env
const GOOGLE_CLIENT_ID = import.meta.env.VITE_GOOGLE_CLIENT_ID || "";

createRoot(document.getElementById("root")!).render(
  <StrictMode>
    <GoogleOAuthProvider clientId={GOOGLE_CLIENT_ID}> {/* 3. ครอบตรงนี้ */}
      <App />
    </GoogleOAuthProvider>
  </StrictMode>
);