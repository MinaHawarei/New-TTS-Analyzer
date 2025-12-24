<?php

namespace App\Models\TTS;

use Carbon\Carbon;
use App\Services\ConcessionApiService;

class CompensationValidation
{
    public static function validate($data,$tkt_id,$service_number,$orgData, $EscalationTimes, $usage, $selectproblem, $selectFollowUp, $ineligibleDays , $voiceInstability , $curuantSupportPool_all , $voicetype)
    {

        $orignalEscalationTimes = $EscalationTimes;
        $needToCheekFollowUP = false ;
        $i = $EscalationTimes - 1;
        $checkDurationUsage = [];
        $Waiting_for_IT = false;
        $EngInspection = false ;
        $cst_has_tkt_before = false ;
        $cst_compansated_before = false ;
        $tkt_rejected_reason = '' ;
        $compansated_status = '';
        $compansated_added_on = null;
        $api_section = '' ;
        //API CALL

        $api = new ConcessionApiService();
        $apiResponse = $api->send($service_number , $tkt_id);
        if(!empty($apiResponse)){
            $apiResponse = array_filter($apiResponse, function ($item) {
                return $item['section'] !== 'phone';
            });

            foreach($apiResponse as $resp){
                $api_section = $resp['section'];
                $cst_has_tkt_before = true ;
                $compansated_status = $resp['status'];
                $compansated_added_on = $resp['added_on'] ;
                if($compansated_status == 'approved'){
                    $cst_compansated_before = true ;

                    break;
                }else{
                    $tkt_rejected_reason = $resp['rejected_reason'];
                }


            }
        }


        $ThirdSupportGroups = [
            'IU Maintenance',
            'Maintenance Visits',
            'NOC',
            'Data Center Unit - DCU',
            'FO-Fiber',
            'Fiber(Regions)',
            'Installation - Operations',
            'FTTH-Support',
            'Pilot-SLS',
            'Pending Fixing TE - IU',
            'Openetsec [Operation Network Security]'
            ];

        $lastClodeCode = 0 ;
        $DSLno = '';

        $newOrgData = $orgData;
        if (empty($orgData)) {
            return ['validation' => false, 'reason' => 'No data available'];
        }

        $codesNoNeedChckUsage = [9, 12, 13, 11, 14, 18, 59, 82, 19, 4, 7, 24, 99, 35, 6,101,102,11,14];
        $MajorFaultCodes = [20,81,82,102];
        $needToCheekUsage = true ;
        $needToUsage = false;
        $ticket_close_time = '';
        $ticket_close_time = $orgData[0]->ticket_close_time;
        $tktStillOpen = false;
        $optimizationPeriod = false ;
        $supportGroups = [
            'Transfered: CC Xceed Technical',
            'Transfered: CC Xeed Basic',
            'Transfered: CC Service Activation',
            'Transfered: SLS-Archiving',
            'Transfered: CC Online Support',
            'Transfered: Digital Data Chat',
            'Transfered: Business and Special Support',
            'Transfered: ICare',
            'Transfered: CSI Team',
            'Transfered: CSI',
            'Transfered: MCU Field Support',
            'Transfered: Mansoura MCU Field Support',
            'Transfered: Alex MCU Field Support',
            'Transfered: CC-Follow up',
            'Transfered: customer 360',
            'Transfered: SLS-IVR Automation',
            'Transfered: MCU Call Center',
            'Transfered: Installation - Operations',
            'Transfered: IU Maintenance',
            'Transfered: NOC',
            'Transfered: CC Second Level Support',
            'Transfered: Maintenance Visits',
            'Transfered: Data Center Unit - DCU',
            'Transfered: FO-Fiber',
            'Transfered: Fiber(Regions)',
            'Transfered: Installation - Operations',
            'Transfered: FTTH-Support',
            'Transfered: Pilot-SLS',
            'Transfered: Pilot - Follow up',
            'Transfered: Pilot-Follow up',
            'Transfered: Pending Fixing TE - IU',
            'Transfered: Second Level Advanced',
            'Transfered: MCU Field Support',
            'Transfered: customer 360',
            'Transfered: Business Technical Support',
            'Transfered: CC-Service Activation',
            'Transfered: CC-Online Support',
            'Transfered: CC-Xceed Technical',
            'Transfered: CC-VIP',
            'Transfered: I Care',
            'Transfered: SLS-FTTH',
            'Transfered: Openetsec [Operation Network Security]',
            'Transfered: OPNETSEC',
        ];
        if ($selectproblem == 'Installation' || $selectproblem == 'technical visits') {
            $supportGroups[] = ['Transfered: MCU Field Support'];
        }
        $SLS_owner = false;
        if($selectproblem == 'logical instability - no multiple logs' || $selectproblem == 'browsing - certain sites'){
            $supportGroups[] = ['CC Second Level Support'];
            $SLS_owner = true;

        }

        $InstabilityCases = ['Physical Instability', 'Bad Line Quality', 'BLQ', 'Logical Instability', 'Slowness', 'Speed', 'Need Optimization', 'logical instability - no multiple logs','High Attenuation','high attenuation',
                            'physical instability', 'bad line quality', 'blq', 'logical instability', 'slowness', 'speed', 'need optimization' , 'voice down (Data Instability impacted)'];

        $usageLimit = 1;
        $IsCaseTypeDown = true;
        $negativeCounter = 0;
        if (in_array($selectproblem, $InstabilityCases) ) {
            $usageLimit = 5;
            $IsCaseTypeDown = false;
            $needToUsage = true;
            $needToCheekUsage = true ;
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



        if ($ineligibleDays == null) {
            $ineligibleDays = 0;
        }
        $lastEscalation = $orgData[count($orgData) - 1];

        $validation = true;
        $reason = '';
        $totalDuration = 0;
        $ValidDuration = 0;
        $startFrom = -1;
        $filteredUsage = [];
        $EscalationTimesValidation = 0;

        $outageTKT = false ;
        $outageTKT_onADF = false;
        $usageMessage = [];
        $exceededQouta = false;
        $majorfultended = 0 ;

        if ($selectFollowUp != null) {
            $selectFollowUp = true;
        }
        if ($voiceInstability != null) {
            $voiceInstability = true;
        }


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

        $delayId = null;
        $accelerationId = null;


        if($totalDuration <= 86400){
            $needToUsage = false;
            $needToCheekUsage = false ;
        }

        //TT Not Closed
        if ($lastEscalation->transfer_time == null && $orgData[0]->ticket_close_time == null) {
            $close_time_for_open = Carbon::now()->toDateTimeString();
            $lastEscalation->duration_seconds = $lastEscalation->transfer_time->diffInSeconds($close_time_for_open);
        }

        if($orgData[0]->ticket_close_time == null ) {
            $tktStillOpen = true;
        }
        $newId = 1 ;
        foreach ($orgData as $item) {
            $item->id = $newId;
            if( $item->close_code == 26){
                $EngInspection = true ;
                break;
            }

            if(($item->support_group == 'Second Level Advanced' && $selectproblem != 'need optimization')|| $item->close_code == 38){
                $i--;
                $orignalEscalationTimes --;
                $EscalationTimes --;
                $item->valid = false;
                $key = array_search($item, $orgData, true);
                if ($key !== false) {
                    unset($orgData[$key]);
                    $orgData = array_values($orgData);
                    $newId -- ;
                    $item->close_code = 404;
                }
            }

            if($item->close_code == 99 ){
                $item_satrt_time = Carbon::parse($item->transfer_time);
                $item_close_time = Carbon::parse($item->close_time);

                $item_duration_seconds = $item_satrt_time->diffInSeconds($item_close_time);
                if($item_duration_seconds < 43200){
                    $item->valid = false;
                }

            }

            //not valid escalation but has 3dr times the same close code
            if($selectproblem == 'need optimization'){
                $item->valid = false;
            }
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


            if ($item->close_code == 67 && $EscalationTimes == $item->id) {
                $outageTKT_onADF = true;
            }

            if ($item->close_code == 'N/A' || $item->close_code == 'N/A') {
                $x = $item->id - 2;     //previos itiem
                $w = $item->id -1;      //cureent item
                $n = $item->id ;      //next item


                if($item->id > 1){
                    if ($orgData[$x]->close_code == 101 && $orgData[$x]->support_group == $item->support_group ) {
                        $i--;
                        $orignalEscalationTimes --;
                        $EscalationTimes --;
                        //$lastEscalation = $orgData[count($orgData) - 2];
                        unset($orgData[$w]);
                        $orgData = array_values($orgData);
                        $newId -- ;
                        $item->close_code = 404;


                    }elseif($orgData[$x]->close_code == 102 && $orgData[$x]->support_group == $item->support_group ){
                        $i--;
                        $orignalEscalationTimes --;
                        $EscalationTimes --;
                        unset($orgData[$w]);
                        $orgData = array_values($orgData);
                        $newId -- ;
                        $item->close_code = 404;

                    }elseif($item->support_group == 'IU Maintenance' || $item->support_group == 'Installation - Operations'){

                        if (isset($orgData[$n])) {
                            $item->close_time = $orgData[$n]->transfer_time ;

                        }else{
                            $lastdate = Carbon::parse($item->close_time);
                            $startFrom_without_closecode = Carbon::parse($item->transfer_time);
                            $formattedDate_without_closecode = date('d-m-Y, h:i A', strtotime($startFrom_without_closecode));


                            $pos = strrpos($data, $formattedDate_without_closecode);

                            if ($pos !== false) {
                                $start_without_closecode = $pos;

                                $afterDate = substr($data, $start_without_closecode);
                                $matchedGroup = null;
                                $matchPos = null;
                                foreach ($supportGroups as $group) {

                                    $found = strpos($afterDate, $group);
                                    if ($found !== false) {

                                        $matchedGroup = $group;
                                        $matchPos = $found;
                                        break;
                                    }
                                }

                                if ($matchedGroup !== null) {

                                    $beforeGroup = substr($afterDate, 0, $matchPos);
                                    preg_match_all('/\d{2}-\d{2}-\d{4}, \d{2}:\d{2} [AP]M/', $beforeGroup, $matches);

                                    $datesFound = $matches[0];
                                    $lastDateBeforeMatch = end($datesFound) ;

                                    $lastDateBeforeMatch = Carbon::createFromFormat('d-m-Y, h:i A', $lastDateBeforeMatch)->format('Y-m-d H:i:s');
                                    $item->close_time = $lastDateBeforeMatch;


                                }else{
                                   if($item->ticket_close_time != null){
                                        $item->close_time = $item->ticket_close_time;
                                    }else{
                                        $item->close_time = Carbon::now()->toDateTimeString();
                                        $tktStillOpen = true;
                                    }
                                }



                            }else{
                                if($item->ticket_close_time != null){
                                    $item->close_time = $item->ticket_close_time;
                                }else{
                                    $item->close_time = Carbon::now()->toDateTimeString();
                                    $tktStillOpen = true;
                                }

                            }

                        }


                        $lastdate = Carbon::parse($item->close_time);
                        $startFrom = Carbon::parse($item->transfer_time);
                        $item->duration_seconds = $startFrom->diffInSeconds($lastdate);
                        $item->valid = false ;


                        $item->duration_seconds = $startFrom->diffInSeconds($lastdate);

                        $startOffset = $startFrom->offsetHours;
                        $endOffset   = $lastdate->offsetHours;

                        // احسب الفرق بالساعات وحوله لثواني
                        $offsetDiffSeconds = ($endOffset - $startOffset) * 3600;

                        // عدل النتيجة بناءً على التغير في التوقيت الصيفي/الشتوي
                        $item->duration_seconds += $offsetDiffSeconds;

                        // تأكد أنها لا تصبح سالبة
                        if ($item->duration_seconds < 0) {
                            $item->duration_seconds = 0;
                        }



                        if ($item->duration_seconds > $item->SLA) {

                            $item->valid = true ;
                            $checkDurationUsage[] = ['end' => $startFrom , 'start' => $lastdate];

                        }
                    }
                    if($orgData[$x]->close_code == 20 || in_array($orgData[$x]->close_code, $MajorFaultCodes)){
                        $item->SLA = 259200;
                        $item->ticket_close_time = $orgData[$x]->ticket_close_time;
                        $item->close_time = $orgData[$x]->close_time;
                        $item->reason = 'CST has major Fault ';
                        $reason = $item->reason ;
                    }
                }

            }

            if($item->valid == false){
                $needToCheekUsage = true ;
                $needToUsage = true ;
            }

            if($outageTKT){
                $IsCaseTypeDown = false;
                $needToUsage = true;
                $needToCheekUsage = true ;
            }



            if ($item->close_code == 101 || $item->close_code == 102 || $item->close_code == 103 ) {
                $satrt_time = $item->close_time;
                $start_time = Carbon::parse($satrt_time);
                $lastClodeCode = $item->close_code;
                $needToUsage = true;
                if($item->close_code == 101){
                    $Waiting_for_IT = true;
                }
                $needToCheekUsage = true ;
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


                } else {
                    $close_time_for_calc = Carbon::parse($item->close_time);

                    $close_time = Carbon::now()->toDateTimeString();


                    $start_time = $item->transfer_time;
                    $start_time = Carbon::parse($start_time);


                    $reason_to_calc = $item->close_code_reason;
                    $item->close_code_reason = $reason_to_calc .'from ' .$close_time_for_calc;
                    $item->close_time = $close_time;

                    $duration_seconds = $start_time->diffInSeconds($close_time);


                    $item->duration_seconds = $duration_seconds;

                    $totalDuration = $duration_seconds;


                }


            }



            // major Fault
            if (in_array($item->close_code, $MajorFaultCodes)) {
                $satrt_time = $orgData[0]->transfer_time;
                $start_time = Carbon::parse($satrt_time);
                $needToUsage = true;
                $needToCheekUsage = true ;
                $item->valid == true ;
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

                    $totalDuration = $duration_seconds;
                }

            }


            if ($item->delayId != null || $item->delayId != 'N/A') {
                $delayId = $item->delayId;
            }

            if ($item->accelerationId != null) {
                $accelerationId = $item->accelerationId;
            }

            if($item->close_time != null && $item->close_code == 'N/A'){
                $item->valid = false ;
                $iteam_start_time = Carbon::parse($item->transfer_time);
                $iteam_close_time = Carbon::parse($item->close_time) ;
                $iteam_duration = $iteam_start_time->diffInSeconds($iteam_close_time);
                if($iteam_duration >= 86400 &&!($item->support_group == 'SLS-IVR Automation' || $item->support_group == 'customer 360')){
                    $item->valid = true ;
                }

            }



            if ($item->valid || $item->close_code_reason == 'Transfered' || in_array($item->close_code, $MajorFaultCodes)) {
                if($item->close_code != 404){
                    $EscalationTimesValidation = $item->id;
                }
            }


            $newId ++ ;

        }
        $lastEscalation = $orgData[count($orgData) - 1];

