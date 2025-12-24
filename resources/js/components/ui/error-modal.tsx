import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
  DialogFooter,
} from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import { AlertCircle, XCircle } from "lucide-react";

interface ErrorModalProps {
  isOpen: boolean;
  onClose: () => void;
  title?: string;
  message: string;
  status?: number | null; // استلام الـ status
}

export function ErrorModal({ isOpen, onClose, title = "Error", message, status }: ErrorModalProps) {

  // منطق اختيار الرسالة والعنوان بناءً على الـ status
  let displayTitle = title;
  let displayMessage = message;

  if (status === 500) {
    displayTitle = "Unsupported Ticket";
    displayMessage = "This ticket type is currently not supported.\n\n" +
                     "Please contact support at:\nMina.Harby@xceedcc.com\n\n" +
                     "Attach a screenshot and the Ticket ID.";
  } else if (status === 403) {
    displayTitle = "Access Denied";
    displayMessage = "You don't have permission to access this resource.";
  } else if (status === 419) {
    displayTitle = "Session Expired";
    displayMessage = "Your session has expired. Please refresh the page.";
  }

  return (
    <Dialog open={isOpen} onOpenChange={onClose}>
      <DialogContent className="sm:max-w-[400px] border-red-200">
        <DialogHeader className="flex flex-col items-center justify-center pt-4">
          <div className="bg-red-100 p-3 rounded-full mb-4">
            <XCircle className="w-10 h-10 text-red-600" />
          </div>
          <DialogTitle className="text-xl font-bold text-red-700">
            {displayTitle}
          </DialogTitle>
        </DialogHeader>

        <div className="text-center py-2 text-muted-foreground font-medium whitespace-pre-line">
            {displayMessage}
        </div>

        <DialogFooter className="sm:justify-center mt-4">
          <Button variant="destructive" onClick={onClose} className="px-8 font-bold">
            Dismiss
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
