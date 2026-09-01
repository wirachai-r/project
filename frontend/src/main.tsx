import { StrictMode } from "react";
import { createRoot } from "react-dom/client";
import { GoogleOAuthProvider } from "@react-oauth/google"; // 1. import เพิ่ม
import { QueryClientProvider } from "@tanstack/react-query";
import App from "./App";
import "./index.css";
import { queryClient } from "@/lib/queryClient";

// 2. ดึงค่า Client ID จากไฟล์ .env
const GOOGLE_CLIENT_ID = import.meta.env.VITE_GOOGLE_CLIENT_ID || "";

createRoot(document.getElementById("root")!).render(
  <StrictMode>
    <QueryClientProvider client={queryClient}>
      <GoogleOAuthProvider clientId={GOOGLE_CLIENT_ID}> {/* 3. ครอบตรงนี้ */}
        <App />
      </GoogleOAuthProvider>
    </QueryClientProvider>
  </StrictMode>
);
