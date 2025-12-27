import React, { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Zap, CreditCard, Layers, AlertCircle, Ban, MessageSquareWarning, Wrench, Clock } from 'lucide-react';

interface HandleActionProps {
    isOpen: boolean;
    setIsOpen: (open: boolean) => void;
    data: any[];
    dslNumber?: string;
}

export function HandleAction({
    isOpen,
    setIsOpen,
    data,
    dslNumber = ''
}: HandleActionProps) {
    const [formData, setFormData] = useState({
        dslNumber: '',
        cstName: '',
        mobileNumber: ''
    });

    React.useEffect(() => {
        if (isOpen && data) {
            const source = Array.isArray(data) ? data[0] : data;

            // استخدم dslNumber من الـ props أولاً، إذا لم يكن متوفراً استخدمه من data
            const incomingDslNumber = dslNumber || source?.DSLnumber || '';

            setFormData(prev => ({
                dslNumber: incomingDslNumber,
                cstName: source?.cst_name || source?.cstName || prev.cstName || '',
                mobileNumber: source?.mobile_number || source?.mobileNumber || prev.mobileNumber || ''
            }));
        }
    }, [isOpen, data, dslNumber]); // أضف dslNumber إلى dependencies

    // حالة لتخزين الأخطاء
    const [errors, setErrors] = useState<{ [key: string]: string }>({});

    if (!data || !Array.isArray(data)) return null;

    const groupedActions = data.reduce((groups: { [key: string]: any[] }, action) => {
        const type = action.type || 'Other Actions';
        if (!groups[type]) groups[type] = [];
        groups[type].push(action);
        return groups;
    }, {});

    const validate = (actionType: string) => {
        const newErrors: { [key: string]: string } = {};

        if (!formData.dslNumber) newErrors.dslNumber = 'DSL Number is required';
        if (!formData.cstName) newErrors.cstName = 'Customer Name is required';

        if (!formData.mobileNumber) {
            newErrors.mobileNumber = 'Mobile is required';
        } else {
            if (!/^\d{11}$/.test(formData.mobileNumber)) {
                newErrors.mobileNumber = 'Mobile must be 11 digits';
            }
            else if (actionType === 'We Mobile Aproval' && !formData.mobileNumber.startsWith('015')) {
                newErrors.mobileNumber = 'Must start with 015 for this action';
            }
        }

        setErrors(newErrors);
        return Object.keys(newErrors).length === 0;
    };

    const handleExecuteAction = (action: any) => {
        if (!validate(action.type)) return;

        let srTypeName = encodeURIComponent(action.sr_name);

        const formattedDsl = "FBB" + (formData.dslNumber.startsWith('0') ? formData.dslNumber.substring(1) : formData.dslNumber);

        let serviceContent = `Customer : ${formData.cstName} , ${action.serviceContent}${formData.mobileNumber}`;

        if (action.sr_type === 'TT') {
            const url = `https://bss.te.eg:12900/csp/sr/business.action?BMEBusiness=srNewSrPage`
                + `&srTypeName=${srTypeName}`
                + `&srTypeId=${action.sr_id}`
                + `&subsNumber=${formattedDsl}`
                + `&serviceContent=${encodeURIComponent(serviceContent)}`
                + `&serviceInfoChar272=${formData.mobileNumber}`
                + `&serviceInfoChar282=${srTypeName}`
                + `&serviceInfoChar276=1`
                + `&serviceInfoChar107=1`
                + `&serviceInfoChar110=2`;
            window.open(url, '_blank');
        } else {
            const url = `https://bss.te.eg:12900/csp/sr/business.action?BMEBusiness=srNewSrPage`
                + `&srTypeName=${srTypeName}`
                + `&srTypeId=${action.sr_id}`
                + `&subsNumber=${formattedDsl}`
                + `&serviceContent=${encodeURIComponent(serviceContent)}`
                + `&serviceInfoChar272=${formData.mobileNumber}`
                + `&serviceInfoChar282=${srTypeName}`
                + `&serviceInfoChar276=1`
                + `&serviceInfoChar107=2`
                + `&serviceInfoChar111=1`;
            window.open(url, '_blank');
        }
    };

    return (
        <Dialog open={isOpen} onOpenChange={(open) => {
            setIsOpen(open);
            if (!open) setErrors({});
        }}>
            <DialogContent className="sm:max-w-[500px] max-h-[90vh] overflow-y-auto bg-white dark:bg-slate-950">
                <DialogHeader>
                    <DialogTitle className="flex items-center gap-2 text-xl border-b pb-4 dark:border-slate-800">
                        <Layers className="w-6 h-6 text-emerald-500" />
                        Execute BSS Actions
                    </DialogTitle>
                </DialogHeader>

                <div className="grid gap-6 py-4">
                    {/* Inputs Section */}
                    <div className="space-y-4 bg-slate-50 dark:bg-slate-900/50 p-4 rounded-xl border dark:border-slate-800">
                        <div className="grid grid-cols-2 gap-4">
                            {/* DSL Number */}
                            <div className="grid gap-1.5">
                                <label className="text-[10px] uppercase font-bold text-slate-500 px-1">DSL Number</label>
                                <Input
                                    className={`h-9 text-xs shadow-sm transition-colors ${errors.dslNumber ? 'border-red-500 focus-visible:ring-red-500' : ''}`}
                                    value={formData.dslNumber}
                                    onChange={(e) => {
                                        setFormData({ ...formData, dslNumber: e.target.value });
                                        if (errors.dslNumber) setErrors({ ...errors, dslNumber: '' });
                                    }}
                                />
                                {errors.dslNumber && <span className="text-[9px] text-red-500 flex items-center gap-1"><AlertCircle className="w-3 h-3" /> {errors.dslNumber}</span>}
                            </div>

                            {/* Mobile Number */}
                            <div className="grid gap-1.5">
                                <label className="text-[10px] uppercase font-bold text-slate-500 px-1">Mobile Number</label>
                                <Input
                                    className={`h-9 text-xs shadow-sm transition-colors ${errors.mobileNumber ? 'border-red-500 focus-visible:ring-red-500' : ''}`}
                                    value={formData.mobileNumber}
                                    onChange={(e) => {
                                        setFormData({ ...formData, mobileNumber: e.target.value });
                                        if (errors.mobileNumber) setErrors({ ...errors, mobileNumber: '' });
                                    }}
                                />
                                {errors.mobileNumber && <span className="text-[9px] text-red-500 flex items-center gap-1"><AlertCircle className="w-3 h-3" /> {errors.mobileNumber}</span>}
                            </div>
                        </div>

                        {/* Customer Name */}
                        <div className="grid gap-1.5">
                            <label className="text-[10px] uppercase font-bold text-slate-500 px-1">Customer Name</label>
                            <Input
                                className={`h-9 text-xs shadow-sm transition-colors ${errors.cstName ? 'border-red-500 focus-visible:ring-red-500' : ''}`}
                                value={formData.cstName}
                                onChange={(e) => {
                                    setFormData({ ...formData, cstName: e.target.value });
                                    if (errors.cstName) setErrors({ ...errors, cstName: '' });
                                }}
                            />
                            {errors.cstName && <span className="text-[9px] text-red-500 flex items-center gap-1"><AlertCircle className="w-3 h-3" /> {errors.cstName}</span>}
                        </div>
                    </div>

                    {/* Grouped Actions Section */}
                    <div className="space-y-6">
                        {Object.keys(groupedActions).map((groupName) => (
                            <div key={groupName} className="space-y-3">
                                <div className="flex items-center gap-2">
                                    <div className="h-[1px] flex-1 bg-emerald-500/20"></div>
                                    <span className="text-[10px] font-black uppercase tracking-[1px] text-emerald-600 dark:text-emerald-500 px-2">
                                        {groupName}
                                    </span>
                                    <div className="h-[1px] flex-1 bg-emerald-500/20"></div>
                                </div>

                                <div className="flex flex-row flex-nowrap gap-2 w-full overflow-hidden py-2 px-1">
                                    {groupedActions[groupName].map((action, index) => {
                                        const isTT = action.sr_type === 'TT';
                                        const isRefuse = action.label === 'Refuse';
                                        const isComplaint = action.label === 'Complaint';

                                        const getButtonTheme = () => {
                                            if (isTT) return "bg-orange-500 hover:bg-orange-600 text-white border-orange-700 shadow-sm";
                                            if (isRefuse) return "bg-red-600 hover:bg-red-700 text-white border-red-800 shadow-sm";
                                            if (isComplaint) return "bg-amber-500 hover:bg-amber-600 text-white border-amber-700 shadow-sm";
                                            return "bg-emerald-600 hover:bg-emerald-700 text-white border-emerald-800 shadow-sm";
                                        };

                                        const renderIcon = () => {
                                            const iconClass = "w-4 h-4 shrink-0";
                                            if (isTT) return <Wrench className={iconClass} />;
                                            if (isRefuse) return <Ban className={iconClass} />;
                                            if (isComplaint) return <MessageSquareWarning className={iconClass} />;
                                            return action.amount ? <CreditCard className={iconClass} /> : <Zap className={iconClass} />;
                                        };

                                        return (
                                            <Button
                                                key={index}
                                                onClick={() => handleExecuteAction(action)}
                                                className={`
                                                    flex-1 flex-shrink min-w-[60px]
                                                    hover:flex-[2.5] focus:flex-[2.5]
                                                    h-auto py-2.5 px-3 rounded-xl border-b-[4px]
                                                    transition-all duration-500 ease-in-out
                                                    active:border-b-0 active:translate-y-[2px]
                                                    flex flex-row items-center justify-center gap-2
                                                    group overflow-hidden
                                                    ${getButtonTheme()}
                                                `}
                                            >
                                                {renderIcon()}
                                                <div className="flex items-center gap-1 text-xs font-black uppercase whitespace-nowrap overflow-hidden">
                                                    <span className="truncate">{action.label}</span>
                                                    <span className="opacity-85 font-medium shrink-0">({action.sr_type})</span>
                                                </div>
                                                {action.sla && (
                                                    <div className="hidden group-hover:flex items-center gap-1 bg-black/20 px-1.5 py-0.5 rounded text-[7px] font-bold ml-auto shrink-0 animate-in fade-in duration-500">
                                                        {action.sla}
                                                    </div>
                                                )}
                                            </Button>
                                        );
                                    })}
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    );
}