        if($selectproblem == 'need optimization'){
            $optimizationPeriod = str_contains($data, 'keep cpe');
            if($optimizationPeriod == false){
                $optimizationPeriod = str_contains($data, 'let cpe');
            }
            if($optimizationPeriod){
                $now = Carbon::now();
                $optimizationPeriod_start = Carbon::parse($orgData[0]->transfer_time);

                $optimizationPeriod_duration = $optimizationPeriod_start->diffInSeconds($now);
                $orgData[0]->valid = true;
                $EscalationTimesValidation = 1;
                if($optimizationPeriod_duration < 259200){
                    $orgData[$i]->close_time = $now;
                }else{
                    $orgData[$i]->close_time = $optimizationPeriod_start->copy()->addDays(3);
                    $orgData[$i]->duration_seconds = 259200;
                    $totalDuration = 259200 ;

                }
                foreach($orgData as $item){
                    $item->valid = true;
                }
                $needToCheekUsage = true ;
                $needToUsage = true ;
            }
        }
        ;

        $end = Carbon::parse($orgData[$i]->close_time);
        $totalDuration = $start->diffInSeconds($end);

        $fromDate = Carbon::parse($orgData[0]->transfer_time)->startOfDay();
        $toDate   = Carbon::parse($orgData[$i]->close_time)->endOfDay();
        $filledUsage = [];
        $lastKnown = null;


