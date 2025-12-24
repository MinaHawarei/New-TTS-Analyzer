import { Loader2 } from "lucide-react";

export default function FullScreenLoader({ text }: { text?: string }) {
  return (
    <div className="fixed inset-0 z-50 bg-background/80 backdrop-blur-sm
                    flex items-center justify-center pointer-events-auto">
      <div className="flex flex-col items-center gap-4">
        <Loader2 className="w-10 h-10 animate-spin text-primary" />
        <p className="text-sm text-muted-foreground">
          {text || "Processing analysis…"}
        </p>
      </div>
    </div>
  );
}
