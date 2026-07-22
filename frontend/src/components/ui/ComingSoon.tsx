// components/ui/ComingSoon.tsx
import { Construction } from "lucide-react";
import { cn } from "../../lib/utils";
import { PlaceholderPanel } from "./PlaceholderPanel";

interface ComingSoonProps {
  title?: string;
  description?: string;
  className?: string;
}

export function ComingSoon({
  title = "กำลังพัฒนา",
  description = "หน้านี้อยู่ระหว่างการพัฒนา",
  className,
}: ComingSoonProps) {
  return (
    <PlaceholderPanel
      icon={<Construction />}
      title={title}
      description={description}
      bordered
      className={cn("h-96", className)}
    />
  );
}