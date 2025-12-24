<?php

namespace App\Models\Outage;

use Carbon\Carbon;
use App\Services\ConcessionApiService;

class OutageValidation
{
    public static function validate($data, $service_number ,$orgData, $usage )
    {
        $DSLno = '';
        $totalDuration = $orgData->Duration;
        $problemType = '' ;

        if (empty($orgData)) {
            return ['validation' => false, 'reason' => 'No data available'];
        }



        // fix last escalation but not included in the orgData



        $validation = true;
        $reason = '';
        $ValidDuration = $orgData->Valid_duration;
        $filteredUsage = [];
        $usageMessage = [];
        $exceededQouta = false;
        $i = 0 ;
        $now = Carbon::now();
        $start = Carbon::parse($orgData->From);

        $startx = Carbon::parse($orgData->From);
        $endx = Carbon::parse($orgData->To);
        $oldValidDuration = $startx->diffInSeconds($endx);

        if($oldValidDuration < 86400){
            $ValidDuration = $oldValidDuration ;
        }

        $end = Carbon::parse($orgData->To);

        //API CALL
        $cst_has_tkt_before = false ;
        $tkt_id = $orgData->ID;
        $api = new ConcessionApiService();
        $apiResponse = $api->send($service_number , $tkt_id);

        if(!empty($apiResponse)){

            $apiResponse = array_filter($apiResponse, function ($item) {
                return $item['section'] !== 'phone';
            });

            foreach($apiResponse as $resp){

                $cst_has_tkt_before = true ;
                $compansated_status = $resp['status'];
                if($compansated_status == 'approved'){
                    $cst_compansated_before = true ;
                }else{
                    if($compansated_status == 'pending'){
                        $cst_has_tkt_before = false ;
                        continue;
                    }
                    $tkt_rejected_reason = $resp['rejected_reason'];
                }

            }
        }



        $needToUsage = $orgData->needToUsage ;




        $comment = strtolower($orgData->Comment_Added_by);

        if($orgData->Problem_type ==  'Major Fault'){
            if (str_contains(strtolower($comment), 'صيانه')) {
                $orgData->Problem_type = 'Major Fault - Maintenance' ;
            }elseif(str_contains(strtolower($comment), 'unms')){
                $orgData->Problem_type = 'Major Fault - UNMS' ;
            }
        }

        $comment = str_replace(['أ','إ','آ'], 'ا', $comment);
        $comment = str_replace('ى', 'ي', $comment);
        $comment = str_replace(['ة'], 'ه', $comment);


        if($orgData->Problem_type ==  'Down'){
            $generalKeywords = ['اتلاف', 'حياه كريمه', 'سرقه' , 'احلال', 'طوارئ', 'طوارى'];

            foreach ($generalKeywords as $word) {
                if (str_contains($comment, $word)) {
                    $orgData->Problem_type = 'Major Fault';
                    break;
                }
            }
        }




        $ineligibleDays = 0 ;
        $usageStart = Carbon::parse($orgData->FollowUpStartDate)->startOfDay();
        $checkDurationUsage = [];


        if ($orgData->Valid_duration == 0) {
            $start = Carbon::parse($orgData->From);
            $end = Carbon::parse($orgData->To);
            //$ValidDuration = $start->diffInSeconds($end);

            $ValidDuration = ($start->copy()->startOfDay()->diffInDays($end->copy()->startOfDay()) + 1)*86400;

            $oldValidDuration = $start->diffInSeconds($end);
            if($oldValidDuration <= 86400 && $ValidDuration > 1){
                $ValidDuration = 86400;
            }elseif($oldValidDuration < 21600){
                $ValidDuration = 0;
            }
            $usageLimit = $orgData->usageLimit ;
            $endx = $end->endOfDay();

            $ValidDurationInDays = $ValidDuration / 86400;
            if($needToUsage){


                foreach ($usage as $date => $details) {
                    $dailyUsage = $details['total_usage'];
                    $usageDate = Carbon::parse($date);
                    $exceededQouta = $details['free_unit_before'];
                    $DSLno = $details['dsl_number'];

                    if ($usageDate->between($usageStart, $endx)) {
                        $filteredUsage[$date] = $details;
                        if ($usageLimit < $dailyUsage || $exceededQouta < 0.2) {
                            $filteredUsage[$date]['color'] = 'red';
                            $ineligibleDays ++;
                            if ($exceededQouta < 0.2) {
                                $filteredUsage[$date]['note'] = '( exceeded  quota )';
                            }

                        } else {
                            $filteredUsage[$date]['color'] = 'green';

                        }
                        if ($exceededQouta <= 0.2) {
                            $usageMessage[] = 'CST in throttling speed in '.$usageDate.' ';
                        }

                    }

                }
            }

            $ValidDuration = $ValidDurationInDays * 86400;

        }


        $start = Carbon::parse($orgData->From);
        $end = Carbon::parse($orgData->To);

        $oldValidDuration = $ValidDuration ;

        if($oldValidDuration < 86400 && $ValidDuration == 86400){
            $ValidDuration = $oldValidDuration;
        }
        $usageMessageText = implode('<br>', $usageMessage);


        if ($totalDuration < 21600 ) {
            $ValidDuration = 0;
            if($orgData->reason == null){
                $orgData->reason = 'its less than 6 hr';
            }
        }else{
            $ineligibleDays = $ineligibleDays * 86400;
            $ValidDuration = $ValidDuration - $ineligibleDays;
            $orgData->validation = true;
            $validation = $orgData->validation ;
            $orgData->Valid_duration = $ValidDuration;
        }





        $reason = $orgData->reason ?? '';
        if($ValidDuration > 15552000 ){
            $ValidDuration = 15552000 ;
            $reason = "Maximum allowed duration to compensation is 6 months";
        }
        if($ValidDuration == 0 && $totalDuration > 86400 ){
            $validation = false;
            $reason = "with High Usage";
        }


        $problemType = $orgData->Problem_type ;
        $validation = $orgData->validation ;
        if($cst_has_tkt_before == true){
            $validation = false;
            $reason = "<mark>Duplicated</mark>";
            $ValidDuration = 0 ;
        }
        $startDate = Carbon::parse($start)->format('d F Y h:i A');
        $closeDate = Carbon::parse($end)->format('d F Y h:i A');




        return [
            'validation' => $validation,
            'reason' => $reason,
            'totalDuration' => $totalDuration,
            'ValidDuration' => $ValidDuration,
            'filteredUsage' => $filteredUsage,
            'usageMessage' => $usageMessageText,
            'startDate' => $startDate,
            'closeDate' => $closeDate,
            'DSLno' => $DSLno ,
            'needToUsage' => $needToUsage ,
            'problemType' => $problemType ,
            'cst_has_tkt_before' => $cst_has_tkt_before ,

        ];
    }
}
