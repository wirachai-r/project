import { AlertCircle, RefreshCw } from "lucide-react";
import { Button } from "./Button";
import { PlaceholderPanel } from "./PlaceholderPanel";

interface DataLoadErrorProps {
  title?: string;
  description?: string;
  onRetry: () => void;
}

export function DataLoadError({
  title = "โหลดข้อมูลไม่สำเร็จ",
  description = "กรุณาตรวจสอบการเชื่อมต่อแล้วลองใหม่อีกครั้ง",
  onRetry,
}: DataLoadErrorProps) {
  return (
    <PlaceholderPanel
      icon={<AlertCircle />}
      title={title}
      description={description}
      action={
        <Button type="button" variant="outline" onClick={onRetry}>
          <RefreshCw className="h-4 w-4" />
          ลองใหม่
        </Button>
      }
      className="min-h-72"
    />
  );
}
