import FullScreenLoader from '@/components/full-screen-loader';
import { PlaceholderPattern } from '@/components/ui/placeholder-pattern';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type NavItem } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { HandleAction } from "@/components/ui/handle-action-modal";
import { ErrorModal } from "@/components/ui/error-modal";
import { useState } from 'react';

const useErrorModal = () => {
    const [error, setError] = useState({
        isOpen: false,
        title: '',
        message: '',
        status: null as number | null // إضافة الـ status هنا
    });

    const handleError = (err: any) => {
        const status = err.response?.status || null;
        const message = err.response?.data?.message || err.message || 'An error occurred';

        setError({
            isOpen: true,
            title: 'Error',
            message,
            status
        });
    };

    const closeError = () => {
        setError({ isOpen: false, title: '', message: '', status: null });
    };

    return { error, handleError, closeError };
};
import {
    Play,
    Ticket,
    Search,
    History,
    ShieldAlert,
    Info,
    CheckCircle2,
    Zap
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import axios from 'axios';
import { route } from 'ziggy-js';
import {
  Accordion,
  AccordionContent,
  AccordionItem,
  AccordionTrigger,
} from "@/components/ui/accordion";

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Home', href: '/' },
    { title: 'TTS Analyzer', href: '/tts-analyzer' },
];