        for ($date = $fromDate->copy(); $date->lte($toDate); $date->addDay()) {
            $dateKey = $date->toDateString();


            if (isset($usage[$dateKey])) {

                $filledUsage[$dateKey] = $usage[$dateKey];
                $lastKnown = $usage[$dateKey];
            } else {
                // اليوم ناقص → نضيف سجل جديد بنفس بيانات آخر يوم
                if ($lastKnown) {
                    $filledUsage[$dateKey] = [
                        'date' => $dateKey,
                        'total_usage' => 0,
                        'free_unit_before' => $lastKnown['free_unit_before'],
                        'free_unit_after' => $lastKnown['free_unit_after'],
                        'packageName' => $lastKnown['packageName'],
                        'exceeded_qouta' => $lastKnown['exceeded_qouta'],
                        'bill_cycle' => $lastKnown['bill_cycle'],
                        'dsl_number' => $lastKnown['dsl_number'],
                    ];
                }
            }
        }
        $filledUsage = array_reverse($filledUsage);



        if(count($filledUsage) > count($usage)){
            $usage = $filledUsage;
        }

        $newEscalationTimes = $EscalationTimes;
        if($EscalationTimesValidation == 1){
            $newEscalationTimes = 1;
            if($i > 0){
                $i--;
            }

        }


