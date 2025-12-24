<?php

namespace App\Models\TTS;

use Carbon\Carbon;
use Carbon\CarbonInterval;
use App\Services\AnalyzerApiService;


class Validation
{
    public static function validate($tkt_id,$data,$orgData, $EscalationTimes, $selectproblem, $PackageId , $curuantSupportPool_all )
    {

        $agentWemobile = [1,24,25,26,27,28,42,43];
        $TlWemobile = [2,3,4,7,10,32,38,39,40,44];
        $CLMWemobile = [5,6,8,9,11,12,13,14,15,16,17,18,19,20,21,22,23,29,30,31,33,34,35,36,37,41];
        $orignalEscalationTimes = $EscalationTimes;
        $delayId = null;
        $reworkId = null;
        $reassignId = null;
        $accelerationId = null;

        $api = new AnalyzerApiService();
        $apiResponse = $api->send($tkt_id);

       if ($apiResponse) {
            $delayId = collect($apiResponse['delay'] ?? [])->pluck('del_id')->first();
            $reworkId = collect($apiResponse['rework'] ?? [])->pluck('del_id')->first();
            $reassignId = collect($apiResponse['reassign'] ?? [])->pluck('del_id')->first();
            $accelerationId = collect($apiResponse['acceleration'] ?? [])->pluck('id')->first();
        }


        $i = $EscalationTimes - 1;
        $FirstsupportPools = [
            'MCU Call Center',
            'CC Second Level Support',
            'CC-Service Activation',
            'CC-Online Support',
            'Digital Data Chat',
            'Business Technical Support',
            'I Care',
            'CSI'
        ];

        $lastClodeCode = 0 ;
        $wiki = '';
        $needWeMobile = false;
        if (empty($orgData)) {
            return ['validation' => false, 'reason' => 'No data available'];
        }

        //$weMobileMessage = "<strong style='color: red;'>not eligible for we mobile compensation</strong>";
        $weMobileMessage = "not eligible for we mobile compensation";
        $MajorFaultCodes = [20,81,82,102];
        $ticket_close_time = '';
        $ticket_close_time = $orgData[0]->ticket_close_time;
        $tktStillOpen = false;
        $status = $orgData[0]->status ;
        $supportGroups = [
                ['pool' => 'IU Maintenance', 'SLA' => 86400],
                ['pool' => 'NOC', 'SLA' => 7200],
                ['pool' => 'Maintenance Visits', 'SLA' => 86400],
                ['pool' => 'Data Center Unit - DCU', 'SLA' => 7200],
                ['pool' => 'FO-Fiber', 'SLA' => 86400],
                ['pool' => 'Fiber(Regions)', 'SLA' => 86400],
                ['pool' => 'Installation - Operations', 'SLA' => 86400],
                ['pool' => 'FTTH-Support', 'SLA' => 86400],
                ['pool' => 'SLS-FTTH', 'SLA' => 7200],
                ['pool' => 'Pilot-SLS', 'SLA' => 7200],
                ['pool' => 'Pilot - Follow up', 'SLA' => 7200],
                ['pool' => 'Pilot-Follow up', 'SLA' => 7200],
                ['pool' => 'Pending Fixing TE - IU', 'SLA' => 259200],
                ['pool' => 'Second Level Advanced', 'SLA' => 7200],
                ['pool' => 'customer 360', 'SLA' => 3600],
                ['pool' => 'SLS-IVR Automation', 'SLA' => 3600],
                ['pool' => 'OPNETSEC', 'SLA' => 9999999999999999],
                ['pool' => 'Openetsec [Operation Network Security]', 'SLA' => 9999999999999999],
            ];
         $MCU_Field_Support_pools = [
            'MCU Field Support',
            'Mansoura MCU Field Support',
            'Alex MCU Field Support'
        ];
        $tktvisit = false ;
        if ($selectproblem == 'Installation' || $selectproblem == 'technical visits' || str_contains($selectproblem, 'visit')) {
            $supportGroups[] = ['pool' => 'Transfered: MCU Field Support', 'SLA' => 432000];
            $supportGroups[] = ['pool' => 'Transfered: Mansoura MCU Field Support', 'SLA' => 432000];
            $supportGroups[] = ['pool' => 'Transfered: Alex MCU Field Support', 'SLA' => 432000];
            $tktvisit = true ;
        }
        $SLS_owner = false;
        if($selectproblem == 'logical instability - no multiple logs' || $selectproblem == 'browsing - certain sites'){
            $supportGroups[] = ['pool' => 'CC Second Level Support', 'SLA' => 18000 ];
            $SLS_owner = true;

        }


        $now = Carbon::now();

        // نحدد بداية ونهاية فترة "التوقف"
        $today8am  = Carbon::today()->setHour(8)->setMinute(0)->setSecond(0);
        $today8pm  = Carbon::today()->setHour(20)->setMinute(0)->setSecond(0);
        $tomorrow8am = $today8am->copy()->addDay();

        $extraSeconds = 0;

        if ($now->between($today8pm, $tomorrow8am)) {
            // الحالة 1: الوقت بين 8 مساءً و 12 منتصف الليل
            $extraSeconds = $now->diffInSeconds($tomorrow8am, false);

        } elseif ($now->between(Carbon::today(), $today8am)) {
            // الحالة 2: الوقت بين 12 منتصف الليل و 8 صباحاً
            $extraSeconds = $now->diffInSeconds($today8am, false);
        }



        //$downCases = ['Data Down', 'Data And VoiceDown', 'WCAP', 'Wrong Nas Port', 'Unable To Obtain IP', 'Browsing', 'Voice overlapping' , 'voice overlapping' , 'voice down (Data Down impacted)'];
        $InstabilityCases = ['Physical Instability', 'Bad Line Quality', 'BLQ', 'Logical Instability', 'Slowness', 'Speed', 'Need Optimization', 'logical instability - no multiple logs',
                            'physical instability', 'bad line quality', 'blq', 'logical instability', 'slowness', 'speed', 'need optimization' , 'voice down (Data Instability impacted)'];
        $physicalCases = ['Data Down', 'Data And Voice Down', 'WCAP', 'Wrong Nas Port', 'Physical Instability', 'Bad Line Quality', 'BLQ', 'Installation', 'Technical Visits','Voice and Data Down', 'DataOnly',
                            'data down', 'data and voice down', 'wcap', 'wrong nas port', 'physical instability', 'bad line quality', 'blq', 'installation', 'technical visits','voice and data down' , 'voice down (Data Instability impacted)' , 'voice down (Data Down impacted)'];
        $logicalCases = ['wrong nas port', 'unable to obtain ip', 'browsing', 'logical instability', 'slowness', 'speed', 'need optimization', 'logical instability - no multiple logs' , 'browsing - certain sites', 'option pack',
                        'wrong nas port', 'unable to obtain ip', 'browsing', 'logical instability', 'slowness', 'speed', 'need optimization', 'wrong profile','adsl/vdsl' , 'Unsupported Services','unsupported services'];



        $IslogicalCases = false;
        $SLSupdate = '';
        $negativeCounter = 0;
        $reassignCounter = 0;
        if (in_array($selectproblem, $InstabilityCases) && !in_array($orgData[0]->close_code, $MajorFaultCodes) ) {
            $needWeMobile = true;
        }

        // fix last escalation but not included in the orgData

        if (in_array($selectproblem, $logicalCases)) {
            $IslogicalCases = true;

            // استخراج كل التحويلات والتواريخ
            preg_match_all('/(\d{2}-\d{2}-\d{4},\s*\d{2}:\d{2}\s*(?:AM|PM))(?:.|\s)*?Transfered:\s*([^\n\d]+)/', $data, $matches, PREG_OFFSET_CAPTURE);

            $lastMatch = null;

            if (!empty($matches[2])) {
                // نبدأ من آخر تحويل ونتراجع للخلف لحد ما نلاقي تحويل موجود في supportGroups
                for ($j = count($matches[2]) - 1; $j >= 0; $j--) {
                    $poolName = trim($matches[2][$j][0]);
                    $dateRaw = $matches[1][$j][0];

                    // نتحقق هل الـ pool ده موجود في supportGroups
                    foreach ($supportGroups as $group) {

                        if ($group['pool'] == $poolName) {
                            $lastMatch = [
                                'pool' => $poolName,
                                'SLA' => $group['SLA'],
                                'time' => $dateRaw,
                            ];
                            break 2; // نكسر اللوبين ونكتفي بأول تطابق صحيح
                        }
                    }
                }

                // لو لقينا تحويل صالح
                if ($lastMatch) {
                    $lastTransferTime = Carbon::parse($lastMatch['time']);


                    $newEscalation = false;

                    if($lastMatch['pool'] == $orgData[$i]->support_group&& $lastTransferTime != $orgData[$i]->transfer_time && $orgData[$i]->close_code != 'N/A' ){
                        $newEscalation = true;
                    }elseif($lastMatch['pool'] != $orgData[$i]->support_group){
                        $newEscalation = true;
                    }
                    if (
                        $newEscalation &&
                        $orgData[$i]->ticket_close_time == null &&
                        $lastTransferTime != null

                    ) {
                        if($lastTransferTime < $orgData[$i]->close_time){
                            $lastTransferTime = Carbon::parse($orgData[$i]->close_time);
                        }
                        $closeCodeTime = Carbon::now()->toDateTimeString();
                        $curuantSupportPool_duration = $lastTransferTime->diffInSeconds($closeCodeTime);

                        $escalationData = new EscalationData(
                            $lastMatch['pool'],
                            $lastMatch['SLA'],
                            $lastTransferTime,
                            null,
                            $curuantSupportPool_duration,
                            'N/A',
                            'N/A',
                            $orgData[$i]->ticketTitle,
                            $orgData[$i]->delayId,
                            $orgData[$i]->accelerationId,
                            null,
                            $orgData[$i]->compensated ,
                            $status
                        );

                        $orgData[] = $escalationData;
                        $negativeCounter --;
                        $EscalationTimes++;
                        $orignalEscalationTimes++;
                        $i = $EscalationTimes - 1;
                    }
                }
            }

            $SLSFormats = '/(Group Of\s*\([^)]*CC Second Level Support[^)]*\)\s*Ticket Info:)(?!\s*(?:Ticket|User|Customer|New Ticket|displayed Mobile|File|Sls|SLS|sls|SMS|sms|Sms|CPE|cpe|solved)\b)(.*)/m';
           if (is_string($data)) {
                if (preg_match_all($SLSFormats, $data, $allMatches, PREG_SET_ORDER)) {
                    $lastMatch = end($allMatches);
                    $SLSupdate = trim($lastMatch[2]); // الجزء اللي بعد Ticket Info:
                }
            }

        }



        if($orgData[$i]->support_group == 'SLS-IVR Automation' || $orgData[$i]->support_group == 'customer 360'){
            $AutomationFormats = '/(Group Of\s*\([^)]*SLS-IVR Automation[^)]*\)\s*Ticket Info:)(?!\s*(?:Ticket|User|Customer|New Ticket|displayed Mobile|File|Sls|SLS|sls|SMS|sms|Sms|CPE|cpe|solved)\b)(.*)/m';
           if (is_string($data)) {
                if (preg_match_all($AutomationFormats, $data, $allMatches, PREG_SET_ORDER)) {
                    $lastMatch = end($allMatches);
                    $Automationupdate = trim($lastMatch[2]); // الجزء اللي بعد Ticket Info:
                    $orgData[$i]->close_code = 88;
                    $orgData[$i]->close_code_reason = $Automationupdate;

                }
            }
        }


        $lastEscalation = $orgData[count($orgData) - 1];

        $reason = '';
        $totalDuration = 0;
        $ValidDuration = 0;
        $EscalationTimesValidation = 0;
        $outageTKT = false ;

        $start = Carbon::parse($orgData[0]->transfer_time);

        $startx = Carbon::parse($orgData[0]->transfer_time);
        $endx = Carbon::parse($orgData[$i]->close_time);
        $oldValidDuration = $startx->diffInSeconds($endx);

        if($orgData[$i]->close_code == 'N/A' && $orgData[0]->ticket_close_time != null){
            $orgData[$i]->close_time = $orgData[$i]->transfer_time;

            $closeCodeTime = Carbon::parse($orgData[$i]->close_time);
            $Code_satrt_time = Carbon::parse($orgData[$i]->transfer_time);
            $orgData[$i]->duration_seconds = $Code_satrt_time->diffInSeconds($closeCodeTime);
        }

        $end = Carbon::parse($orgData[$i]->close_time);
        $totalDuration = $start->diffInSeconds($end);
        $esclationHistory = '';
        $esclationHistory = [] ;
        $duration_from_majorFault = 0;
        $weMobilecompansationQouta = 0;
        $weMobilecompansationExpireDays = 0;
        $weMobileValidation = false ;

        //TT Not Closed
        if ($lastEscalation->transfer_time == null && $orgData[0]->ticket_close_time == null) {
            $close_time_for_open = Carbon::now()->toDateTimeString();
            $lastEscalation->duration_seconds = $lastEscalation->transfer_time->diffInSeconds($close_time_for_open);
        }
        foreach ($orgData as $item) {
            if ($item->close_code == 'N/A') {
                $x = $item->id - 2;     //previos itiem
                $w = $item->id -1;      //cureent item
                if($item->id > 1){
                    if ($orgData[$x]->close_code == 101 && $orgData[$x]->support_group == $item->support_group ) {
                        $i--;
                        $orignalEscalationTimes --;
                        $EscalationTimes --;
                        $lastEscalation = $orgData[count($orgData) - 2];
                        unset($orgData[$w]);
                    }
                }

            }
        }


        foreach ($orgData as $item) {
            //not valid escalation but has 3dr times the same close code

            $numberOfCloseCode2 = 0 ;
            if ($item->close_code == 2) {
                $numberOfCloseCode2++;
            }
            if($numberOfCloseCode2 >= 3){
                $item->valid = true;
            }
            if (in_array($item->close_code, $MajorFaultCodes)){
                $outageTKT = true ;
            }

            if($item->support_group == 'IU Maintenance' && $item->close_code != 102 && $item->close_code != 105){
                $reassignCounter++;
            }

            if ($item->close_code == 'N/A' && $EscalationTimes > 1) {
                //$EscalationTimes = $EscalationTimes - 1;

            }
            if ($item->close_code == 'N/A' || $item->close_code == 'N/A') {
                $x = $item->id - 2;     //previos itiem
                $w = $item->id -1;      //cureent item
                $y = $item->id ;        //next item

        // Transfered tt from pool to anther without Solve
               if (isset($orgData[$y])) {
                    $item->close_code_reason = 'Transfered';
                    $item->valid = true;
                    $item->close_time = $orgData[$y]->transfer_time;
                    $close_timex = Carbon::parse($item->close_time);
                    $transfer_timex = Carbon::parse($item->transfer_time);

                    $item->duration_seconds = $transfer_timex->diffInSeconds($close_timex);
                    $item->valid = $orgData[$y]->valid ;
                } elseif($item->ticket_close_time != null) {
                    $item->close_time = $item->ticket_close_time;
                }else{
                    $item->close_code_reason = '</u>in progress . . .';
                    $tktStillOpen = true;
                }
                if($item->id > 1){
                    if($orgData[$x]->close_code == 20 || in_array($orgData[$x]->close_code, $MajorFaultCodes)){
                        $item->SLA = 259200;
                        $item->ticket_close_time = $orgData[$x]->ticket_close_time;
                        $item->close_time = $orgData[$x]->close_time;
                        $item->reason = 'CST has major Fault ';
                        $reason = $item->reason ;
                    }
                }

            }

            if($outageTKT){
                $needWeMobile = true;
            }
            $Waiting_for_IT = false;
            if ($item->close_code == 101 || $item->close_code == 102 || $item->close_code == 103 ) {
                $satrt_time = $item->close_time;
                $start_time = Carbon::parse($satrt_time);
                $lastClodeCode = $item->close_code;
                if($item->close_code == 101){
                    $needWeMobile = true;
                    $Waiting_for_IT = true;
                }
                if ($item->ticket_close_time != null) {

                    $ticket_close_time = $item->ticket_close_time;
                    $ticket_close_time = Carbon::parse($ticket_close_time);

                    $satrt_time = $item->transfer_time;
                    $item->close_time = $ticket_close_time;
                    $ticket_close_time = Carbon::parse($ticket_close_time);
                    $start_time = Carbon::parse($satrt_time);

                    $duration_seconds = $start_time->diffInSeconds($ticket_close_time);

                    $item->duration_seconds = $duration_seconds;
                    $totalDuration = $duration_seconds;
                    $majorFaultSlaStatus = 'Closed';

                } else {
                    $close_time_for_calc = Carbon::parse($item->close_time);

                    $close_time = Carbon::now()->toDateTimeString();


                    $start_time = $item->transfer_time;
                    $start_time = Carbon::parse($start_time);

                    $duration_from_majorFault = $close_time_for_calc->diffInSeconds($close_time);

                    $reason_to_calc = $item->close_code_reason;
                    $item->close_code_reason = $reason_to_calc .'from ' .$close_time_for_calc;
                    $item->close_time = $close_time;

                    $duration_seconds = $start_time->diffInSeconds($close_time);


                    $item->duration_seconds = $duration_seconds;
                    $duration_from_majorFault_Delay = $duration_from_majorFault + $extraSeconds;
                    if ($item->SLA > $duration_from_majorFault_Delay) {

                        $majorFaultSlaStatus = 'Within SLA';
                        $slaStatus_color = 'green';
                        $needWeMobile = true;
                        $weMobilecompansationQouta = 3;
                        $weMobilecompansationExpireDays = 2;

                    } else {
                        $majorFaultSlaStatus = 'After SLA';
                        $slaStatus_color = 'red';


                    }
                    $totalDuration = $duration_seconds;

                }


            }



            // major Fault
            if (in_array($item->close_code, $MajorFaultCodes)) {
                $satrt_time = $orgData[0]->transfer_time;
                $start_time = Carbon::parse($satrt_time);
                $item->valid == true ;
                if ($item->ticket_close_time != null) {
                    $ticket_close_time = $item->ticket_close_time;
                    $ticket_close_time = Carbon::parse($ticket_close_time);

                    $duration_from_majorFault = $start_time->diffInSeconds($ticket_close_time);

                    $satrt_time = $item->transfer_time;
                    $item->close_time = $ticket_close_time;

                    $ticket_close_time = Carbon::parse($ticket_close_time);
                    $start_time = Carbon::parse($satrt_time);

                    $duration_seconds = $start_time->diffInSeconds($ticket_close_time);

                    $item->duration_seconds = $duration_seconds;

                    $totalDuration = $duration_seconds;
                    $majorFaultSlaStatus = 'Closed';


                } else {
                    $x = $item->id - 2;
                    $w = $item->id -1;
                    if($item->id > 1){
                        if($orgData[$x]->close_code == 20 || in_array($orgData[$x]->close_code, $MajorFaultCodes)){
                            $item->close_time = $orgData[$x]->close_time ;
                            $item->transfer_time = $orgData[$x]->transfer_time ;

                        }
                    }

                    $close_time_for_calc = Carbon::parse($item->close_time);

                    $close_time = Carbon::now()->toDateTimeString();


                    $start_time = $item->transfer_time;
                    $start_time = Carbon::parse($start_time);

                    $duration_from_majorFault = $close_time_for_calc->diffInSeconds($close_time);


                    $reason_to_calc = $item->reason;
                    $item->close_code_reason = $reason_to_calc .'from ' .$close_time_for_calc;
                    if($item->id > 1){
                        if($orgData[$x]->close_code == 20 || in_array($orgData[$x]->close_code, $MajorFaultCodes)){
                            $item->close_code_reason = $orgData[$x]->close_code_reason ;
                        }
                    }

                    $item->close_time = $close_time;

                    $duration_seconds = $start_time->diffInSeconds($close_time);


                    $item->duration_seconds = $duration_seconds;
                    $duration_from_majorFault_Delay = $duration_from_majorFault + $extraSeconds;
                    if (259200 >= $duration_from_majorFault_Delay && $item->close_code != 102) {

                        $majorFaultSlaStatus = 'Within SLA';
                        $slaStatus_color = 'green';
                        $needWeMobile = true;
                        $weMobilecompansationQouta = 3;
                        $weMobilecompansationExpireDays = 2;

                    } elseif(259200 < $duration_from_majorFault && $item->close_code != 102) {
                        $majorFaultSlaStatus = 'After SLA';
                        $slaStatus_color = 'red';

                    }
                    $totalDuration = $duration_seconds;
                }

            }
            $transfer_time_to_history = Carbon::parse($item->transfer_time)->format('d-m, h:i A');
            if ($item->close_code == 'N/A' && $orgData[$i]->id == $item->id && $ticket_close_time == null) {
                $close_time_to_history = 'Now';
            } else {
                $close_time_to_history = Carbon::parse($item->close_time)->format('d-m, h:i A');
            }
            //$esclationHistory = $esclationHistory.'<br>'.$item->id.' - '.$item->support_group.' : From '.$transfer_time_to_history.' To '.$close_time_to_history.'<br><u><span style="color: red;  padding: 0px 0px; font-weight: bold;">'.$item->close_code_reason.'</span></u>';
            $esclationHistory[] = [
                'id' => $item->id,
                'support_group' => $item->support_group,
                'from' => $transfer_time_to_history,
                'to' => $close_time_to_history,
                'reason' => $item->close_code_reason
            ];
            if ($item->valid) {
                $EscalationTimesValidation++;
            }
        }
        $end = Carbon::parse($orgData[$i]->close_time);
        $totalDuration = $start->diffInSeconds($end);
        $start = Carbon::parse($orgData[0]->transfer_time);
        $kemalaahStart = $start->copy()->startOfDay()->diffInSeconds($start);
        $end = Carbon::parse($orgData[$i]->close_time);
        $kemalaahEnd = $end->diffInSeconds($end->copy()->endOfDay());

        if($ValidDuration >= 86400){
            $ValidDuration = $ValidDuration + $kemalaahStart + $kemalaahEnd ;
            $ValidDuration = ceil($ValidDuration);


        }


        $starttocheckless24 = Carbon::parse($orgData[0]->transfer_time);
        $ii = $EscalationTimes - 1;
        $endtocheckless24 = Carbon::parse($orgData[$ii]->close_time);
        $totalDurationtocheckless24 = $starttocheckless24->diffInSeconds($endtocheckless24);

        if($totalDurationtocheckless24 >= 86400){
            $ValidDuration = $ValidDuration / 86400;
            $ValidDuration = ceil($ValidDuration);
            $ValidDuration = $ValidDuration * 86400 ;
        }
        if($oldValidDuration < 86400 && $ValidDuration == 86400){
            $ValidDuration = $oldValidDuration;
        }

        $startDate = Carbon::parse($orgData[0]->transfer_time)->format('d F Y h:i A');
        $closeDate = Carbon::parse($lastEscalation->close_time)->format('d F Y h:i A');
        $curuantSupportPool = $lastEscalation->support_group;

        $slaStatus = '';
        if(!in_array($curuantSupportPool_all, $FirstsupportPools)){
            $SLSupdate = '';
        }

        if($orgData[0]->ticket_close_time != null){
            $slaStatus = 'Closed';
        }
        $slaStatus_color = 'red';
        $actionMessage = '';
        $needDelayIR = false;
        $slaToReadding = CarbonInterval::seconds($lastEscalation->SLA)->cascade()->forHumans();

        $DelayMessage = '';






        //$Waiting_for_IT = false;
        if($lastEscalation->close_code == 101 && $lastEscalation->ticket_close_time == null){
            $Waiting_for_IT = true;
            $needWeMobile = true;

        }

         if ($curuantSupportPool_all == 'Installation - Operations'){
            $needWeMobile = true;
        }

        if ($lastEscalation->close_code == 'N/A' || $lastEscalation->close_code == null || $lastEscalation->close_code == 0 || $ticket_close_time == null || $Waiting_for_IT == true ||$curuantSupportPool_all == 'Installation - Operations') {
            if($totalDuration < 86400){
                 if ($selectproblem == 'physical instability' || $selectproblem == 'bad line quality'|| $selectproblem == 'blq'|| $selectproblem == 'instability'|| $Waiting_for_IT == true || $outageTKT == true || $curuantSupportPool_all == 'Installation - Operations') {
                        $needWeMobile = true;
                        $weMobilecompansationQouta = 3;
                        $weMobilecompansationExpireDays = 2;
                    }else{
                        $needWeMobile = false;
                    }
            }elseif($totalDuration >= 86400){
                if(in_array($PackageId, $agentWemobile)){
                    $weMobilecompansationQouta = 5;
                    $weMobilecompansationExpireDays = 3;
                    $needWeMobile = true;
                }elseif (in_array($PackageId, $TlWemobile)) {
                    $weMobilecompansationQouta = 10;
                    $weMobilecompansationExpireDays = 7;

                } elseif (in_array($PackageId, $CLMWemobile)) {
                    $weMobilecompansationQouta = 20;
                    $weMobilecompansationExpireDays = 15;

                }else{
                    $weMobilecompansationQouta = 404;
                }

            }
            if($curuantSupportPool_all == 'CC Second Level Support' && $SLS_owner == false && $lastEscalation->close_code != 'N/A'){
                $curuantSupportPool_start = Carbon::parse($lastEscalation->transfer_time);
                $curuantSupportPool_end = Carbon::now()->toDateTimeString();
                $lastEscalation->duration_seconds = $curuantSupportPool_start->diffInSeconds($curuantSupportPool_end);
                $lastEscalation->SLA = 7200;
            }
            $duration_for_delay = $lastEscalation->duration_seconds + $extraSeconds;

            if ($lastEscalation->id == 1 || $EscalationTimes == 1) {
                if ($lastEscalation->SLA >  $duration_for_delay &&  $duration_for_delay >= 0) {
                    $slaStatus = 'Within SLA';
                    $slaStatus_color = 'green';

                } else {
                    $slaStatus = 'After SLA';
                    $needDelayIR = true;
                }
            } elseif ($EscalationTimes > 1) {
                if ($lastEscalation->SLA >  $duration_for_delay &&  $duration_for_delay > 1) {
                    $slaStatus = 'Within SLA';
                    $slaStatus_color = 'green';
                    $needWeMobile = true;


                } else {
                    $slaStatus = 'After SLA';
                    $needDelayIR = true;
                }
            }
            if ($curuantSupportPool_all == 'Installation - Operations'){
            $slaStatus = 'According Below';
            $sla = 'kindly check wiki';
            }
            if(in_array($lastEscalation->ticketTitle, $physicalCases) || in_array($lastEscalation->ticketTitle, $logicalCases)){
                if (in_array($lastEscalation->ticketTitle, $physicalCases) && $curuantSupportPool_all == 'Installation - Operations') {
                    $slaStatus = 'According Below';
                    $sla = 'Cairo and Alexandria : 2 Working days <br> - For other : 5 Working days ';
                } elseif ($curuantSupportPool_all == 'Installation - Operations') {
                    $slaStatus = 'According Below';
                    $sla = '1 WD (from 8:00 AM till 8:00 PM)';
                }

            }

            if ($needDelayIR == true) {
                $needWeMobile = true;
            }





            if($IslogicalCases == false){
                $actionMessage = $actionMessage.$lastEscalation->close_code_reason;

            }else{
                $needWeMobile = false;
                $actionMessage = $SLSupdate;
                 if (stripos($SLSupdate, 'no issue') !== false) {
                    $actionMessage = 'No Issue Detected from our Network Side , Inform CST to Check with another CPE .. <a href="https://sp-wiki.te.eg:5443/Pages/Technical_DSL/last%20edit%20Revamp/logical/NOC%20Updates%20for%20all.aspx" target="_blank" style="color:#b28df7; text-decoration:underline;">WIKI</a>';
                }
                if($SLSupdate == ''){
                    $actionMessage = $actionMessage.$lastEscalation->close_code_reason;
                }
            }

            if ($needDelayIR == false) {
                $DelayMessage = '';
            }


            if ($needWeMobile == true) {
                //$weMobileMessage = "<br>offer we mobile compensation <strong style='color: red;'>if not added before</strong><br>we mobile wil be : <strong style='color: green;'>".$weMobilecompansationQouta.' GB for '.$weMobilecompansationExpireDays.' Days</strong>';
                $weMobileMessage = "offer we mobile compensation <strong style='color: red;'>if not added before</strong><br>we mobile wil be : <strong style='color: green;'>";
                $weMobileValidation = true ;


            }
            if ($weMobilecompansationQouta == 0) {
                //$weMobileMessage = "<strong style='color: red;'>not eligible for we mobile compensation</strong>";
                $weMobileMessage = "<strong style='color: red;'>not eligible for we mobile compensation</strong>";
                $weMobilecompansationQouta = 0;
                $weMobilecompansationExpireDays = 0;
                $weMobileValidation = false ;

            }elseif($weMobilecompansationQouta == 404){
                $weMobileMessage = "<strong style='color: red;'>this package not supported yest <br> Please Handle it Manual</strong>";
                $weMobilecompansationQouta = null;
                $weMobilecompansationExpireDays = null;
                $weMobileValidation = false ;

            }
            if ($lastEscalation->support_group == 'Pilot-Follow up' || $lastEscalation->support_group == 'Pilot - Follow up') {
                $actionMessage = $actionMessage.'<br>Don’t with draw ticket and Wait for the SLA <br>(حد هيكلمك من الاقسام المختصة لمتابعة حل المشكلة)';
            }
        }

        if ($lastEscalation->close_code == 20 || $lastEscalation->close_code == 101|| $lastEscalation->close_code == 102|| $lastEscalation->close_code == 103 ||  in_array($lastEscalation->close_code, $MajorFaultCodes) ||$Waiting_for_IT) {
            $slaStatus = $majorFaultSlaStatus;
        }

        $sla = "No estimated time";

        if ($lastEscalation->SLA == 7200){
            $sla = "2H";
        }elseif ($curuantSupportPool_all == 'Installation - Operations'){

            $ticketTitle = strtolower(trim($lastEscalation->ticketTitle));

            $patern = '/option pack/';

            if (preg_match($patern, $ticketTitle, $matches)) {
                $ticketTitle = $matches[0];
            }
            $sla = 'kindly check wiki';


            if (in_array($lastEscalation->ticketTitle, $physicalCases ) || in_array($lastEscalation->ticketTitle, $logicalCases)){
                $sla = "Cairo&Alex: 2WD .. Other gov OR Cabins in all gov 5WD";
            }
            if ($ticketTitle == 'option pack') {
                $sla = "1 WD (from 8:00 AM to 8:00 PM)";
            }
        } elseif ($lastEscalation->SLA == 259200){
            //$slaStatus = 'According Below';
            $sla = "3 Calendar days";
        } elseif ($lastEscalation->SLA == 3600 && $curuantSupportPool_all == 'Pilot-SLS'){
            $sla = "1 Hour";
        }elseif ($lastEscalation->SLA == 86400){
            $sla = "24H";
        }elseif ($lastEscalation->SLA == 432000){
            $sla = "Cairo&Alex: 1D .. Other 5WD";
        }elseif ($lastEscalation->SLA == 3600 && $curuantSupportPool_all != 'Pilot-SLS'){
            $sla = "Handle customer technical problem normally (Logical or physical) according to customer input and automation ticket update.";
            //$sla = "1 Hr";
        }elseif ($lastEscalation->SLA == 18000){
            $sla = "from 2H to 5H";
        }
        if ($curuantSupportPool_all == 'IU Maintenance'){
            $sla ="24H unless if line belong to <u><a href='https://10.19.44.2/ireport/api/haya_karima.html' target='_blank'>Haya Karima</a></u>";
        }
        if ($lastEscalation->reason == 'CST has major Fault '){
            $sla ="72H unless if line belong to <u><a href='https://10.19.44.2/ireport/api/haya_karima.html' target='_blank'>Haya Karima</a></u> 5 Days";
            $wiki = "https://sp-wiki.te.eg:5443/Pages/Technical_DSL/last%20edit%20Revamp/Physical/IU%20Updates.aspx#Major_Fault";
            $slaStatus_color = 'green';
        }
        if ($lastEscalation->close_code == 101){
            $sla = "72H";
            $slaStatus_color = 'green';
        }
        elseif ($lastEscalation->close_code == 102){
            $sla = "24H";
        }
        elseif ($lastEscalation->close_code == 103){
            $sla = "24H";
            $actionMessage = "";
        }
        elseif ($lastEscalation->close_code == 74){
            $sla = "24H";
            $actionMessage = 'Cairo&Alex : CCA will inform the customer that he should check the CPE on CSO "Mandatory".
            <br> other : Customer can check with another CPE normally. and for more Details, Check WIKI Reference - <a href="https://sp-wiki.te.eg:5443/Pages/Technical_DSL/last%20edit%20Revamp/Physical/IU%20Updates.aspx#1st_Level_Team" target="_blank" style="color:#b28df7; text-decoration:underline;">IU Update</a>';
        }
        elseif($lastEscalation->close_code == 26){
            $ValidDuration = 0;
            $reason = $lastEscalation->reason;
            $actionMessage = 'Direct cst to CSO (قسم الحسابات)';
        }
        if (in_array($curuantSupportPool, $MCU_Field_Support_pools) && $tktvisit == true) {
            $slaStatus = 'According Below';
            $slaToReadding = 'Cairo and Alexandria: 1 day.<br>Rest of Egypt : 5 Working days.';
            $sla = "Cairo&Alex: 1D .. Other 5WD";
            $weMobileMessage = "<strong style='color: red;'>not eligible for we mobile compensation</strong>";
            $weMobilecompansationQouta = 0;
            $weMobilecompansationExpireDays = 0;
            $weMobileValidation = false ;

        }
         if($curuantSupportPool_all == 'Openetsec [Operation Network Security]' || $curuantSupportPool_all == 'OPNETSEC') {
                $slaStatus = 'No estimated time';
                $sla = 'No estimated time';
        }
        if ($curuantSupportPool == 'CC Second Level Support'&& $tktvisit == true) {
            $slaStatus = 'According Below';
            $slaToReadding = 'Cairo and Alexandria: 1 day.<br>Rest of Egypt : 5 Working days.';
            $sla = "Cairo&Alex: 1D .. Other 5WD";
            $weMobileMessage = "<strong style='color: red;'>not eligible for we mobile compensation</strong>";
            $weMobilecompansationQouta = 0;
            $weMobilecompansationExpireDays = 0;
            $weMobileValidation = false ;

        }





        $startw = Carbon::parse($orgData[0]->transfer_time);
        $endw = Carbon::parse($orgData[$i]->ticket_close_time);
        $totalDuration = $startw->diffInSeconds($endw);


        if($orgData[0]->ticket_close_time) {
            $slaStatus = 'Closed';
            if($reason == ''){
                $reason = $orgData[$i]->reason;
            }
        }


        if($curuantSupportPool_all != 'CC-Follow up'
            &&$curuantSupportPool_all != 'MCU Field Support'
            &&!in_array($curuantSupportPool, $MCU_Field_Support_pools)
            &&$curuantSupportPool_all != 'customer 360'
            &&$curuantSupportPool_all != 'SLS-IVR Automation'
            && $outageTKT == false && $slaStatus != 'According Below'
            && $slaStatus == 'After SLA'){
            if ($delayId == null || $delayId == 'N/A') {
            $DelayMessage = '<br>Make IR Delay  ';
            $DelayMessage .= '<a href="https://10.19.44.2/ireport/cases/del_tickets_add.php" target="_blank" style="color: #2980b9; text-decoration: underline; font-weight: bold;">[Create IR]</a>';
            $DelayMessage .= '<br> And renewal SLA ';
            } else {
                $DelayMessage = '<br> open IR Delay and Check if Created with Wrong Data<br>And renewal SLA ';
            }
        }
        if($slaStatus == 'According Below'){
            $DelayMessage .= '<br>IF within SLA : stick to SLA<br>
                        IF After SLA : Make IR Delay and Act according to WIKI';
            $DelayMessage .= ' <a href="https://10.19.44.2/ireport/cases/del_tickets_add.php" target="_blank" style="color: #2980b9; text-decoration: underline; font-weight: bold;">[Create IR]</a><br>';
        }

        if($orgData[0]->compensated == 1){
            $reason .= ' <span style="color: black; background-color: yellow; font-size: 1.2em; padding: 2px 6px; border-radius: 4px;">
                Compensated before </span> <span class="blinking" style="margin-right: 6px; font-size: 2.5em;">⚠️
            </span>';
        }
        $optimizationPeriod = false ;
        if($selectproblem == 'need optimization'){
            $close_code_reason = $orgData[$i]->close_code_reason ;
            $optimizationPeriod = str_contains($close_code_reason, 'keep cpe');
            if($optimizationPeriod == false){
                $optimizationPeriod = str_contains($close_code_reason, 'let cpe');
            }
            if($optimizationPeriod){
                $sla = '3 Dayes';
                $actionMessage = 'inform cst keep cpe ON for 3 days to reach max Speed ';
                $slaStatus = '';
                $start_ptimizationPeriod = Carbon::parse($orgData[$i]->close_time);
                $end_ptimizationPeriod = Carbon::now()->toDateTimeString();
                $optimizationDuration = $start_ptimizationPeriod->diffInSeconds($end_ptimizationPeriod);
                if($optimizationDuration < 259200){
                    $slaStatus = 'Within SLA';
                    $slaStatus_color = 'green';

                }else{
                    $slaStatus = 'After SLA';
                    $slaStatus_color = 'red';
                }
            }
        }
        if($slaStatus == 'After SLA after Major Fault' || $slaStatus == 'After SLA'){
            $slaStatus_color = 'red';
        }
        if($orgData[$i]->close_code == 102){
            if($slaStatus == 'After SLA'){
                $actionMessage .= '<br>Check ADF Ticket <br>
                <ul>
                <li>&nbsp;&nbsp;&nbsp;•&nbsp; if Open : renew SLA. </li>
                <li>&nbsp;&nbsp;&nbsp;•&nbsp; if Closed : re-troubleshoot with the customer</li>
                </ul>
                <mark>Create : Outside TEData SR if Not Created Before .</mark>';
            }
        }elseif($orgData[$i]->close_code == 81 || $orgData[$i]->close_code == 82){
             $actionMessage .= '<br>if Problem still exist: <br>
              Re-Escalate the Ticket to IU Pool with normal IU SLA as long as there is no major fault exist after following normal troubleshooting with skipping connect from main step and consider line connected from main <br> Reference -
              <a href="https://sp-wiki.te.eg:5443/Pages/Technical_DSL/last%20edit%20Revamp/Physical/IU%20Updates.aspx#Close_Code_81__82" target="_blank" style="color:#b28df7; text-decoration:underline;">WIKI</a>';
        }elseif($orgData[$i]->close_code == 24){
             $actionMessage .= '<br>If Voice Working Fine: Troubleshoot the case normally
                <br>If Voice Not Working: Check if customer has bills or not
                <br>    If yes inform him to check his bills
                <br>Confirm with the customer that he did not request to cancel the voice service
                <br>    If voice is still not working
                <br>Inform customer with pending fixing TE message
                <br>
                <br>SLA: Case escalated to the responsible team and we are waiting for their update with 3 days SLA.
                <br>Way of communication : Customer should follow on his case with us';
        }elseif($orgData[$i]->close_code == 19){
             $actionMessage .= '<br>For any Returned ticket with Status "Internal Wiring Vendor Visit" or TTS Closed code ”Internal network closed compound” on CC Follow Pool – including update that customer still effected with internal wiring problem OR “Internal System”
            <br>CCA Action: Inform the customer that there is an internal wiring problem and direct him to follow with a technician specialist.';
        }elseif($orgData[$i]->close_code == 27){
            $actionMessage .= '<br>Inform customer to check with his exchange (قسم الحسابات) with required documents (working hours from 8 AM to 4 PM, Friday and Saturday off ) and check WIKI Reference -
            <a href="https://sp-wiki.te.eg:5443/Pages/Technical_DSL/last%20edit%20Revamp/Physical/IU%20Updates.aspx#Address_Modification" target="_blank" style="color:#b28df7; text-decoration:underline;">IU Update</a>';
        }elseif($orgData[$i]->close_code == 105){
            $actionMessage .= '<br>Follow Normal SLA Rules according the case and in case of delay follow Normal Process
            In case ticket returned with update " Duplicated ", CCA will create the below SR in addition to the main required SR/TT according to the customers case.
            Condition : Only one SR must be created for TTS ticke';
        }elseif($orgData[$i]->close_code == 67){
            $actionMessage .= '<br>Re-Escalate the Ticket to IU Pool with normal IU SLA as long as there is no outage exist after following normal troubleshooting.<br> and check WIKI Reference -
            <a href="https://sp-wiki.te.eg:5443/Pages/Technical_DSL/last%20edit%20Revamp/Physical/IU%20Updates.aspx#Address_Modification" target="_blank" style="color:#b28df7; text-decoration:underline;">IU Update</a>';

        }




        if($SLSupdate != ''){
            //$esclationHistory = $esclationHistory.'<br><br> **  SLS Update <br><u><span style="color: red;  padding: 0px 0px; font-weight: bold;">'.$SLSupdate.'</span></u>';
            $esclationHistory[] = [
                'id' => 0,
                'support_group' => "SLS",
                'from' => null,
                'to' => null,
                'reason' => $SLSupdate
            ];
        }

        if($curuantSupportPool_all == 'Second Level Advanced'){
            if($status == 'visit schedule'){
                $slaStatus = 'According Below';
                $sla = null;
                $DelayMessage = '';
                $actionMessage = 'inform customers to wait for feedback from the responsible team.';
            }elseif( $selectproblem == 'unsupported services' && $status == 'waiting for research'){
            }else{
                if($actionMessage == '</u>in progress . . .'){
                    $actionMessage = '';
                }
                $slaStatus = 'According Below';
                $sla = null;
                $actionMessage = '<strong  style="color: yellow;">Handle according 3rd Level update If Exist :</strong><br>'.$actionMessage ;
                $DelayMessage = '';
            }
        }


        $transferToCount = count($orgData) + $negativeCounter ;



        // Re-Assign
        $reassign = false;

        if($reassignCounter >= 2 && $reassignId == null){

            if($curuantSupportPool_all != 'IU Maintenance'){
                $reassign = true;

            }elseif($reassignCounter >= 3 && $curuantSupportPool_all == 'IU Maintenance'){
                $reassign = true;

            }
        }

        if($orgData[0]->ticket_close_time != null){
            $slaStatus = 'Closed';
            $reassign = false;
            $actionMessage = '';
            //$weMobileMessage = "<strong style='color: red;'>not eligible for we mobile compensation</strong>";
            $weMobileMessage = "not eligible for we mobile compensation";
            $weMobilecompansationQouta = 0 ;
            $weMobileValidation = false ;
            $weMobilecompansationExpireDays = 0;

        }

        return [
            'totalDuration' => $totalDuration,
            'startDate' => $startDate,
            'closeDate' => $closeDate,
            'curuantSupportPool' => $curuantSupportPool,
            'esclationHistory' => $esclationHistory,
            'slaStatus' => $slaStatus,
            'sla' => $sla,
            'slaStatus_color' => $slaStatus_color,
            'actionMessage' => $actionMessage,
            'delayId' => $delayId,
            'reworkId' => $reworkId,
            'reassignId' => $reassignId,
            'accelerationId' => $accelerationId,
            'ticket_close_time' => $ticket_close_time,
            'weMobileMessage' => $weMobileMessage ,
            'DelayMessage' => $DelayMessage ,
            'lastClodeCode' => $lastClodeCode ,
            'tktStillOpen' => $tktStillOpen ,
            'Waiting_for_IT' => $Waiting_for_IT ,
            'outageTKT' => $outageTKT ,
            'transferToCount' => $transferToCount ,
            'optimizationPeriod' => $optimizationPeriod ,
            'SLSupdate' => $SLSupdate ,
            'tktvisit' => $tktvisit ,
            'reassign' => $reassign ,
            'wiki' => $wiki ,
            'weMobilecompansationQouta' => $weMobilecompansationQouta ,
            'weMobilecompansationExpireDays' => $weMobilecompansationExpireDays ,
        ];
    }
}