export default function TTSAnalyzer({ packages }: { packages: { id: number; name: string }[] }) {
    const [analysisData, setAnalysisData] = useState<any>(null);
    const [isLoading, setIsLoading] = useState(false);
    const [wasSubmitted, setWasSubmitted] = useState(false);
    const { error, handleError, closeError } = useErrorModal();
    // استخدام useForm من Inertia للتعامل مع البيانات والـ CSRF تلقائياً
    const { data, setData, post, processing, reset } = useForm({
        tktID: '',
        inputText: '',
        selectPackage: '',
    });
    const errors = {
        tktID: wasSubmitted && !data.tktID,
        inputText: wasSubmitted && !data.inputText,
        selectPackage: wasSubmitted && !data.selectPackage,
    };

    const isFormInvalid = !data.tktID || !data.inputText || !data.selectPackage;
    const openTicketLogs = (ticketId: string) => {
        if (!ticketId || !ticketId.trim()) {
            console.warn("No Ticket ID provided to the function");
            return;
        }
        console.log("Processing Ticket ID:", ticketId);
        const url = `http://tts/new/index.php/logs/core_log/get_all_ticket_logs?ticket_id=${ticketId}`;
        window.open(url, 'TicketLogsPopup', 'width=800,height=600,resizable=yes,scrollbars=yes');
    };

    const [errorModal, setErrorModal] = useState({ isOpen: false, message: "" });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        // 1. تفعيل إظهار الأخطاء عند الضغط
        setWasSubmitted(true);

        // 2. التحقق من صحة البيانات قبل إرسال الطلب للـ API
        if (isFormInvalid) return;

        setIsLoading(true);
        setAnalysisData(null);

        axios.post(route('analyze.data'), data)
            .then((response) => {
                setAnalysisData(response.data);
                // اختياري: إذا أردت إخفاء اللون الأحمر بعد النجاح
                setWasSubmitted(false);
            })
            .catch(handleError)
            .finally(() => {
                setIsLoading(false);
            });
    };

    const [isActionModalOpen, setIsActionModalOpen] = useState(false);
    const [selectedMobileData, setSelectedMobileData] = useState<any>(null);

    const handleOpenModal = (weMobileData: any) => {
        setSelectedMobileData(weMobileData);
        setIsActionModalOpen(true);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="TTS Analyzer - Analysis" />

            <div className="container mx-auto grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

                {/* الجزء الأيسر: مدخلات البيانات */}
                <div className="lg:col-span-5">
                    <Card className="border-primary/20 shadow-lg flex flex-col h-[650px]"> {/* تحديد ارتفاع ثابت للكارد */}
                        <CardHeader className="bg-primary/5 shrink-0">
                            <CardTitle className="text-lg flex items-center gap-2 text-primary">
                                <Search className="w-5 h-5" />
                                Analysis Inputs
                            </CardTitle>
                        </CardHeader>

                        <CardContent className="pt-6 space-y-4 flex-1 flex flex-col overflow-hidden">
                            {/* الجزء العلوي: Ticket ID */}
                            <div className="flex gap-2 shrink-0">
                                <div className="relative flex-1">
                                    <Ticket className="absolute left-3 top-3 w-4 h-4 text-muted-foreground" />
                                    <Input
                                        placeholder="Ticket ID"
                                        className={`pl-9 ${errors.tktID ? "border-red-500 focus:border-red-500" : ""}`}
                                        value={data.tktID}
                                        onChange={e => setData('tktID', e.target.value)}
                                        onKeyDown={(e) => {
                                            if (e.key === 'Enter') {
                                                e.preventDefault();
                                                openTicketLogs(data.tktID);
                                            }
                                        }}
                                        onPaste={(e) => {
                                            e.preventDefault();
                                            const pastedText = e.clipboardData.getData('text').trim();

                                            setData('tktID', pastedText);

                                            openTicketLogs(pastedText);
                                        }}
                                    />

                                    {errors.tktID && (
                                    <p className="text-xs text-red-500 mt-1">Ticket ID is required</p>
                                    )}

                                </div>
                                <Button variant="secondary" onClick={() => openTicketLogs(data.tktID)}>Logs</Button>
                            </div>

                            <Textarea
                                placeholder="Paste TT item logs here..."
                                className={`flex-1 font-mono text-xs resize-none
                                    ${errors.inputText ? "border-red-500 focus:border-red-500" : ""}`}
                                value={data.inputText}
                                onChange={e => setData('inputText', e.target.value)}
                                />

                                {errors.inputText && (
                                <p className="text-xs text-red-500">Item logs are required</p>
                                )}


                            <div className="space-y-4 shrink-0 pt-2">
                                <div className="space-y-1">
                                    <label className="text-xs font-semibold uppercase text-muted-foreground">Package Selection</label>
                                    <Select onValueChange={(v) => setData('selectPackage', v)}>
                                        <SelectTrigger
                                            className={`h-9 ${errors.selectPackage ? "border-red-500" : ""}`}
                                        >
                                            <SelectValue placeholder="Choose a Package" />
                                        </SelectTrigger>

                                        <SelectContent>
                                            {packages?.map(item => (
                                            <SelectItem key={item.id} value={item.id.toString()}>
                                                {item.name}
                                            </SelectItem>
                                            ))}
                                        </SelectContent>
                                        </Select>

                                        {errors.selectPackage && (
                                        <p className="text-xs text-red-500">Package selection is required</p>
                                        )}

                                </div>

                                <div className="flex gap-2">
                                    <Button
                                        className="flex-1 bg-primary hover:bg-primary/90 shadow-md"
                                        onClick={handleSubmit}
                                        disabled={isLoading}
                                        >
                                        {isLoading ? (
                                            "Analyzing…"
                                        ) : (
                                            <>
                                            <Play className="w-4 h-4 mr-2" /> Submit Analysis
                                            </>
                                        )}
                                        </Button>

                                    <Button variant="destructive" className="px-3" onClick={() => { reset(); setAnalysisData(null); setWasSubmitted(false);}}>
                                        Reset
                                    </Button>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
                {/* الجزء الأيمن: النتائج */}
                <div className="lg:col-span-7 space-y-6">
                    <Card className="min-h-[400px] relative overflow-hidden flex flex-col border-primary/10">
                        <CardHeader className="border-b border-border/50 shrink-0">
                            <CardTitle className="text-lg flex items-center gap-2">
                                <CheckCircle2 className="w-5 h-5 text-green-500" />
                                Analysis Results
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="flex-1 overflow-y-auto p-6 bg-slate-50/30 dark:bg-slate-900/10">
                            {isLoading ? (
                                <FullScreenLoader text="Analyzing ticket data, please wait…" />
                            ) : !analysisData ? (
                                <div className="flex flex-col items-center justify-center h-full text-muted-foreground opacity-40">
                                    <PlaceholderPattern className="w-16 h-16 mb-4" />
                                    <p className="text-sm font-medium">Input data and submit to see results</p>
                                </div>
                            ) : (
                                <div className="space-y-4 animate-in fade-in zoom-in-95 duration-300">
                                    {/* المجموعة الأولى */}
                                    <div className="grid grid-cols-2 md:grid-cols-4 gap-4 bg-muted/20 p-4 rounded-lg border border-border/40">
                                        <CompactResultRow label="Problem Type" value={analysisData.problemType} bold className="md:col-span-1" />
                                        <CompactResultRow label="Escalations" value={`${analysisData.escalationtimes} times`} />
                                        <CompactResultRow label="Support Pool" value={analysisData.curuantSupportPool} isBadge />
                                        <CompactResultRow label="Status" value={analysisData.curuantStatus} color="oklch(0.65 0.15 45)" />
                                    </div>

                                    {/* المجموعة الثانية */}
                                    <div className="grid grid-cols-2 md:grid-cols-3 gap-6 px-2">
                                        <CompactResultRow label="Duration" value={analysisData.totalDuration} color="oklch(0.6 0.2 250)" bold />
                                        <CompactResultRow label="From" value={analysisData.startDate} />
                                        <CompactResultRow label="To" value={analysisData.closeDate} />
                                    </div>

                                    {/* SLA Status, SLA Value & Technical IDs */}
                                    <div className="flex flex-col md:flex-row items-stretch bg-primary/5 rounded-md border border-primary/10 overflow-hidden min-h-[4.5rem]">

                                        {/* حاوية الـ SLA: تتقسم داخلياً إذا وجد المحتويين */}
                                        <div className="flex flex-1 min-w-0">

                                            {/* الجزء الأول: SLA Status */}
                                            <div className="flex-1 px-4 py-3 flex flex-col justify-center min-w-0 border-r border-primary/5">
                                                <span className="text-[10px] text-muted-foreground uppercase font-black tracking-widest leading-none mb-1.5">
                                                    SLA Status
                                                </span>
                                                <span className="text-base font-black leading-tight break-words" style={{ color: analysisData.slaStatus_color }}>
                                                    <div dangerouslySetInnerHTML={{ __html: analysisData.slaStatus }} />
                                                </span>
                                            </div>

                                            {/* الجزء الثاني: الـ SLA (يظهر فقط إذا لم يكن null) */}
                                            {analysisData.sla && (
                                                <div className="flex-1 px-4 py-3 flex flex-col justify-center min-w-0 border-l border-primary/10 bg-primary/[0.02]">
                                                    <span className="text-[10px] text-muted-foreground uppercase font-black tracking-widest leading-none mb-1.5">
                                                        SLA
                                                    </span>
                                                    <span className="text-base font-bold text-foreground/80 leading-tight">
                                                        <div dangerouslySetInnerHTML={{ __html: analysisData.sla }} />
                                                    </span>
                                                </div>
                                            )}
                                        </div>

                                        {/* قسم المعرفات التقنية (الجانب الأيمن) */}
                                        <div className="flex flex-wrap shrink-0 items-center bg-primary/10 px-4 py-2 gap-4 border-t md:border-t-0 md:border-l border-primary/20">
                                            {analysisData.delayId && <CompactResultRow label="Delay" value={analysisData.delayId} transparent />}

                                            {[
                                                { id: analysisData.reassignId, label: "Re-Assign" },
                                                { id: analysisData.accelerationId, label: "Acceleration" },
                                                { id: analysisData.reworkId, label: "Re-Work" }
                                            ].map((item, index) => (
                                                item.id && (
                                                    <div key={index} className="flex items-center gap-4 h-full">
                                                        <div className="hidden md:block h-6 w-[1.5px] bg-primary/20" />
                                                        <CompactResultRow label={item.label} value={item.id} transparent />
                                                    </div>
                                                )
                                            ))}
                                        </div>
                                    </div>

                                    {/* History Accordion */}
                                    <Accordion type="single" collapsible className="w-full px-1">
                                        <AccordionItem value="history" className="border-none">
                                            <AccordionTrigger className="py-2 px-3 bg-muted/20 hover:bg-muted/30 rounded-md text-[12px] h-9 transition-all">
                                                <div className="flex items-center gap-2 font-bold text-primary/80">
                                                    <History className="w-4 h-4" />
                                                    Show Escalation History
                                                </div>
                                            </AccordionTrigger>
                                            <AccordionContent
                                                className="mt-2 p-0 rounded-md overflow-y-auto shadow-inner border
                                                    bg-white border-gray-200
                                                    dark:bg-slate-950 dark:border-white/5"
                                                >
                                                <div className="flex flex-col">
                                                    {Array.isArray(analysisData.esclationHistory) ? (
                                                        analysisData.esclationHistory.map((item: any, index: number) => (
                                                            <div key={index}
                                                                className="p-3 border-b last:border-none transition-colors group
                                                                    border-gray-200 hover:bg-gray-50
                                                                    dark:border-white/5 dark:hover:bg-white/[0.03]">
                                                                <div className="flex justify-between items-center mb-1.5">
                                                                    <span className="text-blue-400 font-bold text-[12px] flex items-center gap-2">
                                                                        <span className="w-1 h-1 rounded-full bg-blue-400 group-hover:scale-150 transition-transform" />
                                                                        #{item.id} — {item.support_group}
                                                                    </span>
                                                                    <span className="text-[9px] text-slate-500 font-mono font-bold uppercase tracking-widest">Step {index + 1}</span>
                                                                </div>

                                                                <div className="flex items-center flex-wrap gap-x-3 gap-y-1 text-[11px] font-mono">
                                                                {/* From */}
                                                                <div className="flex items-center gap-1.5">
                                                                    <span className="text-gray-500 text-[10px] uppercase font-bold dark:text-slate-500">
                                                                    From
                                                                    </span>
                                                                    <span className="text-gray-700 bg-gray-100 px-1.5 py-0.5 rounded
                                                                    dark:text-slate-300 dark:bg-white/5">
                                                                    {item.from}
                                                                    </span>
                                                                </div>

                                                                {/* To */}
                                                                <div className="flex items-center gap-1.5">
                                                                    <span className="text-gray-500 text-[10px] uppercase font-bold dark:text-slate-500">
                                                                    To
                                                                    </span>
                                                                    <span
                                                                    className={`px-1.5 py-0.5 rounded ${
                                                                        item.to.includes('Now')
                                                                        ? 'text-green-600 bg-green-100 animate-pulse font-bold dark:text-green-400 dark:bg-green-400/10'
                                                                        : 'text-gray-700 bg-gray-100 dark:text-slate-300 dark:bg-white/5'
                                                                    }`}
                                                                    >
                                                                    {item.to}
                                                                    </span>
                                                                </div>
                                                                </div>

                                                                {item.reason && (
                                                                    <div className="mt-2.5 py-2 px-3 bg-red-500/10 border-l-2 border-red-500 rounded-r-sm">
                                                                        <p className="text-red-400 text-[11px] font-bold leading-relaxed italic">
                                                                            {item.reason}
                                                                        </p>
                                                                    </div>
                                                                )}
                                                            </div>
                                                        ))
                                                    ) : (
                                                        <div className="p-4 text-center text-slate-500 text-xs italic">No detailed history available.</div>
                                                    )}
                                                </div>
                                            </AccordionContent>
                                        </AccordionItem>
                                    </Accordion>
                                </div>
                            )}
                        </CardContent>
                        <div className="absolute inset-0 opacity-[0.02] pointer-events-none">
                            <PlaceholderPattern className="size-full stroke-primary" />
                        </div>
                    </Card>

                    {/* الكروت السفلية */}
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4 shrink-0">
                        <Card className="border-red-200 dark:border-red-900/30">
                            <CardHeader className="py-3 px-4 bg-red-50 dark:bg-red-950/20">
                                <CardTitle className="text-sm text-red-600 flex items-center gap-2">
                                    <ShieldAlert className="w-4 h-4" /> Recommended Action
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="p-4 text-sm italic">
                                <div dangerouslySetInnerHTML={{ __html: analysisData?.actionMessage || "Submit data to see action..." }} />
                            </CardContent>
                        </Card>

                        <Card className="border-blue-200 dark:border-blue-900/30">
                            <CardHeader className="py-3 px-4 bg-blue-50 dark:bg-blue-950/20">
                                <CardTitle className="text-sm text-blue-600 flex items-center gap-2">
                                    <Info className="w-4 h-4" /> Mobile Adjustment Details
                                </CardTitle>
                            </CardHeader>
                                <CardContent className="p-4 text-sm">
                                    {renderWeMobileContent(analysisData?.weMobile, handleOpenModal)}
                                </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
            <HandleAction
               isOpen={isActionModalOpen}
                setIsOpen={setIsActionModalOpen}
                data={analysisData?.available_actions || []}
                ticketId={data.tktID}
            />
           <ErrorModal
                isOpen={error.isOpen}
                onClose={closeError}
                title={error.title}
                message={error.message}
                status={error.status} // مرر الـ status هنا
            />

        </AppLayout>
    );
}


