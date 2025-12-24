import { Skeleton } from "@/components/ui/skeleton"

export function AnalysisSkeleton() {
    return (
        <div className="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-4 animate-pulse">
            {[...Array(8)].map((_, i) => (
                <div key={i} className="space-y-2 py-1">
                    {/* تحاكي الـ Label الصغير */}
                    <Skeleton className="h-3 w-20 bg-muted" />
                    {/* تحاكي القيمة */}
                    <Skeleton className="h-5 w-full bg-muted/60 rounded-md" />
                </div>
            ))}
            {/* تحاكي قسم الـ History في الأسفل */}
            <div className="col-span-full mt-4 space-y-2">
                <Skeleton className="h-10 w-full rounded-lg bg-muted" />
            </div>
        </div>
    )
}