        if ($newEscalationTimes < 2 && $EscalationTimesValidation > 0) {

            if ($orgData[0]->valid === true) {
                $validation = true;
                $reason = $orgData[$i]->reason ?? "";
                $startw = Carbon::parse($orgData[0]->transfer_time);
                $endw = Carbon::parse($orgData[$i]->close_time);
                //$ValidDuration = $startw->diffInSeconds($endw);
                $ValidDuration = ($startw->copy()->startOfDay()->diffInDays($endw->copy()->startOfDay()) + 1)*86400;
                $oldValidDuration = $startw->diffInSeconds($endw);
                if($oldValidDuration <= 86400 && $ValidDuration > 1){
                    $ValidDuration = $oldValidDuration;
                }

                $ValidDurationInDays = $ValidDuration / 86400;

                if ($ValidDurationInDays > 0) {
                    $start->startOfDay();
                    $end->startOfDay();
                    if($IsCaseTypeDown == true && $outageTKT == false){
                        if($ValidDurationInDays > 10 ){
                            $needToCheekUsage = true ;
                            $needToUsage = true ;
                            if ($selectFollowUp == true) {
                                $start->addDays(10);
                            }
                        }else{
                            $needToCheekUsage = false ;
                            $needToUsage = false ;
                        }
                    }else{
                        $needToCheekUsage = true ;
                        $needToUsage = true ;
                        if($ValidDuration <= 86400 && $outageTKT == false){

                            $needToUsage = false ;
                            $needToCheekUsage = false ;
                        }
                        if($ValidDurationInDays > 10 && $selectFollowUp == true && $outageTKT == false){
                            $start->addDays(10);
                        }


                    }

                    if($needToUsage){
                        foreach ($usage as $date => $details) {
                            $dailyUsage = $details['total_usage'];
                            $usageDate = Carbon::parse($date);
                            $exceededQouta = $details['free_unit_before'];
                            $DSLno = $details['dsl_number'];
                            // if down less than 10 days and Follow up
                            if ($selectFollowUp === true && $IsCaseTypeDown === true && $ValidDurationInDays <= 10 && $outageTKT == false) {

                                if ($usageDate->between($start, $end) && $exceededQouta <= 0.2) {
                                    $usageMessage[] = $usageDate;
                                    $ineligibleDays++;
                                }
                            } else {
                                if ($usageDate->between($start, $end)) {
                                    $filteredUsage[$date] = $details;

                                    if ($usageLimit < $dailyUsage || $exceededQouta < 0.2 || $majorfultended == 1) {
                                        $filteredUsage[$date]['color'] = 'red';
                                        $ineligibleDays++;
                                        if ($exceededQouta < 0.2) {
                                            $filteredUsage[$date]['note'] = '( exceeded  quota )';
                                        }
                                        if ($orgData[$i]->close_code == 20 || in_array($orgData[$i]->close_code, $MajorFaultCodes)) {
                                            $majorfultended = 1;
                                        }

                                    } else {
                                        $filteredUsage[$date]['color'] = 'green';

                                    }
                                    if ($exceededQouta <= 0.2) {
                                        $usageMessage[] = $usageDate;

                                    }
                                }
                            }

                        }
                    }

                $ValidDuration = $ValidDurationInDays * 86400;
                }

            } else {
                $validation = false;
                $reason = $orgData[0]->reason;
            }

        } elseif ($newEscalationTimes >= 2 && $EscalationTimesValidation > 0) {
            $start = Carbon::parse($orgData[0]->transfer_time);


            $i = $EscalationTimesValidation - 1;
            $end = Carbon::parse($orgData[$i]->close_time);
            $ValidDuration = ($start->copy()->startOfDay()->diffInDays($end->copy()->startOfDay()) + 1)*86400;

            $oldValidDuration = $start->diffInSeconds($end);
            if($oldValidDuration <= 86400 && $ValidDuration > 1){
                $ValidDuration = 86400;
            }


            $ValidDurationInDays = $ValidDuration / 86400;
            $w = 0;


            if($IsCaseTypeDown == true && $outageTKT == false){
                if($ValidDurationInDays > 10 ){
                    $needToCheekUsage = true ;
                    $needToUsage = true ;
                    if ($selectFollowUp == true) {
                        $start->addDays(10);
                    }
                }else{
                    $needToCheekUsage = false ;
                    $needToUsage = false ;
                }

            }else{
                $needToCheekUsage = true ;
                $needToUsage = true ;
                if($oldValidDuration < 86400 && $outageTKT == false){
                    $needToUsage = false ;
                    $needToCheekUsage = false ;
                }
                if($ValidDurationInDays > 10 && $selectFollowUp == true && $outageTKT == false){
                    $start->addDays(10);
                }
            }


            $lastdate = Carbon::parse($orgData[0]->close_time);
            $startFrom = 0;
            $Duration = 0;



            foreach ($newOrgData as $item) {
                if ($item->id == 1) {
                    $lastdate = Carbon::parse($newOrgData[0]->close_time);
                    $startFrom = Carbon::parse($item->transfer_time);

                } else {
                    $startFrom = Carbon::parse($item->transfer_time);
                    $Duration = $lastdate->diffInSeconds($startFrom);
                    if ($Duration > 172800) {
                        $checkDurationUsage[] = ['end' => $startFrom , 'start' => $lastdate];
                    }
                    $lastdate = Carbon::parse($item->close_time);
                }
            }
            if ($needToUsage == true && $EscalationTimesValidation > 0) {

                foreach ($usage as $date => $details) {
                    $dailyUsage = $details['total_usage'];
                    $usageDate = Carbon::parse($date);
                    $exceededQouta = $details['free_unit_before'];
                    $DSLno = $details['dsl_number'];
                    $start->startOfDay();
                    $end->startOfDay();
                    // حساب الايام الفاليد للتعويض

                    if ($checkDurationUsage == null){
                        if ($usageDate->between($start, $end)) {
                            $filteredUsage[$date] = $details;
                            if ($usageLimit < $dailyUsage || $exceededQouta < 0.2) {
                                $filteredUsage[$date]['color'] = 'red';
                                $ineligibleDays++;
                                if ($exceededQouta < 0.2) {
                                    $filteredUsage[$date]['note'] = '( exceeded  quota )';
                                }
                            } else {
                                $filteredUsage[$date]['color'] = 'green';
                            }
                            if ($exceededQouta <= 0.2) {
                                $usageMessage[] = $usageDate;
                            }

                        }

                    }else{
                        if ($usageDate->between($start, $end)) {
                            $filteredUsage[$date] = $details;
                            if($usageLimit < $dailyUsage || $exceededQouta < 0.2) {
                                $filteredUsage[$date]['color'] = 'red';
                                $ineligibleDays++;
                                if ($exceededQouta < 0.2) {
                                    $filteredUsage[$date]['note'] = '( exceeded  quota )';
                                }
                                if ($start == $usageDate) {
                                }
                                if ($end == $usageDate) {

                                }
                            } else {
                                $filteredUsage[$date]['color'] = 'green';
                            }
                            if ($exceededQouta <= 0.2) {
                                $usageMessage[] = $usageDate;
                            }

                        }

                        foreach ($checkDurationUsage as $period) {
                            $invalidstart = $period['start'];
                            $invalidend = $period['end'];
                            $invalidstart = Carbon::parse($invalidstart)->toDateString(); // الناتج: "2025-04-08"
                            if ($usageDate->between($invalidstart, $invalidend) && !$usageDate->between($start, $end)) {
                                $filteredUsage[$date] = $details;
                                if ($usageLimit < $dailyUsage || $exceededQouta < 0.2) {
                                    $filteredUsage[$date]['color'] = 'red';
                                    $ineligibleDays++;
                                    if ($exceededQouta < 0.2) {
                                        $filteredUsage[$date]['note'] = '( exceeded  quota )';
                                    }
                                    if ($start == $usageDate) {
                                    }
                                    if ($end == $usageDate) {

                                    }
                                } else {
                                    $filteredUsage[$date]['color'] = 'green';
                                }
                                if ($exceededQouta <= 0.2) {
                                    $usageMessage[] = $usageDate;
                                }
                            }
                        }
                    }
                    $w++;
                }
            } elseif ($needToUsage === false && $EscalationTimesValidation > 0) {


                $start = Carbon::parse($orgData[0]->transfer_time);
                $i = $EscalationTimesValidation - 1;
                $end = Carbon::parse($orgData[$i]->close_time);
                //$ValidDuration = $start->diffInSeconds($end);
                $ValidDuration = ($start->copy()->startOfDay()->diffInDays($end->copy()->startOfDay()) + 1)*86400;

                $oldValidDuration = $start->diffInSeconds($end);
                if($oldValidDuration <= 86400 && $ValidDuration > 1){
                    $ValidDuration = 86400;
                }
                $start->startOfDay();
                $end->startOfDay();

                $ValidDurationInDays = $ValidDuration / 86400;

                // Check if the case is a "down" case and not an outage ticket
                if ($IsCaseTypeDown == true && $outageTKT == false) {

                    // Default: No need to check usage or require usage data
                    $needToCheekUsage = false;
                    $needToUsage = false;

                    // If the valid duration is more than 10 days
                    if ($ValidDurationInDays > 10) {

                        // Now we need to check usage and usage data is required
                        $needToCheekUsage = true;
                        $needToUsage = true;

                        // If the user selected a follow-up option
                        if ($selectFollowUp === true) {
                            // Add 10 days to the start date
                            $start->addDays(10);
                        }

                    }

                }else{
                    $needToCheekUsage = true ;
                    $needToUsage = true ;
                    if($oldValidDuration < 86400 && $outageTKT == false){
                        $needToUsage = false ;
                        $needToCheekUsage = false ;
                    }
                    if($ValidDurationInDays > 10 && $selectFollowUp == true && $outageTKT == false){
                        $start->addDays(10);
                    }
                }

                if($needToUsage === true){
                    $w = 0;

                    foreach ($usage as $date => $details) {
                        $dailyUsage = $details['total_usage'];
                        $usageDate = Carbon::parse($date);
                        $exceededQouta = $details['free_unit_before'];
                        $DSLno = $details['dsl_number'];
                        $start->startOfDay();
                        $end->startOfDay();

                        // حساب الايام الفاليد للتعويض
                        if ($checkDurationUsage == null){
                            if ($usageDate->between($start, $end)) {
                                $filteredUsage[$date] = $details;
                                if ($usageLimit < $dailyUsage || $exceededQouta < 0.2 || $majorfultended == 1) {
                                    $filteredUsage[$date]['color'] = 'red';
                                    $ineligibleDays++;

                                    if ($exceededQouta < 0.2) {
                                        $filteredUsage[$date]['note'] = '( exceeded  quota )';
                                    }
                                    if ($orgData[$i]->close_code == 20 || in_array($orgData[$i]->close_code, $MajorFaultCodes)) {
                                        $majorfultended = 1;
                                    }

                                } else {
                                    $filteredUsage[$date]['color'] = 'green';

                                }
                                if ($exceededQouta <= 0.2) {
                                    $usageMessage[] = $usageDate;
                                }
                            }
                        }else{
                            if ($usageDate->between($start, $end)) {
                                $filteredUsage[$date] = $details;
                                if ($usageLimit < $dailyUsage || $exceededQouta < 0.2 || $majorfultended == 1) {
                                    $filteredUsage[$date]['color'] = 'red';
                                    $ineligibleDays++;

                                    if ($exceededQouta < 0.2) {
                                        $filteredUsage[$date]['note'] = '( exceeded  quota )';
                                    }
                                    if ($orgData[$i]->close_code == 20 || in_array($orgData[$i]->close_code, $MajorFaultCodes)) {
                                        $majorfultended = 1;
                                    }

                                } else {
                                    $filteredUsage[$date]['color'] = 'green';

                                }
                                if ($exceededQouta <= 0.2) {
                                    $usageMessage[] = $usageDate;
                                }
                            }

                            foreach ($checkDurationUsage as $period) {
                                $invalidstart = $period['start'];
                                $invalidend = $period['end'];
                                $invalidstart = Carbon::parse($invalidstart)->toDateString(); // الناتج: "2025-04-08"
                                if ($usageDate->between($invalidstart, $invalidend) && !$usageDate->between($start, $end)) {
                                    $filteredUsage[$date] = $details;
                                    if ($usageLimit < $dailyUsage || $exceededQouta < 0.2) {
                                        $filteredUsage[$date]['color'] = 'red';
                                        $ineligibleDays++;
                                        if ($exceededQouta < 0.2) {
                                            $filteredUsage[$date]['note'] = '( exceeded  quota )';
                                        }

                                    } else {
                                        $filteredUsage[$date]['color'] = 'green';
                                    }
                                    if ($exceededQouta <= 0.2) {
                                        $usageMessage[] = $usageDate;
                                    }
                                }
                            }

                        }
                        $w++;
                    }
                }else{
                    //$needToCheekUsage = true ;
                    //$needToUsage = true;
                    foreach ($usage as $date => $details) {

                        $dailyUsage = $details['total_usage'];
                        $usageDate = Carbon::parse($date);
                        $exceededQouta = $details['free_unit_before'];
                        $DSLno = $details['dsl_number'];
                        foreach ($checkDurationUsage as $period) {
                            $invalidstart = $period['start'];
                            $invalidend = $period['end'];
                            $start->startOfDay();
                            $end->startOfDay();
                            if ($usageDate->between($invalidstart, $invalidend)) {
                                $filteredUsage[$date] = $details;
                                if ($usageLimit < $dailyUsage || $exceededQouta < 0.2) {
                                $filteredUsage[$date]['color'] = 'red';
                                $ineligibleDays++;
                                if ($exceededQouta < 0.2) {
                                    $filteredUsage[$date]['note'] = '( exceeded  quota )';
                                }
                                if ($orgData[$i]->close_code == 20 ||  in_array($orgData[$i]->close_code, $MajorFaultCodes)) {
                                    $majorfultended = 1;
                                }
                                } else {
                                    $filteredUsage[$date]['color'] = 'green';
                                }
                                if ($exceededQouta <= 0.2) {
                                    $usageMessage[] = $usageDate;
                                }
                            }
                        }
                    }
                }


            }
        }
        $start = Carbon::parse($orgData[0]->transfer_time);
        $end = Carbon::parse($orgData[$i]->close_time);