function CompactResultRow({ label, value, color, bold, isBadge, className }: any) {
    const isHtml = (str: any) => /<[a-z][\s\S]*>/i.test(str);

    return (
        <div className={`flex flex-col ${className}`}>
            <span className="text-[10px] text-muted-foreground uppercase font-bold tracking-tight leading-tight mb-0.5">
                {label}
            </span>
            {isBadge ? (
                <Badge variant="outline" className="text-[11px] h-6 py-0 px-2 w-fit font-bold bg-background border-primary/20">
                    {value}
                </Badge>
            ) : (
                <span className={`text-[13px] truncate ${bold ? 'font-extrabold' : 'font-semibold'}`} style={{ color: color }}>
                    {isHtml(value) ? (
                        <span dangerouslySetInnerHTML={{ __html: value }} />
                    ) : (
                        value || 'N/A'
                    )}
                </span>
            )}
        </div>
    );
}

function ResultRow({ label, value, color, bold, isBadge }: any) {
    const isHtml = (str: any) => /<[a-z][\s\S]*>/i.test(str);

    return (
        <div className="border-b border-border/50 py-2">
            <span className="text-xs text-muted-foreground block uppercase tracking-wider">{label}</span>
            {isBadge ? (
                <Badge variant="secondary" className="mt-1">{value}</Badge>
            ) : (
                <div className={`text-sm ${bold ? 'font-bold' : ''}`} style={{ color: color }}>
                    {isHtml(value) ? (
                        <div dangerouslySetInnerHTML={{ __html: value }} />
                    ) : (
                        value || 'N/A'
                    )}
                </div>
            )}
        </div>
    );
}
// دالة لعرض محتوى We Mobile بناءً على الشروط
const renderWeMobileContent = (data: any, handleOpenModal: (data: any) => void) => {
    // 1. حالة عدم وجود بيانات أو تحميل
    if (!data) return "No adjustment info available.";

    // 2. إذا كان غير صالح (Valid = false) -> اعرض الرسالة فقط
    if (data.valid === false) {
        return (
            <div
                className="text-red-500 font-medium"
                dangerouslySetInnerHTML={{ __html: data.message }}
            />
        );
    }

    // 3. إذا كان صالح (Valid = true) -> اعرض التفاصيل وزر الأكشن
    return (
        <div className="space-y-4">
            {/* عرض الرسالة العلوية (Instructions) */}
            <div className="text-xs text-muted-foreground bg-blue-50/50 p-2 rounded border border-blue-100 dark:border-blue-900/50 dark:bg-blue-900/20">
                <div dangerouslySetInnerHTML={{ __html: data.message }} />
            </div>

            {/* عرض البيانات غير الفارغة فقط (Mapping non-null elements) */}
            <div className="grid grid-cols-2 gap-3">
                {data.quota && (
                    <CompactResultRow
                        label="MBB Quota"
                        value={`${data.quota} GB`}
                        bold
                        color="oklch(0.6 0.18 145)" // لون أخضر
                    />
                )}

                {data.expireDays && (
                    <CompactResultRow
                        label="Validity"
                        value={`${data.expireDays} Days`}
                    />
                )}

                {data.Handled_By && (
                    <CompactResultRow label="Assigned To" value={data.Handled_By} />
                )}


                {data.bss_service_code ? (
                <CompactResultRow label="bss service code" value={data.bss_service_code} />
                ) : (
                <CompactResultRow label="SLA" value={data.sla} />
                )}

            </div>

            {data.sr_id && (
                <Button
                    onClick={() => handleOpenModal(data)}
                    className="w-full h-10 text-sm font-semibold bg-emerald-600 hover:bg-emerald-500 text-white
                            shadow-[0_4px_14px_0_rgba(16,185,129,0.39)]
                            transition-all duration-300 ease-in-out
                            hover:scale-[1.01] active:scale-[0.98]
                            flex items-center justify-center gap-2 group"
                >
                    <Zap className="w-4 h-4 fill-current group-hover:animate-pulse" />
                    Take an Action
                </Button>
            )}
        </div>
    );
};
