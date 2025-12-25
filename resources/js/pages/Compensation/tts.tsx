import FullScreenLoader from '@/components/full-screen-loader';
import { PlaceholderPattern } from '@/components/ui/placeholder-pattern';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type NavItem } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { HandleAction } from "@/components/ui/handle-action-modal";
import { ErrorModal } from "@/components/ui/error-modal";
import { useState , useRef } from 'react';
import {Play,Ticket,Search,History,ShieldAlert,Info,CheckCircle2,Zap , Activity} from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Checkbox } from '@/components/ui/checkbox';
import axios from 'axios';
import { route } from 'ziggy-js';
import compensation from '@/routes/compensation'
import { RadioGroup , RadioGroupItem } from '@/components/ui/radio-group';
import {
  Accordion,
  AccordionContent,
  AccordionItem,
  AccordionTrigger,
} from "@/components/ui/accordion";

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Compensation', href: '/' },
    { title: 'TTS Ticket', href: compensation.tts.url() },
];


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


export default function TTSAnalyzer({ packages }: { packages: { id: number; name: string }[] }) {
    const [analysisData, setAnalysisData] = useState<any>(null);
    const [isLoading, setIsLoading] = useState(false);
    const { error, handleError, closeError } = useErrorModal();
    const [wasSubmitted, setWasSubmitted] = useState(false);
    const [showVoiceImpact, setShowVoiceImpact] = useState(false);
    const [showTicketOpen, setShowTicketOpen] = useState(false);
    const [showOutageClose, setShowOutageClose] = useState(false);
    const [showFollowUpcase, setShowFollowUpcase] = useState(false);

    // استخدام useForm من Inertia للتعامل مع البيانات والـ CSRF تلقائياً
    const { data, setData, post, processing, reset } = useForm({
        tktID: '',
        DSLnumber: '',
        inputText: '',
        selectPackage: '',
        usageFile: '',
        voiceinstability: '',
        outageCloseTime: '',
        ineligibleDays: '',
        FollowUpcase: '',
        tktopen: '',
        UsageFile: null as File | null,
    });
    const errors = {
        tktID: wasSubmitted && !data.tktID,
        DSLnumber: wasSubmitted && !data.DSLnumber,
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
    setWasSubmitted(true);

    const hasMissingFields = !data.tktID || !data.DSLnumber || !data.inputText || !data.selectPackage;

    if (hasMissingFields) {
        console.log("Validation Failed");
        return;
    }

    setIsLoading(true);
    setAnalysisData(null);

    // --- الحل هنا: تحويل الكائن إلى FormData ---
    const formData = new FormData();

    // إضافة كل الحقول العادية
    Object.keys(data).forEach(key => {
        // نتحقق إذا كانت القيمة موجودة (ليست null) لتجنب المشاكل
        const value = (data as Record<string, any>)[key];
        if (value !== null && value !== undefined) {
            formData.append(key, value);
        }
    });

    // إرسال الـ formData بدلاً من الـ data
    axios.post(route('compensation.data'), formData, {
        headers: {
            'Content-Type': 'multipart/form-data', // إبلاغ السيرفر بنوع البيانات
        }
    })
    .then((response) => {
        // ... نفس الكود الخاص بك
        setAnalysisData(response.data);
        setWasSubmitted(false);
        setShowFollowUpcase(response.data.needToCheekFollowUP || false);
        // ...
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
    const dateInputRef = useRef<HTMLInputElement>(null);
    const fileInputRef = useRef<HTMLInputElement>(null);

    const handleInputClick = () => {
        try {
            dateInputRef.current?.showPicker();
        } catch (err) {
            dateInputRef.current?.focus();
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Compensation - TTS" />

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
                                <div className="relative flex-1">
                                    <Ticket className="absolute left-3 top-3 w-4 h-4 text-muted-foreground" />
                                    <Input
                                        placeholder="DSL Number"
                                        className={`pl-9 ${errors.DSLnumber ? "border-red-500 focus:border-red-500" : ""}`}
                                        value={data.DSLnumber}
                                        onChange={e => setData('DSLnumber', e.target.value)}
                                        onKeyDown={(e) => {
                                            if (e.key === 'Enter') {
                                                e.preventDefault();
                                                openTicketLogs(data.DSLnumber);
                                            }
                                        }}
                                        onPaste={(e) => {
                                            e.preventDefault();
                                            const pastedText = e.clipboardData.getData('text').trim();

                                            setData('DSLnumber', pastedText);

                                            openTicketLogs(pastedText);
                                        }}
                                    />

                                    {errors.DSLnumber && (
                                    <p className="text-xs text-red-500 mt-1">DSL Number is required</p>
                                    )}

                                </div>
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
                               <div className="flex gap-4 items-end shrink-0 pt-2">
                                    {/* اختار الباقة */}
                                    <div className="flex-1 space-y-1">
                                        <label className="text-[10px] font-bold uppercase text-muted-foreground">
                                            Package Selection
                                        </label>
                                        <Select value={data.selectPackage} onValueChange={(v) => setData('selectPackage', v)}>
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
                                            <p className="text-[10px] text-red-500 mt-1">Required</p>
                                        )}
                                    </div>

                                    {/* رفع الملف */}
                                    <div className="flex-1 space-y-1">
                                        <label className="text-[10px] font-bold uppercase text-muted-foreground">
                                            Usage File (Optional)
                                        </label>
                                        <Input
                                            ref={fileInputRef}
                                            type="file"
                                            accept=".xlsx, .xls"
                                            className="h-9 text-xs"
                                            onChange={(e) => {
                                                const file = e.target.files ? e.target.files[0] : null;
                                                setData('UsageFile', file);
                                            }}
                                        />
                                    </div>
                                </div>
                                <div className="space-y-4">
                                    {showFollowUpcase && (

                                    <div className="pl-6 space-y-2 animate-in fade-in slide-in-from-left-4">
                                        <label htmlFor="FollowUpcase" className="text-sm font-medium">
                                            CST follows up with us within Problem Period
                                        </label>
                                    <div className="flex items-center gap-2">
                                        <RadioGroup
                                        value={data.FollowUpcase}
                                        onValueChange={(value) => setData('FollowUpcase', value)}
                                        className="flex items-center gap-4"
                                        >
                                        <div className="flex items-center gap-2">
                                            <RadioGroupItem value="yes" id="option1" />
                                            <label htmlFor="option1" className="text-sm cursor-pointer">Yes</label>
                                        </div>
                                        <div className="flex items-center gap-2">
                                            <RadioGroupItem value="no" id="option2" />
                                            <label htmlFor="option2" className="text-sm cursor-pointer">No</label>
                                        </div>
                                        </RadioGroup>
                                    </div>
                                    </div>
                                )}

                                    {/* Voice Impact Checkbox */}

                                    {showVoiceImpact && (
                                        <div className="pl-6 space-y-2 animate-in fade-in slide-in-from-left-4">
                                            <label htmlFor="voiceinstability" className="text-sm font-medium">
                                                Voice Down and Data was impacted
                                            </label>
                                            <div className="flex items-center gap-2">
                                                <RadioGroup
                                                value={data.voiceinstability}
                                                onValueChange={(value) => setData('voiceinstability', value)}
                                                className="flex items-center gap-4"
                                                >
                                                <div className="flex items-center gap-2">
                                                    <RadioGroupItem value="Down" id="option1" />
                                                    <label htmlFor="option1" className="text-sm cursor-pointer">Down Case</label>
                                                </div>
                                                <div className="flex items-center gap-2">
                                                    <RadioGroupItem value="Instability" id="option2" />
                                                    <label htmlFor="option2" className="text-sm cursor-pointer">Instability Case</label>
                                                </div>
                                                </RadioGroup>
                                            </div>
                                        </div>
                                    )}

                                    {/* Ticket Still Open Checkbox */}
                                    {showTicketOpen && (
                                        <div className="flex items-center space-x-2">
                                            <Checkbox
                                            id="tktopen"
                                            checked={data.tktopen === 'true'}
                                            onCheckedChange={(checked) => setData('tktopen', checked ? 'true' : '')}
                                            />
                                            <label htmlFor="tktopen" className="text-sm">
                                            in case ticket still open but solved
                                            </label>
                                        </div>
                                    )}
                                    {/* Outage Close Time */}
                                    {showOutageClose && (
                                        <div className="space-y-1">
                                            <label className="text-sm font-medium text-slate-700 dark:text-slate-300">
                                                Close Time on CST360:
                                            </label>
                                            <div className="relative group" onClick={handleInputClick}>
                                                <Input
                                                    ref={dateInputRef}
                                                    type="datetime-local"
                                                    value={data.outageCloseTime}
                                                    onChange={(e) => setData('outageCloseTime', e.target.value)}
                                                    max={new Date().toISOString().slice(0, 16)}
                                                    className="
                                                        cursor-pointer w-full h-10
                                                        bg-slate-50 border-slate-200 text-slate-900

                                                        dark:bg-slate-950 dark:border-slate-800 dark:text-slate-100
                                                        dark:[color-scheme:dark]

                                                        transition-all duration-200
                                                        focus:ring-2 focus:ring-primary/20 focus:border-primary
                                                        group-hover:border-primary/50

                                                        dark:[&::-webkit-calendar-picker-indicator]
                                                        dark:[&::-webkit-calendar-picker-indicator]:brightness-200
                                                        dark:[&::-webkit-calendar-picker-indicator]:opacity-80
                                                    "
                                                    />
                                                </div>
                                            </div>
                                        )}
                                    </div>



                                <div className="flex gap-2 items-center shrink-0 pt-2">
                                    {/* خانة Unpaid Days - تأخذ مساحة صغيرة محددة */}
                                    <div className="w-44 space-y-1">
                                        <label className="text-[10px] font-bold uppercase text-muted-foreground whitespace-nowrap">
                                            Unpaid Days
                                        </label>
                                        <Input
                                            type="number"
                                            placeholder="0"
                                            min="0"
                                            className="h-9 text-center"
                                            value={data.ineligibleDays}
                                            onChange={e => setData('ineligibleDays', e.target.value)}
                                        />
                                    </div>

                                    {/* زر Submit - يأخذ باقي المساحة المتاحة */}
                                    <Button
                                        className="flex-1 h-9 bg-primary hover:bg-primary/90 shadow-md mt-4"
                                        onClick={handleSubmit}
                                        disabled={isLoading}
                                    >
                                        {isLoading ? (
                                            "Analyzing…"
                                        ) : (
                                            <>
                                                <Play className="w-3 h-3 mr-1" /> Submit
                                            </>
                                        )}
                                    </Button>

                                    <Button
                                        variant="destructive"
                                        className="px-3 h-9 mt-4"
                                        onClick={() => {
                                            // 1. إعادة تعيين بيانات الفورم الأساسية
                                            reset();

                                            // 2. مسح نتائج التحليل
                                            setAnalysisData(null);

                                            // 3. إعادة تعيين حالة التحقق
                                            setWasSubmitted(false);

                                            // 4. إخفاء الحقول التي ظهرت بناءً على التحليل السابق (مهم جداً)
                                            setShowVoiceImpact(false);
                                            setShowTicketOpen(false);
                                            setShowOutageClose(false);
                                            setShowFollowUpcase(false);

                                            // 5. مسح ملف الإدخال من المتصفح يدوياً
                                            if (fileInputRef.current) {
                                                fileInputRef.current.value = '';
                                            }
                                        }}
                                    >
                                        Reset
                                    </Button>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
                {/* الجزء الأيمن: النتائج */}
                <div className="lg:col-span-7 space-y-6">
                    <Card className="min-h-[650px] relative overflow-hidden flex flex-col border-primary/10">
                        <CardHeader className="border-b border-border/50 shrink-0">
                            <CardTitle className="text-lg flex items-center gap-2">
                                <CheckCircle2 className="w-5 h-5 text-green-500" />
                                Tech Compensation Results
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
                                <div className="space-y-6 animate-in fade-in zoom-in-95 duration-300">

                                {/* 1. قسم الفاليديشن (الأهم) */}
                                    <div className="p-0 rounded-xl border-2 overflow-hidden flex flex-col md:flex-row shadow-sm transition-all"
                                        style={{ borderColor: analysisData.validationcolor }}>

                                        {/* الجزء الأيسر: محتوى الفاليديشن الرئيسي */}
                                        <div className="flex-1 p-4 flex flex-col gap-2"
                                            style={{ backgroundColor: `${analysisData.validationcolor}10` }}>
                                            <div className="flex items-center justify-between">
                                                <span className="text-[10px] font-black uppercase tracking-widest opacity-70">Validation Status</span>
                                                {analysisData.tktStillOpen && (
                                                    <Badge variant="destructive" className="animate-pulse shadow-sm">Ticket Still Open</Badge>
                                                )}
                                            </div>

                                            <h3 className="text-3xl font-black leading-tight" style={{ color: analysisData.validationcolor }}>
                                                {analysisData.validation}
                                            </h3>

                                            {analysisData.validationReason && (
                                                <p className="text-sm font-medium opacity-80 italic flex items-center gap-1">
                                                    <span className="inline-block w-1.5 h-1.5 rounded-full bg-current opacity-50" />
                                                    Reason: {analysisData.validationReason}
                                                </p>
                                            )}
                                        </div>

                                        {/* الجزء الأيمن: الأيام المستحقة (Valid Duration) */}
                                        {analysisData.validDuration !== null && (
                                            <div className="md:w-32 flex flex-col items-center justify-center p-4 border-t md:border-t-0 md:border-l border-current/20"
                                                style={{ backgroundColor: analysisData.validationcolor }}>
                                                <span className="text-[9px] font-black text-white/80 uppercase tracking-tighter mb-1 text-center">
                                                    Valid Duration
                                                </span>
                                                <div className="flex flex-col items-center leading-none">
                                                    <span className="text-3xl font-black text-white">
                                                        {analysisData.validDuration}
                                                    </span>
                                                    <span className="text-[13px] font-bold text-white/90">
                                                        Days
                                                    </span>
                                                </div>
                                            </div>
                                        )}
                                    </div>

                                    {/* 2. المجموعة الأساسية (Problem Info) */}
                                    <div className="grid grid-cols-2 md:grid-cols-4 gap-4 bg-muted/20 p-4 rounded-lg border border-border/40">
                                        <CompactResultRow label="Problem Type" value={analysisData.problemType} bold />
                                        <CompactResultRow label="Escalations" value={`${analysisData.escalationtimes} times`} />
                                        <CompactResultRow label="DSL Number" value={analysisData.DSLno} />
                                        <CompactResultRow label="Main Package" value={analysisData.mainpackage} isBadge/>
                                    </div>

                                    {/* 3. قسم التعويضات (Compensation) */}
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        {/* تعويض الجيجا بايت */}
                                        {(analysisData.compensationGB || analysisData.GBresponsbleTeam) && (
                                            <div className="p-3 rounded-lg border border-blue-100 bg-blue-50/50 dark:bg-blue-950/10">
                                                <span className="text-[10px] text-blue-600 font-bold uppercase flex justify-between">
                                                    free Quita
                                                        <span className="text-[20px] bg-orange-100 text-orange-600 px-1.5 py-0.5 rounded-full flex items-center gap-1 animate-bounce-subtle">
                                                            🎁 +{analysisData.compensationGB}
                                                        </span>
                                                </span>
                                                <div className="flex justify-between items-end mt-1">
                                                    <span className="text-3xl font-bold">
                                                        {analysisData.compensationGB}
                                                    </span>
                                                </div>
                                                {analysisData.hwoAddGB && <p className="text-[14px] mt-1 text-muted-foreground italic">{analysisData.hwoAddGB}</p>}
                                            </div>
                                        )}

                                        {/* تعويض المبلغ المالي */}
                                        {(analysisData.compensationLE || analysisData.LEresponsbleTeam) && (
                                            <div className="p-3 rounded-lg border border-green-100 bg-green-50/50 dark:bg-green-950/10">
                                                <span className="text-[10px] text-green-600 font-bold uppercase flex justify-between">
                                                    Amount
                                                    <span className="text-[20px] bg-amber-100 text-amber-700 px-1.5 py-0.5 rounded-full flex items-center gap-1">
                                                        🎁 +{analysisData.compensationGB}
                                                    </span>
                                                </span>
                                                <div className="flex justify-between items-end mt-1">
                                                    <span className="text-3xl font-bold">
                                                        {analysisData.compensationLE}
                                                    </span>
                                                </div>
                                                {analysisData.hwoAddLE && <p className="text-[14px] mt-1 text-muted-foreground italic">{analysisData.hwoAddLE}</p>}
                                            </div>
                                        )}
                                    </div>

                                    {/* 4. التواريخ والمدة */}
                                    <div className="grid grid-cols-1 md:grid-cols-4 gap-4 px-2">
                                        <CompactResultRow label="closed From" value={analysisData.closedDate} color="oklch(0.6 0.2 250)" bold/>
                                        <CompactResultRow label="From" value={analysisData.startDate} />
                                        <CompactResultRow label="To" value={analysisData.closeDate} />
                                        <CompactResultRow label="Total Duration" value={analysisData.totalDuration} color="oklch(0.6 0.2 250)" bold />

                                    </div>

                                    {/* 5. تحذيرات إضافية (تظهر فقط إذا كانت true أو بها نص) */}
                                    {(analysisData.duplecatedWarning || analysisData.outage || analysisData.specialHandling) && (
                                        <div className="flex flex-wrap gap-2 pt-2">
                                            {analysisData.duplecatedWarning && <Badge variant="outline" className="text-orange-500 border-orange-500 bg-orange-50">Duplicate Detected</Badge>}
                                            {analysisData.outage && <Badge className="bg-purple-500">Outage Impacted</Badge>}
                                            {analysisData.specialHandling && <Badge variant="secondary">Special Handling</Badge>}
                                            {analysisData.voiceImpacted && <Badge variant="outline">Voice Impacted</Badge>}
                                        </div>
                                    )}

                                    {/* 6. Accordion for Usage */}
                                    {analysisData.usageCollectionData && (
                                        <Accordion type="single" collapsible className="w-full px-1 mt-4">
                                            <AccordionItem value="usage-history" className="border-none">
                                                <AccordionTrigger className="py-2 px-3 bg-muted/20 hover:bg-muted/30 rounded-md text-[14px] h-9 transition-all">
                                                    <div className="flex items-center gap-2 font-bold text-primary/80">
                                                        <Activity className="w-4 h-4" />
                                                        Show Daily Usage Details
                                                    </div>
                                                </AccordionTrigger>
                                                <AccordionContent
                                                    className="mt-2 p-0 rounded-md overflow-y-auto max-h-[400px] shadow-inner border
                                                        bg-white border-gray-200
                                                        dark:bg-slate-950 dark:border-white/5"
                                                >
                                                    <div className="flex flex-col">
                                                        {Array.isArray(analysisData.usageCollectionData) && analysisData.usageCollectionData.length > 0 ? (
                                                            analysisData.usageCollectionData.map((item: any, index: number) => (
                                                                <div key={index}
                                                                    className="p-3 border-b last:border-none transition-colors group
                                                                        border-gray-200 hover:bg-gray-50
                                                                        dark:border-white/5 dark:hover:bg-white/[0.03]">

                                                                    {/* السطر الأول: التاريخ ولون الحالة الديناميكي */}
                                                                    <div className="grid grid-cols-4 items-center mb-1.5 gap-2">
                                                                        {/* التاريخ */}
                                                                        <div className="col-span-1 flex items-center gap-2">
                                                                            <span className="font-bold text-[14px] flex items-center gap-2 min-w-[120px]"
                                                                                style={{ color: item.color }}>
                                                                                <span className="w-1.5 h-1.5 rounded-full group-hover:scale-150 transition-transform"
                                                                                    style={{ backgroundColor: item.color }} />
                                                                                {item.date}
                                                                            </span>
                                                                        </div>

                                                                        {/* الاستهلاك */}
                                                                        <div className="col-span-1 flex items-center justify-end gap-1.5">
                                                                            <span className="text-gray-700 bg-gray-100 px-1.5 py-0.5 rounded font-black dark:text-slate-200 dark:bg-white/10">
                                                                            {item.usage} {item.unit}
                                                                            </span>
                                                                        </div>

                                                                        {/* High Usage Badge - دائماً محجوز المساحة */}
                                                                        <div className="col-span-1 flex justify-center">
                                                                            {item.is_high ? (
                                                                            <span className="text-[14px] text-orange-600 bg-orange-100 px-1.5 py-0.5 rounded-full font-bold animate-pulse dark:bg-orange-500/10 dark:text-orange-400 whitespace-nowrap">
                                                                                High Usage
                                                                            </span>
                                                                            ) : (
                                                                            <span className="invisible text-[14px] px-1.5 py-0.5 whitespace-nowrap">
                                                                                Placeholder
                                                                            </span>
                                                                            )}
                                                                        </div>

                                                                        {/* Day Number */}
                                                                        <div className="col-span-1 flex justify-end">
                                                                            <span className="text-[12px] text-slate-500 font-mono font-bold uppercase tracking-widest opacity-60">
                                                                            Day {index + 1}
                                                                            </span>
                                                                        </div>
                                                                    </div>

                                                                    {/* السطر الثالث: عرض الملاحظة بأسلوب انسيابي إذا وجدت */}
                                                                    {item.note && item.note.trim() !== "" && (
                                                                        <div className="mt-2.5 py-2 px-3 bg-blue-500/5 border-l-2 border-blue-400 rounded-r-sm">
                                                                            <p className="text-blue-600 dark:text-blue-400 text-[10.5px] font-bold leading-relaxed italic">
                                                                                Note: {item.note}
                                                                            </p>
                                                                        </div>
                                                                    )}
                                                                </div>
                                                            ))
                                                        ) : (
                                                            <div className="p-8 text-center flex flex-col items-center gap-2">
                                                                <div className="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center dark:bg-slate-900">
                                                                    <Activity className="w-5 h-5 text-slate-400" />
                                                                </div>
                                                                <p className="text-slate-500 text-xs italic">No usage data found for this ticket.</p>
                                                            </div>
                                                        )}
                                                    </div>
                                                </AccordionContent>
                                            </AccordionItem>
                                        </Accordion>
                                    )}
                                    {analysisData.usageMessage &&(
                                        <div className="mt-2.5 py-2 px-3 bg-red-500/5 border-l-2 border-red-500 rounded-r-sm">
                                            <p className="text-red-600 dark:text-red-400 text-[14px] font-bold leading-relaxed italic">
                                                {analysisData.usageMessage}
                                            </p>
                                        </div>
                                    )}
                                </div>
                            )}
                        </CardContent>
                    </Card>
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