        $ineligibleDays = $ineligibleDays * 86400;
        if($ValidDuration > 864000){
            $needToCheekFollowUP = true ;
        }

        $ValidDuration = $ValidDuration - $ineligibleDays;
        $starttocheckless24 = Carbon::parse($orgData[0]->transfer_time);
        $ii = $EscalationTimes - 1;

        $endtocheckless24 = Carbon::parse($orgData[$ii]->close_time);
        $totalDurationtocheckless24 = $starttocheckless24->diffInSeconds($endtocheckless24);

        if($totalDurationtocheckless24 >= 86400){
            $ValidDuration = $ValidDuration / 86400;
            $ValidDuration = ceil($ValidDuration);
            $ValidDuration = $ValidDuration * 86400 ;
        }
        $startx = Carbon::parse($orgData[0]->transfer_time);
        $endx = Carbon::parse($orgData[$i]->close_time);
        $oldValidDuration = $startx->diffInSeconds($endx);

        if($oldValidDuration < 86400 && $ValidDuration == 86400){
            $ValidDuration = $oldValidDuration;
            $needToUsage = false;
            $needToCheekUsage = false ;

        }elseif($ValidDuration == 86400){
            $needToUsage = false;
            $needToCheekUsage = false ;

        }
        // breifing for 3/2/2025
        if ($ValidDuration < 43200) {
            $ValidDuration = 0;
            $reason = 'its less than 12 hr';
            $validation = false;

        }

