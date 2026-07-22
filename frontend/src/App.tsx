import { RouterProvider } from "react-router-dom";
import { Toaster } from "sonner";
import { TooltipProvider } from "@/components/ui/Tooltip";
import { router } from "./routes/router";
import "./App.css";

function App() {
  return (
    <TooltipProvider>
      <RouterProvider router={router} />
      <Toaster
        position="top-right"
        richColors
        toastOptions={{
          style: {
            fontFamily: "'Prompt', ui-sans-serif, system-ui, sans-serif",
          },
          classNames: {
            success: "!bg-[var(--color-success)] !text-white",
            error: "!bg-[var(--color-danger)] !text-white",
          },
        }}
      />
    </TooltipProvider>
  );
}

export default App;
