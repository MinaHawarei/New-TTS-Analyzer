import { Breadcrumbs } from '@/components/breadcrumbs';
import { AnalysisSkeleton } from '@/components/analysis-skeleton';
import FullScreenLoader from '@/components/full-screen-loader';
import { PlaceholderPattern } from '@/components/ui/placeholder-pattern';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type NavItem } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { useState } from 'react';
import {
    Play,
    RotateCcw,
    Ticket,
    ExternalLink,
    Search,
    History,
    ShieldAlert,
    Info,
    CheckCircle2
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
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

    // استخدام useForm من Inertia للتعامل مع البيانات والـ CSRF تلقائياً
    const { data, setData, post, processing, reset } = useForm({
        tktID: '',
        inputText: '',
        selectPackage: '',
    });
    const errors = {
        tktID: !data.tktID,
        inputText: !data.inputText,
        selectPackage: !data.selectPackage,
    };
    const isFormInvalid =
        errors.tktID || errors.inputText || errors.selectPackage;
    // دالة فتح نافذة الـ Logs الخارجية
    const openTicketLogs = () => {
        if (!data.tktID) return alert('Please enter Ticket ID first!');
        const url = `http://tts/new/index.php/logs/core_log/get_all_ticket_logs?ticket_id=${data.tktID}`;
        window.open(url, 'TicketLogsPopup', 'width=800,height=600,resizable=yes,scrollbars=yes');
    };

    // إرسال التحليل
    const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!data.inputText) return alert('item logs is required!');

    setIsLoading(true);
    setAnalysisData(null);

    axios.post(route('analyze.data'), data)
        .then((response) => {
            setAnalysisData(response.data);
        })
        .finally(() => {
            setIsLoading(false);
        });
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
                                    />

                                    {errors.tktID && (
                                    <p className="text-xs text-red-500 mt-1">Ticket ID is required</p>
                                    )}

                                </div>
                                <Button variant="secondary" onClick={openTicketLogs}>Logs</Button>
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
                                        disabled={isFormInvalid || isLoading}
                                        >
                                        {isLoading ? (
                                            "Analyzing…"
                                        ) : (
                                            <>
                                            <Play className="w-4 h-4 mr-2" /> Submit Analysis
                                            </>
                                        )}
                                        </Button>

                                    <Button variant="destructive" className="px-3" onClick={() => { reset(); setAnalysisData(null); }}>
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

                                    {/* SLA Status & Technical IDs */}
                                    <div className="flex items-stretch bg-primary/5 rounded-md border border-primary/10 overflow-hidden min-h-[4.5rem]">
                                        <div className="flex-1 px-4 flex flex-col justify-center min-w-0">
                                            <span className="text-[10px] text-muted-foreground uppercase font-black tracking-widest leading-none mb-1">
                                                Final SLA Status
                                            </span>
                                            <span className="text-base font-black leading-tight" style={{ color: analysisData.slaStatus_color }}>
                                                {analysisData.slaStatus}
                                            </span>
                                        </div>

                                        <div className="flex shrink-0 items-center bg-primary/10 px-4 gap-4 border-l border-primary/20">
                                            {analysisData.delayId && <CompactResultRow label="Delay" value={analysisData.delayId} transparent />}
                                            {analysisData.reassignId && (
                                                <div className="flex items-center gap-4 h-full">
                                                    <div className="h-6 w-[1.5px] bg-primary/20" />
                                                    <CompactResultRow label="Re-Assign" value={analysisData.reassignId} transparent />
                                                </div>
                                            )}
                                            {analysisData.accelerationId && (
                                                <div className="flex items-center gap-4 h-full">
                                                    <div className="h-6 w-[1.5px] bg-primary/20" />
                                                    <CompactResultRow label="Acceleration" value={analysisData.accelerationId} transparent />
                                                </div>
                                            )}
                                            {analysisData.reworkId && (
                                                <div className="flex items-center gap-4 h-full">
                                                    <div className="h-6 w-[1.5px] bg-primary/20" />
                                                    <CompactResultRow label="Re-Work" value={analysisData.reworkId} transparent />
                                                </div>
                                            )}
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
                                            <AccordionContent className="mt-2 p-0 bg-slate-950 rounded-md overflow-y-auto shadow-inner border border-white/5">
                                                <div className="flex flex-col">
                                                    {Array.isArray(analysisData.esclationHistory) ? (
                                                        analysisData.esclationHistory.map((item: any, index: number) => (
                                                            <div key={index} className="p-3 border-b border-white/5 last:border-none hover:bg-white/[0.03] transition-colors group">
                                                                <div className="flex justify-between items-center mb-1.5">
                                                                    <span className="text-blue-400 font-bold text-[12px] flex items-center gap-2">
                                                                        <span className="w-1 h-1 rounded-full bg-blue-400 group-hover:scale-150 transition-transform" />
                                                                        #{item.id} — {item.support_group}
                                                                    </span>
                                                                    <span className="text-[9px] text-slate-500 font-mono font-bold uppercase tracking-widest">Step {index + 1}</span>
                                                                </div>
                                                                <div className="flex items-center flex-wrap gap-x-3 gap-y-1 text-[11px] font-mono">
                                                                    <div className="flex items-center gap-1.5">
                                                                        <span className="text-slate-500 text-[10px] uppercase font-bold">From</span>
                                                                        <span className="text-slate-300 bg-white/5 px-1.5 py-0.5 rounded">{item.from}</span>
                                                                    </div>
                                                                    <div className="flex items-center gap-1.5">
                                                                        <span className="text-slate-500 text-[10px] uppercase font-bold">To</span>
                                                                        <span className={`px-1.5 py-0.5 rounded ${item.to.includes('Now') ? 'text-green-400 bg-green-400/10 animate-pulse font-bold' : 'text-slate-300 bg-white/5'}`}>
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
                                    <Info className="w-4 h-4" /> Adjustment Details
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="p-4 text-sm">
                                <div dangerouslySetInnerHTML={{ __html: analysisData?.weMobileMessage || "No adjustment info available." }} />
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}

// --- Components المحدثة لدعم HTML ---

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