        $startDate = Carbon::parse($orgData[0]->transfer_time)->format('d F Y h:i A');
        $closeDate = Carbon::parse($lastEscalation->close_time)->format('d F Y h:i A');
        $curuantSupportPool = $lastEscalation->support_group;





        if ($lastEscalation->close_code == 'N/A' || $lastEscalation->close_code == null || $lastEscalation->close_code == 0 || $ticket_close_time == null || $Waiting_for_IT == true ||$curuantSupportPool_all == 'Installation - Operations') {
            if($curuantSupportPool_all == 'CC Second Level Support' && $SLS_owner == false && $lastEscalation->close_code != 'N/A'){
                $curuantSupportPool_start = Carbon::parse($lastEscalation->transfer_time);
                $curuantSupportPool_end = Carbon::now()->toDateTimeString();
                $lastEscalation->duration_seconds = $curuantSupportPool_start->diffInSeconds($curuantSupportPool_end);
                $lastEscalation->SLA = 7200;
            }

        }







        if($lastEscalation->close_code == 26){
            $ValidDuration = 0;
            $reason = $lastEscalation->reason;
            $validation = false;
        }





        $i = $EscalationTimes - 1;
        $startw = Carbon::parse($orgData[0]->transfer_time);
        $endw = Carbon::parse($orgData[$i]->close_time);
        $totalDuration = $startw->diffInSeconds($endw);



