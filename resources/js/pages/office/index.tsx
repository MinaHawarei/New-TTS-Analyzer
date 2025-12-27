import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { Construction, HardHat, Hammer, Timer } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Maintenance',
        href: '/maintenance',
    },
];

export default function UnderConstruction() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Under Construction" />

            <div className="relative min-h flex flex-col items-center justify-center overflow-hidden bg-white dark:bg-slate-950 rounded-xl border border-dashed border-slate-300 dark:border-slate-800">

                {/* الأشرطة التحذيرية العلوية (Construction Stripes) */}
                <div className="absolute top-0 left-0 w-full h-8 bg-[repeating-linear-gradient(-45deg,#facc15,#facc15_20px,#000_20px,#000_40px)] opacity-90 shadow-md z-20" />

                {/* محتوى الصفحة */}
                <div className="relative z-10 text-center space-y-8 px-4 py-20"> {/* زيادة الـ padding من الأعلى */}
                    {/* أيقونة المهندس أو الخوذة مع أنيميشن نبض */}
                    <div className="relative inline-block mb-4">
                        <div className="absolute inset-0 animate-ping rounded-full bg-yellow-400/20" />
                        <div className="relative bg-yellow-400 p-6 rounded-full shadow-2xl">
                            <HardHat className="w-16 h-16 text-black" />
                        </div>
                    </div>

                    <div className="space-y-4">
                        <h1 className="text-5xl md:text-7xl font-black text-slate-900 dark:text-white tracking-tighter uppercase">
                            Under <span className="text-yellow-500">Construction</span>
                        </h1>

                        <div className="flex items-center justify-center gap-2 text-xl md:text-2xl font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.2em]">
                            <Timer className="w-6 h-6 animate-spin-slow" />
                            Completion Soon
                        </div>
                    </div>

                    {/* رسالة توضيحية */}
                    <p className="max-w-md mx-auto text-lg text-slate-600 dark:text-slate-400 leading-relaxed font-medium italic">
                        "We are currently forging the future of analytics. This module is being fine-tuned to provide you with a world-class experience."
                    </p>

                    {/* شريط التقدم الوهمي */}
                    <div className="max-w-sm mx-auto space-y-2">
                        <div className="flex justify-between text-xs font-black uppercase text-slate-500">
                            <span>System Integration</span>
                            <span>75%</span>
                        </div>
                        <div className="h-3 w-full bg-slate-200 dark:bg-slate-800 rounded-full overflow-hidden border border-slate-300 dark:border-slate-700">
                            <div className="h-full w-[75%] bg-[repeating-linear-gradient(-45deg,#facc15,#facc15_10px,#eab308_10px,#eab308_20px)] animate-progress-stripes" />
                        </div>
                    </div>

                    <div className="flex items-center justify-center gap-6 pt-4 text-slate-400">
                        <Hammer className="w-6 h-6 animate-bounce" />
                        <Construction className="w-6 h-6" />
                    </div>
                </div>

                {/* الأشرطة التحذيرية السفلية */}
                <div className="absolute bottom-0 left-0 w-full h-8 bg-[repeating-linear-gradient(-45deg,#facc15,#facc15_20px,#000_20px,#000_40px)] opacity-90 shadow-md z-20" />

                {/* نمط خلفية باهتة توحي بالمخططات الهندسية */}
                <div className="absolute inset-0 opacity-[0.03] dark:opacity-[0.05] pointer-events-none bg-[url('https://www.transparenttextures.com/patterns/blueprint.png')]" />
            </div>

            {/* إضافة الأنيميشن المطلوب في Tailwind Config أو كـ Style tag */}
            <style>{`
                @keyframes progress-stripes {
                    from { background-position: 0 0; }
                    to { background-position: 40px 0; }
                }
                .animate-progress-stripes {
                    background-size: 40px 40px;
                    animation: progress-stripes 1s linear infinite;
                }
                @keyframes spin-slow {
                    from { transform: rotate(0deg); }
                    to { transform: rotate(360deg); }
                }
                .animate-spin-slow {
                    animation: spin-slow 3s linear infinite;
                }
            `}</style>
        </AppLayout>
    );
}
