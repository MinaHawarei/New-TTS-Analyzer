import SectionLoader from '@/components/full-screen-loader';
import AppLayout from '@/layouts/app-layout';
import { PlaceholderPattern } from '@/components/ui/placeholder-pattern';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { useState , useRef } from 'react';
import { HandleAction } from "@/components/ui/handle-action-modal";
import {
    Search, Info, CheckCircle2, Hash, Package,
    User, Phone, Ticket, AlertCircle, FileText,
    Activity, Zap, Upload, Calendar, AlertTriangle, Plus, Trash2, Layers, Lock , Cpu
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Textarea } from "@/components/ui/textarea";
import { Checkbox } from "@/components/ui/checkbox";
import { Label } from "@/components/ui/label";
import { route } from 'ziggy-js';
import { ErrorModal } from "@/components/ui/error-modal";
import axios from 'axios';
import { Separator } from "@/components/ui/separator";
import {
  Accordion,
  AccordionContent,
  AccordionItem,
  AccordionTrigger,
} from "@/components/ui/accordion";

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Compensation', href: '#' },
    { title: 'Outage Compensation', href: '#' },
];

export default function OutageCompensation({ packages }: { packages: { id: number; name: string }[] }) {
    const [analysisData, setAnalysisData] = useState<any>(null);
    const [isLoading, setIsLoading] = useState(false);
    const [wasSubmitted, setWasSubmitted] = useState(false);
    const [errorModal, setErrorModal] = useState({ isOpen: false, title: '', message: '', status: null as number | null });
    const [showUnlockConfirm, setShowUnlockConfirm] = useState(false); // هل نظهر رسالة التأكيد؟
    const fileInputRef = useRef<HTMLInputElement>(null);

    const { data, setData, post, processing, progress, reset } = useForm({
        DSLnumber: '',
        selectPackage: '',
        ineligibleDays: '0', // Unpaid Days
        UsageFile: null as File | null,
        FollowUpcase: 'no',
        inputText: '', // Ticket Dump

        outages: [
            { ID: '', outageType: '', From: '', To: '' }
        ]
    });

    const [isActionModalOpen, setIsActionModalOpen] = useState(false);
    const [selectedMobileData, setSelectedMobileData] = useState<any>(null);
    const [isLocked, setIsLocked] = useState(false); // هل الشاشة مقفلة؟

    const errors = {
        DSLnumber: wasSubmitted && !data.DSLnumber,
        inputText: wasSubmitted && !data.inputText,
        selectPackage: wasSubmitted && !data.selectPackage,
    };

    const openDuplicated = (DSLno: string) => {

        if (!DSLno || !DSLno.trim()) {
            console.warn("No Ticket ID provided to the function");
            return;
        }

        if (DSLno.startsWith("0")) {
            DSLno = DSLno.substring(1);
        }

        const urls = [
            `https://bss.te.eg:12900/csp/pbh/business.action?BMEBusiness=pbhRelativeProcess&subsNumber=FBB${DSLno}`,
            `https://bss.te.eg:12900/csp/pbh/business.action?BMEBusiness=srQueryAction&subsNumber=FBB${DSLno}`,
        ];

        urls.forEach((url, index) => {
            window.open(url, `_blank${index}`);
        });
    };
    const handleOpenModal = (weMobileData: any) => {
        setSelectedMobileData(weMobileData);
        setIsActionModalOpen(true);
    };

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

    const addOutageRow = () => {
        setData('outages', [...data.outages, { ID: '', outageType: '', From: '', To: '' }]);
    };

    const removeOutageRow = (index: number) => {
        const newOutages = [...data.outages];
        newOutages.splice(index, 1);
        setData('outages', newOutages);
    };

    const updateOutageRow = (index: number, field: string, value: any) => {
        const newOutages = [...data.outages];
        newOutages[index] = {
            ...newOutages[index],
            [field]: value
        };
        setData('outages', newOutages);
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        setWasSubmitted(true);

        // التحقق من الحقول الأساسية
        if (!data.DSLnumber || !data.selectPackage) return;
        const userUsedTextarea = data.inputText?.trim().length > 0;

    const userUsedOutages =
        data.outages &&
        data.outages.length > 0 &&
        data.outages.some(o =>
            (o.ID && o.ID.trim()) ||
            (o.outageType && o.outageType.trim()) ||
            (o.From && o.From.trim()) ||
            (o.To && o.To.trim())
        );

    // ❌ لا جدول ولا Dump
    if (!userUsedTextarea && !userUsedOutages) {
        setErrorModal({
            isOpen: true,
            title: 'Validation Error',
            message: 'You must either fill Outage Incidents OR paste outage dump text.',
            status: 400
        });
        return;
    }

    // ❌ الاتنين مستخدمين
    if (userUsedTextarea && userUsedOutages) {
        setErrorModal({
            isOpen: true,
            title: 'Validation Error',
            message: 'Please use ONLY one method: either Outage Incidents OR Dump text — not both.',
            status: 400
        });
        return;
    }

        setIsLoading(true);
        setAnalysisData(null);

        // 1. إنشاء كائن FormData جديد
        const formData = new FormData();

        // 2. إضافة البيانات الأساسية
        formData.append('DSLnumber', data.DSLnumber);
        formData.append('selectPackage', data.selectPackage);
        formData.append('ineligibleDays', data.ineligibleDays);
        formData.append('FollowUpcase', data.FollowUpcase);
        formData.append('inputText', data.inputText);

        // 3. إضافة الملف إذا وُجد
        if (data.UsageFile) {
            formData.append('UsageFile', data.UsageFile);
        }

        // 4. إرسال المصفوفة بشكل مفصل (Indexed Array)
        // هذا هو الجزء الأهم ليتمكن Laravel من قراءتها كـ Array في الـ Request
        data.outages.forEach((outage, index) => {
            // نستخدم نفس الأسماء التي يتوقعها الباك-إند الخاص بك
            formData.append(`outages[${index}][ID]`, outage.ID || '');
            formData.append(`outages[${index}][outageType]`, outage.outageType || '');
            formData.append(`outages[${index}][From]`, outage.From || '');
            formData.append(`outages[${index}][To]`, outage.To || '');
        });

        // 5. إرسال الطلب عبر Axios
        axios.post(route('outage.data'), formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        })
        .then((response) => {
            setAnalysisData(response.data);
            setWasSubmitted(false);
            // يمكنك إضافة تنبيه نجاح هنا
        })
        .catch((err) => {
            console.error("Submission Error:", err);
            setErrorModal({
                isOpen: true,
                title: 'Server Error',
                message: err.response?.data?.message || 'Check backend validation or server logs',
                status: err.response?.status
            });
        })
        .finally(() => setIsLoading(false));
    };
    const isInvalid = (field: keyof typeof data) => wasSubmitted && !data[field];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Outage Compensation" />

            <div className="container mx-auto space-y-6 pb-10 max-w-5xl">

                <form onSubmit={handleSubmit}>

                    {/* Main Input Card */}
                    <Card className="border-primary/10 shadow-lg overflow-hidden">
                        <CardHeader className="bg-gradient-to-r from-primary/5 via-primary/0 to-transparent py-4 border-b border-primary/5">
                            <CardTitle className="text-lg flex items-center gap-2 text-primary font-bold uppercase tracking-tight">
                                <Layers className="w-5 h-5" />
                                Outage Compensation
                            </CardTitle>
                            <CardDescription>
                                Enter the customer details and outage logs below.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="p-8 space-y-8">
                            <Separator className="bg-border/60" />

                            {/* Section 2: General Information */}
                            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">

                                {/* DSL Number */}
                                <div className="space-y-1.5">
                                    <label className="text-[10px] font-bold text-muted-foreground uppercase ml-1">DSL Number</label>
                                    <div className="relative">
                                        <Phone className="absolute left-3 top-2.5 w-4 h-4 text-muted-foreground" />
                                        <Input
                                            placeholder="DSL Number"
                                            className={`pl-9 ${errors.DSLnumber ? "border-red-500 focus:border-red-500" : ""}`}
                                            value={data.DSLnumber}
                                            onChange={e => setData('DSLnumber', e.target.value)}
                                            onKeyDown={(e) => {
                                                if (e.key === 'Enter') {
                                                    e.preventDefault();
                                                    openDuplicated(data.DSLnumber);
                                                }
                                            }}
                                            onPaste={(e) => {
                                                e.preventDefault();
                                                const pastedText = e.clipboardData.getData('text').trim();

                                                setData('DSLnumber', pastedText);

                                                openDuplicated(pastedText);
                                            }}
                                        />
                                        {errors.DSLnumber && (
                                            <p className="text-xs text-red-500 mt-1">DSL Number is required</p>
                                        )}
                                    </div>
                                </div>

                                {/* Package */}
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

                                {/* Unpaid Days */}
                                <div className="space-y-1.5">
                                    <label className="text-[10px] font-bold text-muted-foreground uppercase ml-1">UnPaid Days</label>
                                    <Input
                                        type="number"
                                        min="0"
                                        className="h-10"
                                        value={data.ineligibleDays}
                                        onChange={e => setData('ineligibleDays', e.target.value)}
                                    />
                                </div>

                                {/* Follow Up */}
                                <div className="space-y-1.5">
                                    <label className="text-[10px] font-bold text-muted-foreground uppercase ml-1">CST Follows Up?</label>
                                    <Select value={data.FollowUpcase} onValueChange={(v) => setData('FollowUpcase', v)}>
                                        <SelectTrigger className="h-10">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="no">No</SelectItem>
                                            <SelectItem value="yes">Yes</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>

                            {/* Section 1: Dynamic Outages */}
                            <div className="space-y-4">
                                <div className="flex items-center justify-between">
                                    <label className="text-xs font-bold text-muted-foreground uppercase tracking-wider flex items-center gap-2">
                                        <AlertCircle className="w-4 h-4" /> Outage Incidents
                                    </label>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        onClick={addOutageRow}
                                        className="h-8 text-xs border-dashed border-primary/40 hover:border-primary hover:bg-primary/5 transition-colors"
                                    >
                                        <Plus className="w-3.5 h-3.5 mr-1.5" /> Add Outage
                                    </Button>
                                </div>

                                <div className="space-y-3">
                                    {data.outages?.map((outage, index) => (
                                        <div key={index}
                                            className="group relative flex flex-col md:flex-row gap-3 items-end p-3 rounded-lg border bg-card/40 hover:bg-accent/5 transition-all duration-200 shadow-sm">

                                            {/* رقم التسلسل الصغير في الزاوية */}
                                            <div className="absolute -left-2 -top-2 w-5 h-5 bg-primary text-[10px] text-white rounded-full flex items-center justify-center font-bold shadow-sm z-10">
                                                {index + 1}
                                            </div>

                                            {/* Outage ID - مساحة صغيرة (Flex 2) */}
                                            <div className="flex-1 md:flex-[1.5] w-full space-y-1">
                                                <label className="text-[10px] font-bold text-muted-foreground/80 uppercase px-1 flex items-center gap-1">
                                                    <Hash className="w-3 h-3" /> Outage ID
                                                </label>
                                                <Input
                                                    placeholder="Outage #"
                                                    className="h-9 text-xs focus-visible:ring-primary/30"
                                                    value={outage.ID}
                                                    onChange={(e) => updateOutageRow(index, 'ID', e.target.value)}
                                                />
                                            </div>

                                            {/* Type - مساحة متوسطة (Flex 2) */}
                                            <div className="flex-1 md:flex-[2] w-full space-y-1">
                                                <label className="text-[10px] font-bold text-muted-foreground/80 uppercase px-1">Problem Type</label>
                                                <Select value={outage.outageType} onValueChange={(v) => updateOutageRow(index, 'outageType', v)}>
                                                    <SelectTrigger className="h-9 text-xs">
                                                        <SelectValue placeholder="Select" />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        <SelectItem value="Down">Down</SelectItem>
                                                        <SelectItem value="Major Fault - Down">Down - (سرقة - اتلاف - احلال - طوارئ -حياة كريمة - صيانة)</SelectItem>
                                                        <SelectItem value="Major Fault">Major Fault</SelectItem>
                                                        <SelectItem value="Major Fault - Maintenance">Major Fault - صيانة</SelectItem>
                                                        <SelectItem value="Major Fault - UNMS">Major Fault - UNMS</SelectItem>
                                                        <SelectItem value="No Browsing">No Browsing</SelectItem>
                                                        <SelectItem value="NAS Port ID">NAS Port ID</SelectItem>
                                                        <SelectItem value="Unable to obtain ip">Unable to obtain ip</SelectItem>
                                                        <SelectItem value="Wrong Card & Port">Wrong Card & Port</SelectItem>
                                                        <SelectItem value="Down Management">Down Management</SelectItem>
                                                        <SelectItem value="Electricity Outage">Electricity Outage</SelectItem>
                                                        <SelectItem value="Slowness">Slowness</SelectItem>
                                                        <SelectItem value="Unstable">Unstable</SelectItem>
                                                        <SelectItem value="Service Degradation">Service Degradation</SelectItem>
                                                        <SelectItem value="Ehlal Cabin MSAN">Ehlal Cabin MSAN</SelectItem>
                                                        <SelectItem value="Physical instability">Physical instability</SelectItem>
                                                        <SelectItem value="Logical Instability">Logical Instability</SelectItem>
                                                        <SelectItem value="Replacement">Replacement</SelectItem>
                                                    </SelectContent>
                                                </Select>
                                            </div>

                                            {/* From Date - مساحة عرضية كبيرة (Flex 3) */}
                                            <div className="flex-1 md:flex-[3] w-full space-y-1">
                                                <label className="text-[10px] font-bold text-muted-foreground/80 uppercase px-1 flex items-center gap-1">
                                                    <Calendar className="w-3 h-3 text-emerald-500" /> From
                                                </label>
                                                <Input
                                                    type="datetime-local"
                                                    className="h-9 text-xs appearance-none"
                                                    value={outage.From}
                                                    onChange={(e) => updateOutageRow(index, 'From', e.target.value)}
                                                />
                                            </div>

                                            {/* To Date - مساحة عرضية كبيرة (Flex 3) */}
                                            <div className="flex-1 md:flex-[3] w-full space-y-1">
                                                <label className="text-[10px] font-bold text-muted-foreground/80 uppercase px-1 flex items-center gap-1">
                                                    <Calendar className="w-3 h-3 text-red-500" /> To
                                                </label>
                                                <Input
                                                    type="datetime-local"
                                                    className="h-9 text-xs"
                                                    value={outage.To}
                                                    onChange={(e) => updateOutageRow(index, 'To', e.target.value)}
                                                />
                                            </div>

                                            {/* Action - زر الحذف */}
                                            <div className="flex items-center justify-end pb-0.5">
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    size="icon"
                                                    className="h-9 w-9 text-muted-foreground hover:text-destructive hover:bg-destructive/5"
                                                    onClick={() => removeOutageRow(index)}
                                                    disabled={data.outages.length <= 1}
                                                >
                                                    <Trash2 className="w-4 h-4" />
                                                </Button>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>

                            <Separator className="bg-border/60" />

                            {/* Section 3: Dump & File */}
                            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div className="space-y-1.5 md:col-span-2">
                                    <label className="text-[10px] font-bold text-muted-foreground uppercase ml-1">Ticket Dump / Details</label>
                                    <Textarea
                                        placeholder="Paste outage details here..."
                                        className="max-h-[100px] min-h-[100px] font-mono text-xs resize-none bg-muted/20 focus:bg-background transition-colors"
                                        value={data.inputText}
                                        onChange={e => setData('inputText', e.target.value)}
                                    />
                                </div>

                                <div className="space-y-1.5">
                                    <label className="text-[10px] font-bold text-muted-foreground uppercase ml-1">Usage File (Excel)</label>
                                    <div className="h-[100px] border-2 border-dashed rounded-lg flex flex-col items-center justify-center text-center hover:bg-muted/50 hover:border-primary/50 transition-all cursor-pointer relative group">
                                        <Input
                                            ref={fileInputRef}
                                            type="file"
                                            accept=".xls,.xlsx"
                                            className="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10"
                                            onChange={e => setData('UsageFile', e.target.files ? e.target.files[0] : null)}
                                        />
                                        <div className="group-hover:scale-110 transition-transform duration-300">
                                            <Upload className="w-6 h-6 text-muted-foreground mb-2 mx-auto" />
                                        </div>
                                        <span className="text-sm font-medium text-muted-foreground truncate max-w-[90%] px-4">
                                            {data.UsageFile ? (
                                                <span className="text-primary font-semibold">{data.UsageFile.name}</span>
                                            ) : (
                                                "Click to upload usage file"
                                            )}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            {/* Submit Action */}
                            <div className="pt-4 flex justify-end gap-4 flex-wrap md:flex-nowrap">

                                <Button
                                    className="w-full md:w-auto px-12 py-6 text-lg bg-primary hover:bg-primary/90 shadow-lg hover:shadow-primary/20 transition-all active:scale-95"
                                    type="submit"
                                    disabled={isLoading}
                                >
                                    {isLoading ? "Calculating…..." : "Calculate Compensation"}
                                    <Zap className="w-5 h-5 ml-2 fill-current animate-pulse" />
                                </Button>

                                <Button
                                    variant="destructive"
                                    className="w-full md:w-auto px-12 py-6 text-lg shadow-lg hover:shadow-destructive/20 transition-all active:scale-95"
                                    onClick={() => {
                                        reset();
                                        setAnalysisData(null);
                                        setWasSubmitted(false);

                                        if (fileInputRef.current) {
                                            fileInputRef.current.value = '';
                                        }
                                    }}
                                >
                                    Reset
                                </Button>

                            </div>

                        </CardContent>
                    </Card>

                </form>


                {/* Part 3: Results */}
                <div className="min-h-[300px] transition-all duration-500 ease-in-out">
                <Card className="w-full animate-in fade-in slide-in-from-bottom-8 duration-700 border-primary/10 shadow-2xl ring-1 ring-primary/5">
                    {/* Card Header */}
                    <CardHeader className="border-b bg-muted/20 flex flex-row items-center justify-between py-4">
                    <CardTitle className="text-lg flex items-center gap-2">
                        <FileText className="w-5 h-5 text-primary" />
                        Outage Compensation
                    </CardTitle>
                    </CardHeader>

                    {/* Card Content */}
                    <CardContent className="flex-1 relative p-6 overflow-y-auto min-h-[400px]">
                    {/* Loader */}
                    {isLoading && (
                        <div className="min-h-[300px] absolute inset-0 z-50 flex items-center justify-center bg-background/40 backdrop-blur-[2px]">
                            <SectionLoader text="Analyzing & Calculating Outage data, please wait" />
                        </div>
                    )}

                    {/* Placeholder */}
                    {!isLoading && !analysisData && (
                        <div className="flex flex-col items-center justify-center h-full text-muted-foreground opacity-40">
                        <PlaceholderPattern className="w-16 h-16 mb-4" />
                        <p className="text-sm font-medium">
                            Input data and submit to see results
                        </p>
                        </div>
                    )}

                    {/* Results */}
                    {!isLoading && analysisData && (
                        <div className="space-y-6">
                        {/* --- Warnings --- */}
                        {analysisData.warnings && analysisData.warnings.length > 0 && (
                            <div className="w-full max-w-2xl mx-auto space-y-3 mb-6">
                            {analysisData.warnings
                                .sort((a: { level: number }, b: { level: number }) => a.level - b.level)
                                .map((warn: { level: number; message: string }, idx: number) => {
                                const getStyle = (level: number) => {
                                    switch (level) {
                                    case 1: return {
                                        container: "bg-red-50 dark:bg-red-950/30 border-red-200 dark:border-red-900/50 shadow-red-500/5",
                                        icon: <AlertTriangle className="w-5 h-5 text-red-600 dark:text-red-400" />,
                                        textColor: "text-red-800 dark:text-red-300",
                                        labelColor: "text-red-500/80",
                                    };
                                    case 2: return {
                                        container: "bg-amber-50 dark:bg-amber-950/30 border-amber-200 dark:border-amber-900/50 shadow-amber-500/5",
                                        icon: <AlertCircle className="w-5 h-5 text-amber-600 dark:text-amber-400" />,
                                        textColor: "text-amber-800 dark:text-amber-300",
                                        labelColor: "text-amber-500/80",
                                    };
                                    default: return {
                                        container: "bg-blue-50 dark:bg-blue-950/30 border-blue-200 dark:border-blue-900/50 shadow-blue-500/5",
                                        icon: <Info className="w-5 h-5 text-blue-600 dark:text-blue-400" />,
                                        textColor: "text-blue-800 dark:text-blue-300",
                                        labelColor: "text-blue-500/80",
                                    };
                                    }
                                };
                                const style = getStyle(warn.level);
                                return (
                                    <div key={idx} className={`group relative flex items-center gap-4 p-4 rounded-2xl border shadow-sm transition-all hover:shadow-md ${style.container}`}>
                                    <div className="flex-shrink-0 animate-in fade-in zoom-in duration-300">{style.icon}</div>
                                    <div className="flex-1">
                                        <div className={`text-[10px] font-black uppercase tracking-widest mb-0.5 ${style.labelColor}`}>Warning</div>
                                        <p className={`text-base font-bold leading-tight tracking-tight ${style.textColor}`}>{warn.message}</p>
                                    </div>
                                    <div className={`absolute left-0 top-1/4 bottom-1/4 w-1 rounded-r-full opacity-60 ${warn.level === 1 ? "bg-red-600" : warn.level === 2 ? "bg-amber-600" : "bg-blue-600"}`} />
                                    </div>
                                );
                                })}
                            </div>
                        )}

                        {/* --- Main Dashboard Container --- */}
                        <div className="flex flex-col lg:flex-row gap-4 mb-6 items-stretch">

                        {/* 👈 Left Column: Validation & Dates (Takes ~70%) */}
                        <div className="lg:w-[70%] flex flex-col gap-3">

                            {/* 1. Validation Section (Main Box) */}
                            <div className="rounded-2xl border-2 overflow-hidden flex flex-col md:flex-row shadow-sm transition-all shrink-0"
                                style={{ borderColor: analysisData.validationcolor }}>

                            <div className="flex-1 p-5 flex flex-col justify-center gap-1"
                                style={{ backgroundColor: `${analysisData.validationcolor}10` }}>
                                <span className="text-[10px] font-black uppercase tracking-widest opacity-60">Validation Status</span>
                                <h3 className="text-2xl font-black leading-tight" style={{ color: analysisData.validationcolor }}>
                                {analysisData.validation}
                                </h3>
                                {analysisData.validationReason && (
                                <p className="text-xs font-medium opacity-80 italic flex items-center gap-1.5 mt-1">
                                    <span className="w-1.5 h-1.5 rounded-full bg-current opacity-50" />
                                    {analysisData.validationReason}
                                </p>
                                )}
                            </div>

                            {analysisData.validDuration !== null && (
                                <div className="md:w-32 flex flex-col items-center justify-center p-4 border-t md:border-t-0 md:border-l border-current/20 shrink-0 text-white"
                                    style={{ backgroundColor: analysisData.validationcolor }}>
                                <span className="text-[9px] font-black opacity-80 uppercase tracking-tighter text-center">Valid Dur.</span>
                                <div className="text-center">
                                    <span className="text-3xl font-black block leading-none">
                                    {analysisData.validDuration < 1 ? Math.ceil(analysisData.validDuration * 24) : analysisData.validDuration}
                                    </span>
                                    <span className="text-[10px] font-bold uppercase">
                                    {analysisData.validDuration === 1 ? "Day" : analysisData.validDuration < 1 ? "Hours" : "Days"}
                                    </span>
                                </div>
                                </div>
                            )}
                            </div>

                            {/* 2. Dates Grid (2 Above, 2 Below) - Filled to match height */}
                            <div className="grid grid-cols-2 gap-3 flex-1">
                                {/* العنصر الأول: Start */}
                                <div className="bg-muted/30 p-4 rounded-2xl border border-border/50 shadow-inner flex flex-col justify-center">
                                    <CompactResultRow label="Start" value={analysisData.startDate} />
                                </div>

                                {/* العنصر الثاني: End */}
                                <div className="bg-muted/30 p-4 rounded-2xl border border-border/50 shadow-inner flex flex-col justify-center">
                                    <CompactResultRow label="End" value={analysisData.closeDate} />
                                </div>

                                {/* العنصر الثالث: Total Duration (ياخذ العرض كامل) */}
                                <div className="col-span-2 bg-muted/30 p-4 rounded-2xl border border-border/50 shadow-inner flex flex-col justify-center items-center text-center">
                                    <CompactResultRow
                                    label="Total Duration"
                                    value={analysisData.totalDuration}
                                    color="oklch(0.6 0.2 250)"
                                    bold
                                    />
                                </div>
                            </div>
                        </div>

                        {/* 👈 Right Column: Sidebar Info (Takes ~30%) */}
                        <div className="lg:w-[30%]">
                            <div className="h-full bg-card dark:bg-slate-900/40 p-5 rounded-3xl border border-border/60 shadow-sm flex flex-col">
                            <div className="space-y-5 flex-1">


                                <div className="flex flex-col gap-1 pt-4 border-t border-border/40">
                                <span className="text-[10px] font-bold text-muted-foreground uppercase tracking-widest">DSL Number</span>
                                <div className="flex items-center gap-2">
                                    <p className={`text-lg font-mono font-black ${analysisData.validation === "Wrong Usage File" ? "text-red-500" : "text-primary"}`}>
                                    {analysisData.DSLno}
                                    </p>
                                    {analysisData.validation === "Wrong Usage File" && (
                                        <Badge variant="destructive" className="h-4 text-[8px] animate-pulse">Verify!</Badge>
                                    )}
                                </div>
                                </div>
                                <div className="flex flex-col gap-1">
                                    <span className="text-[10px] font-bold text-muted-foreground uppercase tracking-widest">Closed From</span>
                                    <p className="text-sm font-black text-foreground/90 leading-tight">{analysisData.closedDate}</p>
                                </div>

                                <div className="flex flex-col gap-1 pt-4 border-t border-border/40">
                                <span className="text-[10px] font-bold text-muted-foreground uppercase tracking-widest">Package</span>
                                <div className="bg-secondary/50 p-2 rounded-lg border border-border/20 mt-1">
                                    <p className="text-[14px] font-bold text-center truncate">{analysisData.mainpackage}</p>
                                </div>
                                </div>
                            </div>
                            </div>
                        </div>
                        </div>

                        {/* --- Compensation Section --- */}
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div className="p-3 rounded-lg border border-blue-100 bg-blue-50/50 dark:bg-blue-950/10">
                                <span className="text-[10px] text-blue-600 font-bold uppercase flex justify-between">
                                    free Quota
                                    {Number(analysisData.satisfaction) > 0 && (
                                    <span className="text-[20px] bg-orange-100 text-orange-600 px-1.5 py-0.5 rounded-full flex items-center gap-1 animate-bounce-subtle">
                                        🎁 +{analysisData.satisfaction} GB
                                    </span>
                                    )}
                                </span>
                                <div className="flex flex-col mt-1 justify-between">
                                    <span className="text-3xl font-bold">{analysisData.compensationGB} GB</span>
                                    <div className="flex items-center gap-1.5">
                                    <Badge variant="secondary" className="font-mono py-0 px-1.5 text-[12px]">
                                        {Number(analysisData.compensationGB) * 1024 * 1024 * 1024}
                                    </Badge>
                                    <span className="text-[11px] text-muted-foreground font-bold italic">Bytes</span>
                                    </div>
                                </div>
                                {analysisData.hwoAddGB && <p className="text-[14px] mt-1 text-muted-foreground italic">{analysisData.hwoAddGB}</p>}
                            </div>

                            <div className="p-3 rounded-lg border border-green-100 bg-green-50/50 dark:bg-green-950/10">
                                <span className="text-[10px] text-green-600 font-bold uppercase flex justify-between">
                                    Amount
                                    {Number(analysisData.satisfaction) > 0 && Number(analysisData.compensationLE) > 0 && (
                                    <span className="text-[20px] bg-orange-100 text-orange-600 px-1.5 py-0.5 rounded-full flex items-center gap-1 animate-bounce-subtle">
                                        🎁 +{analysisData.satisfaction}GB
                                    </span>
                                    )}
                                </span>
                                <div className="flex justify-between items-end mt-1">
                                    <span className="text-3xl font-bold">{analysisData.compensationLE} EGP</span>
                                </div>
                                {analysisData.hwoAddLE && <p className="text-[14px] mt-1 text-muted-foreground italic">{analysisData.hwoAddLE}</p>}
                            </div>
                        </div>



                        {/* --- Usage Accordion --- */}
                        {analysisData.usageCollectionData && (
                            <Accordion type="single" collapsible className="w-full px-1 mt-4">
                            <AccordionItem value="usage-history" className="border-none">
                                <AccordionTrigger className="py-2 px-3 bg-muted/20 hover:bg-muted/30 rounded-md text-[14px] h-9 transition-all">
                                <div className="flex items-center gap-2 font-bold text-primary/80">
                                    <Activity className="w-4 h-4" />
                                    Show Daily Usage Details
                                </div>
                                </AccordionTrigger>
                                <AccordionContent className="mt-2 p-0 rounded-md overflow-y-auto max-h-[400px] shadow-inner border bg-white border-gray-200 dark:bg-slate-950 dark:border-white/5">
                                <div className="flex flex-col">
                                    {Array.isArray(analysisData.usageCollectionData) && analysisData.usageCollectionData.length > 0 ? (
                                    analysisData.usageCollectionData.map((item: any, index: number) => (
                                        <div key={index} className="p-3 border-b last:border-none transition-colors group border-gray-200 hover:bg-gray-50 dark:border-white/5 dark:hover:bg-white/[0.03]">
                                        <div className="grid grid-cols-4 items-center mb-1.5 gap-2">
                                            <div className="col-span-1 flex items-center gap-2">
                                            <span className="font-bold text-[14px] flex items-center gap-2 min-w-[120px]" style={{ color: item.color }}>
                                                <span className="w-1.5 h-1.5 rounded-full group-hover:scale-150 transition-transform" style={{ backgroundColor: item.color }} />
                                                {item.date}
                                            </span>
                                            </div>
                                            <div className="col-span-1 flex items-center justify-end gap-1.5">
                                            <span className="text-gray-700 bg-gray-100 px-1.5 py-0.5 rounded font-black dark:text-slate-200 dark:bg-white/10">
                                                {item.usage} {item.unit}
                                            </span>
                                            </div>
                                            <div className="col-span-1 flex justify-center">
                                            {item.is_high ? (
                                                <span className="text-[14px] text-orange-600 bg-orange-100 px-1.5 py-0.5 rounded-full font-bold animate-pulse dark:bg-orange-500/10 dark:text-orange-400 whitespace-nowrap">
                                                High Usage
                                                </span>
                                            ) : (
                                                <span className="invisible text-[14px] px-1.5 py-0.5 whitespace-nowrap">Placeholder</span>
                                            )}
                                            </div>
                                            <div className="col-span-1 flex justify-end">
                                            <span className="text-[12px] text-slate-500 font-mono font-bold uppercase tracking-widest opacity-60">
                                                Day {index + 1}
                                            </span>
                                            </div>
                                        </div>
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

                        {/* --- Usage Message --- */}
                        {analysisData.usageMessage && (
                            <div className="mt-2.5 py-2 px-3 bg-red-500/5 border-l-2 border-red-500 rounded-r-sm">
                            <p className="text-red-600 dark:text-red-400 text-[14px] font-bold leading-relaxed italic">
                                {analysisData.usageMessage}
                            </p>
                            </div>
                        )}

                        {/* --- Action Buttons --- */}
                        <CardContent className="p-4 text-sm">
                            {renderActionButton(analysisData?.weMobile, handleOpenModal)}
                        </CardContent>

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
                dslNumber={data.DSLnumber} // أضف هذا السطر
            />

            <ErrorModal
                isOpen={errorModal.isOpen}
                onClose={() => setErrorModal({ ...errorModal, isOpen: false })}
                title={errorModal.title}
                message={errorModal.message}
                status={errorModal.status}
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

function ResultRow({ label, value, bold }: { label: string, value: string, bold?: boolean }) {
    return (
        <div className="flex justify-between items-center py-2 border-b border-border/40 last:border-0 border-dashed">
            <span className="text-xs text-muted-foreground uppercase tracking-wide">{label}</span>
            <span className={`text-sm ${bold ? 'font-bold text-primary' : 'font-medium'}`}>{value || 'N/A'}</span>
        </div>
    );
}