        if (in_array($orgData[0]->close_code, $codesNoNeedChckUsage) && $ValidDuration <86400) {
            $needToCheekUsage = false;

        }
        if($lastEscalation->close_code == 104){
            $validation = false;
            $reason = 'CST problem';
        }
        if($orgData[0]->ticket_close_time) {
            if($reason == ''){
                $reason = $orgData[$i]->reason;
            }
        }







        $transferToCount = count($orgData) + $negativeCounter ;


        if($ValidDuration > 15552000 ){
            $ValidDuration = 15552000 ;
            $reason = "Maximum allowed duration to compensation is 6 months";
        }
        if($ValidDuration > 259200 && $optimizationPeriod == true ){
            $ValidDuration = 259200 ;
            $tktStillOpen = false;
        }
        if($ValidDuration == 0 && $oldValidDuration > 86400 && $EscalationTimesValidation > 0){
            $validation = false;
            if($reason == 'its less than 12 hr'){
                $reason = "The customer has high usage";
            }else{
                 $reason .= " - with High Usage";
            }

        }elseif($EscalationTimesValidation == 0){
            $validation = false;
            $reason = "Customer Side Problem";
        }

        $third_level_esclated = false ;
        foreach( $orgData as $item){
            if(in_array($item->support_group, $ThirdSupportGroups)){
                $third_level_esclated = true ;
            }
        }
        if($third_level_esclated == false && $validation == 1){
            $validation = 0  ;
            $ValidDuration = 0 ;
            $reason = "Ticket not Escalated to 3rd Level Pool";
        }
        if($DSLno == ''){
            foreach ($usage as $date => $details) {
            $DSLno = $details['dsl_number'];
                if($DSLno != ''){
                    break;
                }
            }
        }

