import FullScreenLoader from '@/components/full-screen-loader';
import { PlaceholderPattern } from '@/components/ui/placeholder-pattern';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type NavItem } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { HandleAction } from "@/components/ui/handle-action-modal";
import { ErrorModal } from "@/components/ui/error-modal";
import { useState , useRef } from 'react';
import {Play,Ticket,Search,FileSpreadsheet,ShieldAlert,Info,CheckCircle2,Zap,Activity,Lock,AlertTriangle,AlertCircle,Phone} from 'lucide-react';
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


export default function TTSCompensation({ packages }: { packages: { id: number; name: string }[] }) {
    const [analysisData, setAnalysisData] = useState<any>(null);
    const [isLoading, setIsLoading] = useState(false);
    const { error, handleError, closeError } = useErrorModal();
    const [wasSubmitted, setWasSubmitted] = useState(false);
    const [showVoiceImpact, setShowVoiceImpact] = useState(false);
    const [showTicketOpen, setShowTicketOpen] = useState(false);
    const [showOutageClose, setShowOutageClose] = useState(false);
    const [showFollowUpcase, setShowFollowUpcase] = useState(false);

    const [isLocked, setIsLocked] = useState(false); // هل الشاشة مقفلة؟
    const [showUnlockConfirm, setShowUnlockConfirm] = useState(false); // هل نظهر رسالة التأكيد؟

    // 2. دالة طلب فك القفل (تظهر الـ Popup)
    const handleUnlockRequest = () => {
        setShowUnlockConfirm(true);
    };

    // 3. دالة تأكيد فك القفل (تنفذ الفك فعلياً)
    const confirmUnlock = () => {
        setIsLocked(false);
        setShowUnlockConfirm(false);
    };

    // 4. دالة إلغاء الفك
    const cancelUnlock = () => {
        setShowUnlockConfirm(false);
    };

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
    // تأكدنا إننا بنصفر حالة القفل في بداية البحث الجديد
    setIsLocked(false);
    setShowUnlockConfirm(false);

    const formData = new FormData();
    Object.keys(data).forEach(key => {
        const value = (data as Record<string, any>)[key];
        if (value !== null && value !== undefined) {
            formData.append(key, value);
        }
    });

    axios.post(route('compensation.data'), formData, {
        headers: {
            'Content-Type': 'multipart/form-data',
        }
    })

    .then((response) => {
        setAnalysisData(response.data);

        // ==========================================================
        // التعديل هنا: الربط بالمتغير الصحيح من السيرفر
        // ==========================================================
        if (response.data.cst_has_tkt_before == true || response.data.cst_has_tkt_before == 1) {
            setIsLocked(true); // تفعيل القفل
        } else {
            setIsLocked(false); // عدم تفعيل القفل
        }
        // ==========================================================

        setWasSubmitted(false);
        setShowFollowUpcase(response.data.needToCheekFollowUP || false);
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
                                    <Phone className="absolute left-3 top-3 w-4 h-4 text-muted-foreground" />
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

                                    <div className="flex-1 space-y-1">
                                        <label className="text-[10px] font-bold uppercase text-muted-foreground flex items-center gap-1.5">
                                            Usage File (Optional)
                                        </label>

                                        <div className="relative">
                                            {/* الأيقونة داخل الـ Input */}
                                            <FileSpreadsheet className={`absolute left-3 top-2.5 w-4 h-4 z-10 transition-colors ${
                                                data.UsageFile ? "text-green-600" : "text-muted-foreground"
                                            }`} />

                                            <Input
                                                ref={fileInputRef}
                                                type="file"
                                                accept=".xlsx, .xls"
                                                /* إضافة padding-left (pl-9) لترك مساحة للأيقونة */
                                                className={`h-9 text-xs pl-9 file:mr-4 file:py-1 file:px-2 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-primary/10 file:text-primary hover:file:bg-primary/20 transition-all ${
                                                    analysisData?.validation === "Wrong Usage File"
                                                    ? "border-red-500 ring-2 ring-red-500/20 animate-pulse"
                                                    : data.UsageFile ? "border-green-500/50 bg-green-50/10" : ""
                                                }`}
                                                onChange={(e) => {
                                                    const file = e.target.files ? e.target.files[0] : null;
                                                    setData('UsageFile', file);
                                                }}
                                            />
                                        </div>
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
                        <CardContent className="flex-1 overflow-y-auto p-6 bg-slate-50/30 dark:bg-slate-900/10 relative">
                            {/* --- نافذة تأكيد فك القفل (POP UP) --- */}
                            {showUnlockConfirm && (
                                <div className="absolute inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm animate-in fade-in duration-200">
                                    <div className="bg-background border border-border p-6 rounded-xl shadow-2xl w-80 text-center space-y-4 transform scale-100 animate-in zoom-in-95 duration-200">
                                        <div className="mx-auto w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center mb-2">
                                            <AlertTriangle className="w-6 h-6 text-orange-600" />
                                        </div>
                                        <div>
                                            <h3 className="font-bold text-lg">Are you sure?</h3>
                                            <p className="text-sm text-muted-foreground">Do you really want to unlock the results?</p>
                                        </div>
                                        <div className="flex gap-3 justify-center pt-2">
                                            <Button variant="outline" size="sm" onClick={cancelUnlock} className="w-full">
                                                Cancel
                                            </Button>
                                            <Button variant="default" size="sm" onClick={confirmUnlock} className="w-full bg-orange-600 hover:bg-orange-700">
                                                Yes, Unlock
                                            </Button>
                                        </div>
                                    </div>
                                </div>
                            )}

                            {/* --- شاشة القفل (The Lock Screen) --- */}
                            {isLocked && !isLoading ? (
                                <div className="h-full flex flex-col items-center justify-center space-y-6 animate-in zoom-in-95 duration-500">
                                    {/* القفل المتحرك */}
                                    <div
                                        className="relative group cursor-pointer"
                                        onClick={handleUnlockRequest}
                                    >
                                        <div className="absolute -inset-4 bg-primary/20 rounded-full blur-xl opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                                        <div className="relative bg-background p-6 rounded-full border-4 border-primary/20 shadow-xl group-hover:scale-110 transition-transform duration-300">
                                            <Lock className="w-16 h-16 text-primary animate-bounce" style={{ animationDuration: '2s' }} />
                                        </div>

                                        {/* رسالة تظهر عند التمرير */}
                                        <div className="absolute top-full left-1/2 -translate-x-1/2 mt-4 opacity-0 group-hover:opacity-100 transition-opacity duration-300 whitespace-nowrap">
                                            <span className="text-xs font-bold text-primary bg-primary/10 px-3 py-1 rounded-full">
                                                Click to Unlock
                                            </span>
                                        </div>
                                    </div>

                        <div className="max-w-md mx-auto text-center space-y-6 py-4">
                        <div className="space-y-2">
                            <h2 className="text-3xl font-extrabold tracking-tight text-foreground">
                            Results Locked
                            </h2>
                            <p className="text-muted-foreground text-sm leading-relaxed">
                            The results are currently locked due to the following reasons:
                            </p>
                        </div>

                        <div className="space-y-4">
                            {analysisData?.duplecatedWarning && (
                            <div className="px-4 py-3 rounded-xl bg-destructive/10 border border-destructive/20 shadow-sm shadow-destructive/5">
                                <p className="text-2xl font-black text-destructive leading-tight tracking-tight">
                                {analysisData.duplecatedWarning}
                                </p>
                            </div>
                            )}

                            {analysisData?.validationReason && (
                            <p className="text-lg font-bold text-amber-600 dark:text-amber-400 leading-relaxed">
                                {analysisData.validationReason}
                            </p>
                            )}
                        </div>

                        {/* Decorative Divider */}
                        <div className="flex justify-center items-center gap-2 pt-2">
                            <span className="h-1.5 w-1.5 rounded-full bg-destructive/40 dark:bg-destructive/60"></span>
                            <span className="h-1 w-12 rounded-full bg-destructive/20 dark:bg-destructive/30"></span>
                            <span className="h-1.5 w-1.5 rounded-full bg-destructive/40 dark:bg-destructive/60"></span>
                        </div>
                        </div>

                                </div>
                            ) : (
                            isLoading ? (
                                <FullScreenLoader text="Analyzing ticket data, please wait…" />
                            ) : !analysisData ? (
                                <div className="flex flex-col items-center justify-center h-full text-muted-foreground opacity-40">
                                    <PlaceholderPattern className="w-16 h-16 mb-4" />
                                    <p className="text-sm font-medium">Input data and submit to see results</p>
                                </div>
                            ) : (
                                <div className="space-y-6 animate-in fade-in zoom-in-95 duration-300">

                                    {analysisData?.warnings && analysisData.warnings.length > 0 && (
                                        <div className="w-full max-w-2xl mx-auto space-y-3 mb-6">
                                            {/* ترتيب التحذيرات بحيث المستوى 1 يظهر الأول */}
                                            {analysisData.warnings
                                            .sort((a: any, b: any) => a.level - b.level)
                                            .map((warn: { level: number; message: string }, idx: number) => {
                                                // تحديد التصميم بناءً على الـ Level
                                                const getStyle = (level: number) => {
                                                switch (level) {
                                                    case 1: // خطير جداً (أحمر)
                                                    return {
                                                        container: "bg-red-50 dark:bg-red-950/30 border-red-200 dark:border-red-900/50 shadow-red-500/5",
                                                        icon: <AlertTriangle className="w-5 h-5 text-red-600 dark:text-red-400" />,
                                                        textColor: "text-red-800 dark:text-red-300",
                                                        label: "High Priority",
                                                        labelColor: "text-red-500/80"
                                                    };
                                                    case 2: // تنبيه (برتقالي)
                                                    return {
                                                        container: "bg-amber-50 dark:bg-amber-950/30 border-amber-200 dark:border-amber-900/50 shadow-amber-500/5",
                                                        icon: <AlertCircle className="w-5 h-5 text-amber-600 dark:text-amber-400" />,
                                                        textColor: "text-amber-800 dark:text-amber-300",
                                                        label: "Attention Required",
                                                        labelColor: "text-amber-500/80"
                                                    };
                                                    default: // معلومات عادية (أزرق)
                                                    return {
                                                        container: "bg-blue-50 dark:bg-blue-950/30 border-blue-200 dark:border-blue-900/50 shadow-blue-500/5",
                                                        icon: <Info className="w-5 h-5 text-blue-600 dark:text-blue-400" />,
                                                        textColor: "text-blue-800 dark:text-blue-300",
                                                        label: "Information",
                                                        labelColor: "text-blue-500/80"
                                                    };
                                                }
                                                };

                                                const style = getStyle(warn.level);

                                                return (
                                                <div
                                                    key={idx}
                                                    className={`group relative flex items-center gap-4 p-4 rounded-2xl border shadow-sm transition-all hover:shadow-md ${style.container}`}
                                                >
                                                    {/* الأيقونة */}
                                                    <div className="flex-shrink-0 animate-in fade-in zoom-in duration-300">
                                                    {style.icon}
                                                    </div>

                                                    {/* محتوى التحذير */}
                                                    <div className="flex-1">
                                                    <div className={`text-[10px] font-black uppercase tracking-widest mb-0.5 ${style.labelColor}`}>
                                                        {style.label}
                                                    </div>
                                                    <p className={`text-base font-bold leading-tight tracking-tight ${style.textColor}`}>
                                                        {warn.message}
                                                    </p>
                                                    </div>

                                                    {/* خط جانبي جمالي لزيادة التأكيد */}
                                                    <div className={`absolute left-0 top-1/4 bottom-1/4 w-1 rounded-r-full opacity-60 ${
                                                    warn.level === 1 ? "bg-red-600" : warn.level === 2 ? "bg-amber-600" : "bg-blue-600"
                                                    }`} />
                                                </div>
                                                );
                                            })}
                                        </div>
                                        )}

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
                                        {/* التعديل هنا: إضافة تمييز لخانة الـ DSL إذا كان هناك خطأ في الملف */}
                                        <div className={`rounded-md transition-all duration-500 ${
                                            analysisData.validation === "Wrong Usage File"
                                            ? "bg-red-500/10 ring-2 ring-red-500/50 animate-pulse p-1"
                                            : ""
                                        }`}>
                                            <CompactResultRow
                                                label="DSL Number"
                                                value={analysisData.DSLno}
                                            />
                                            {analysisData.validation === "Wrong Usage File" && (
                                                <span className="text-[10px] text-red-600 font-bold px-2 block mt-[-4px]">
                                                    Verify DSL Number!
                                                </span>
                                            )}
                                        </div>
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
                                    <CardContent className="p-4 text-sm">
                                        {renderActionButton(analysisData?.weMobile, handleOpenModal)}
                                    </CardContent>
                                </div>
                            ))}

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
const renderActionButton = (data: any, handleOpenModal: (data: any) => void) => {
    return (
        <div className="space-y-4">
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
        </div>
    );
};