        if($checkDurationUsage != null && $needToCheekUsage == false){
            $needToCheekUsage = true ;

        }



        $usagecount = count($usageMessage) ;
        $newusageMessage = '';
        if($usagecount == 1){
           $newusageMessage = 'CST in throttling speed in '.$usageMessage[0]->toDateString().' ';
        }elseif($usagecount > 1){
            $last = $usageMessage[0]->toDateString();
            $first = $usageMessage[$usagecount - 1]->toDateString();

            $newusageMessage = 'CST throttling started From ' . $first .' to ' . $last;
        }

        $usageMessageText = $newusageMessage;
        if($EngInspection){
            $reason = 'CST has Engineering inspection';
            $validation = false ;
            $ValidDuration = 0 ;
            $needToCheekUsage = false ;
            $needToUsage = false ;
        }

        //$reason = $test;
        return [
            'validation' => $validation,
            'reason' => $reason,
            'totalDuration' => $totalDuration,
            'ValidDuration' => $ValidDuration,
            'filteredUsage' => $filteredUsage,
            'usageMessage' => $usageMessageText,
            'startDate' => $startDate,
            'closeDate' => $closeDate,
            'curuantSupportPool' => $curuantSupportPool,
            'delayId' => $delayId,
            'accelerationId' => $accelerationId,
            'ticket_close_time' => $ticket_close_time,
            'needToUsage' => $needToCheekUsage ,
            'lastClodeCode' => $lastClodeCode ,
            'DSLno' => $DSLno ,
            'tktStillOpen' => $tktStillOpen ,
            'outageTKT' => $outageTKT ,
            'transferToCount' => $transferToCount ,
            'optimizationPeriod' => $optimizationPeriod ,
            'outageTKT_onADF' => $outageTKT_onADF ,
            'needToCheekFollowUP' => $needToCheekFollowUP ,
            'cst_has_tkt_before' => $cst_has_tkt_before ,
            'cst_compansated_before' => $cst_compansated_before ,
            'tkt_rejected_reason' => $tkt_rejected_reason ,
            'compansated_status' => $compansated_status ,
            'compansated_added_on' => $compansated_added_on ,
            'api_section' => $api_section ,
        ];
    }
}
