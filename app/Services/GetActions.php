<?php

namespace App\Services;

class GetActions
{
    public static function getActions(
        $type,
        $problemType,
        $amount,
        $quota,
        $hwoAddGB,
        $hwoAddLE,
        $specialHandling,
        $tktStillOpen,
        $is_telephonet ,
        $tkt_id ,
        $satisfaction ,
        $validDays

    ): array {
        $type = strtolower(trim($type));
        $problemType = strtolower(trim($problemType));
        $hwoAddGB    = strtolower(trim($hwoAddGB));
        $hwoAddLE    = strtolower(trim($hwoAddLE));
        $specialHandling = strtolower(trim($specialHandling));
        $tktStillOpen    = filter_var($tktStillOpen, FILTER_VALIDATE_BOOLEAN);
        $is_telephonet    = filter_var($is_telephonet, FILTER_VALIDATE_BOOLEAN);
        $actions = [];

        if ($validDays < 1) {
            $hours = ceil($validDays * 24);
            $validDaysText = $hours . ' ' . ($hours == 1 ? 'Hour' : 'Hours');
        } else {
            $days = ceil($validDays);
            $validDaysText = $days . ' ' . ($days == 1 ? 'Day' : 'Days');
        }


        switch ($type) {
            case 'compensation' :
                if($problemType == 'outage'){
                    $ticketType = 'Outage';
                }else{
                    $ticketType = 'Ticket';
                }
                $GBactions = self::ttsCompensationActions(
                    'GB',
                    $problemType,
                    $amount,
                    $quota,
                    $hwoAddGB,
                    $specialHandling,
                    $tktStillOpen,
                    $is_telephonet ,
                    $tkt_id ,
                    $satisfaction ,
                    $validDays,
                    $validDaysText,
                    $ticketType
                );
                $LEactions = self::ttsCompensationActions(
                    'LE',
                    $problemType,
                    $amount,
                    $quota,
                    $hwoAddLE,
                    $specialHandling,
                    $tktStillOpen,
                    $is_telephonet ,
                    $tkt_id ,
                    $satisfaction ,
                    $validDays,
                    $validDaysText,
                    $ticketType
                );

                $actions = self::mergeActions($GBactions, $LEactions);
                if($quota > 0){
                    $serviceContent ='has been Refuse to compensated for his '.$ticketType.' ID :  '.$tkt_id.' with a value of '.$quota.' GB OR '.$amount.' EGP '.$satisfaction.' for the Valid period due for compensation , which is: '.$validDaysText.' and his phone Number is ';

                    $actions[] = [
                        'type'       => 'General',
                        'label'      => 'Refuse',
                        'sr_type'    => 'SR',
                        'sr_id'      => '099019020',
                        'sr_name'    => 'CST refuse the Concession',
                        'sla'        => null,
                        'quota'      => $quota,
                        'amount'     => $amount,
                        'expireDays' => null,
                        'serviceContent'=> $serviceContent
                    ];

                }
                break;
             default:
                // nothing
                break;
        }



        return $actions;
    }

    private static function mergeActions(array $actions1, array $actions2): array
    {
        $merged = [];
        $seenIds = [];

        $allActions = array_merge($actions1, $actions2);

        foreach ($allActions as $action) {
            $id = $action['sr_id'];

            if (isset($seenIds[$id])) {
                foreach ($merged as $key => $mergedAction) {
                    if ($mergedAction['sr_id'] === $id) {
                        if (isset($mergedAction['serviceContent']) && isset($action['serviceContent']) &&
                            $mergedAction['serviceContent'] === $action['serviceContent']) {

                            $merged[$key]['type'] = 'General';

                            if ($merged[$key]['label'] !== $action['label']) {
                                $merged[$key]['label'] = $merged[$key]['label'] . '/' . $action['label'];
                            }
                        } else {
                            $merged[] = $action;
                        }
                    }
                }
            } else {
                $seenIds[$id] = true;
                $merged[] = $action;
            }
        }

        return $merged;
    }


    private static function ttsCompensationActions(
        $type,
        $problemType,
        $amount,
        $quota,
        $responsibleTeam,
        $specialHandling,
        $tktStillOpen ,
        $is_telephonet ,
        $tkt_id ,
        $satisfaction ,
        $validDays,
        $validDaysText ,
        $ticketType
    ): array {
        $serviceContent = null;
        $actions = [];
        if($satisfaction == 0){
            $satisfaction = 'without satisfaction';
        }else{
            $satisfaction ='and satisfaction : '.$satisfaction . ' GB' ;
        }
        if ($responsibleTeam === 'not eligible') {

            if($quota == 0){
                $actions[] = [
                'type'       => 'Not Eligible',
                'label'      => 'Not Eligible',
                'sr_type'    => 'SR',
                'sr_id'      => '100034002',
                'sr_name'    => 'Technical Concession',
                'sla'        => null,
                'quota'      => null,
                'amount'     => null,
                'expireDays' => null,
                'serviceContent'=> 'not eligible for compensation for '.$ticketType.' ID: '. $tkt_id . ' Customer confirmed satisfaction. Customer phone number is '
                ];
                $actions[] = [
                    'type'       => 'Not Eligible',
                    'label'      => 'Complaint',
                    'sr_type'    => 'TT',
                    'sr_id'      => '099019002',
                    'sr_name'    => 'Technical Concession',
                    'sla'        => null,
                    'quota'      => null,
                    'amount'     => null,
                    'expireDays' => null,
                    'serviceContent'=> 'not eligible for compensation for '.$ticketType.' ID: '. $tkt_id . ', and the customer is not satisfied and requested to raise a complaint. Customer phone number is '

                ];
            }

        }elseif($responsibleTeam === 'agent on spot'){
            $sr_id = '100034007' ;
            $sr_name = "Tech Concession On Spot-Approved";


            if($tktStillOpen){
                $sr_id = '100034026' ;
                $sr_name = "Tech Concession On Spot-Ticket open";

                if($type == 'LE'){
                    $serviceContent ='has been compensated for his '.$ticketType.' ID :  '.$tkt_id.' with a value of '.$amount.' EGP '.$satisfaction.' for the Valid period due for compensation , which is: '.$validDaysText.', Problem solved but ticket still open , and his phone Number is ';
                }else{
                    $serviceContent ='has been compensated for his '.$ticketType.' ID :  '.$tkt_id.' with a value of '.$quota.' GB '.$satisfaction.' for the Valid period due for compensation , which is: '.$validDaysText.', Problem solved but ticket still open , and his phone Number is ';
                }

            }else{
                if($validDays <= 0.5){
                    $sr_name = 'Outage from 6h to 12h– On Spot';
                }
                if($type == 'LE'){
                    $serviceContent ='has been compensated for his '.$ticketType.' ID :  '.$tkt_id.' with a value of '.$amount.' EGP '.$satisfaction.' for the Valid period due for compensation , which is: '.$validDaysText.' and his phone Number is ';

                }else{
                    $serviceContent ='has been compensated for his '.$ticketType.' ID :  '.$tkt_id.' with a value of '.$quota.' GB '.$satisfaction.' for the Valid period due for compensation , which is: '.$validDaysText.' and his phone Number is ';
                }

            }
            $actions[] = [
                'type'       => $type,
                'label'      => 'Added on Spot',
                'sr_type'    => 'SR',
                'sr_id'      => $sr_id,
                'sr_name'    => $sr_name,
                'sla'        => null,
                'quota'      => $quota,
                'amount'     => $amount,
                'expireDays' => null,
                'serviceContent'=> $serviceContent
            ];
            if($specialHandling === 'clmle agent on spot'){
                $serviceContent ='asked for compensation regarding '.$ticketType.' ID :  '.$tkt_id.' for the Valid period due for compensation , which is: '.$validDaysText.'The compensation Satisfaction Gigabytes has been Added with : '.$satisfaction.' and amount with a value of '.$amount.' EGP need to be added, and his phone Number is ';
                $actions[] = [
                'type'       => 'LE',
                'label'      => 'Satisfaction Quota',
                'sr_type'    => 'SR',
                'sr_id'      => '100065001',
                'sr_name'    => 'Satisfaction Quota - action Done',
                'sla'        => null,
                'quota'      => $quota,
                'amount'     => $amount,
                'expireDays' => null,
                'serviceContent'=> $serviceContent
                ];
            }elseif($specialHandling === 'clmgb agent on spot'){
                $serviceContent ='asked for compensation regarding '.$ticketType.' ID :  '.$tkt_id.' for the Valid period due for compensation , which is: '.$validDaysText.'The compensation Amount in EGB has been Added with : '.$amount.' EGP and Satisfaction Gigabytes with a value of '.$satisfaction.' need to be added, and his phone Number is ';
                $actions[] = [
                'type'       => 'LE',
                'label'      => 'Satisfaction Quota',
                'sr_type'    => 'SR',
                'sr_id'      => '100065001',
                'sr_name'    => 'Satisfaction Quota - action Done',
                'sla'        => null,
                'quota'      => $quota,
                'amount'     => $amount,
                'expireDays' => null,
                'serviceContent'=> $serviceContent
                ];
            }
        }elseif($responsibleTeam === 'clm team sla 15 min' || $responsibleTeam === 'clm team & billing sla 75 min (8am:9pm except friday 2pm:9pm)'){

            if($type == 'LE'){
                $serviceContent ='asked for compensation regarding '.$ticketType.' ID :  '.$tkt_id.' with a value of '.$amount.' EGP '.$satisfaction.' for the Valid period due for compensation , which is: '.$validDaysText.' and his phone Number is ';
            }else{
                $serviceContent ='asked for compensation regarding '.$ticketType.' ID :  '.$tkt_id.' with a value of '.$quota.' GB '.$satisfaction.' for the Valid period due for compensation , which is: '.$validDaysText.' and his phone Number is ';
            }

            if($specialHandling === 'clmle agent on spot'){
                $serviceContent ='asked for compensation regarding '.$ticketType.' ID : '.$tkt_id.'for the Valid period due for compensation , which is: '.$validDaysText.'The compensation Satisfaction Gigabytes has been Added with : '.$satisfaction.' and amount with a value of '.$amount.' EGP need to be added, and his phone Number is ';
            }elseif($specialHandling === 'clmgb agent on spot'){
                $serviceContent ='asked for compensation regarding '.$ticketType.' ID : '.$tkt_id.'for the Valid period due for compensation , which is: '.$validDaysText.'The compensation Amount in EGB has been Added with : '.$amount.' EGP and Satisfaction Gigabytes with a value of '.$satisfaction.' need to be added, and his phone Number is ';
            }

            if($responsibleTeam === 'clm team sla 15 min'){
                $sla = '15 Minuts';
            }else{
               $sla = '75 Minuts';
            }
            switch (strtolower($problemType)) {
                case 'data down':
                case 'adsl/vdsl':
                    $sr_id = '101003014' ;
                    $sr_name = 'Tech Concession Data Down';
                    if($is_telephonet){
                        $sr_id = '095002008' ;
                    }
                    break;
                case 'voice overlapping':
                    $sr_id = '101003019' ;
                    $sr_name = 'Tech Concession Voice overlapping';
                    if($is_telephonet){
                        $sr_id = '095002013' ;
                    }
                    break;
                case 'data and voice down':
                case 'voice and data down':
                case 'voice down (data down impacted)':
                    $sr_id = '101003023' ;
                    $sr_name = 'Tech Concession Data and Voice Down';
                    if($is_telephonet){
                        $sr_id = '095002017' ;
                    }
                    break;

                case 'wrong card and port':
                case 'wcap':
                    $sr_id = '101003020' ;
                    $sr_name = 'Tech Concession WCAP';
                    if($is_telephonet){
                        $sr_id = '095002014' ;
                    }
                    break;

                case 'voice down (data instability impacted)':
                case 'physical instability':
                case 'installation':
                    $sr_id = '101003015' ;
                    $sr_name = 'Tech Concession Physical instability';
                    if($is_telephonet){
                        $sr_id = '095002009' ;
                    }
                    break;

                case 'logical instability':
                case 'logical instability - no multiple logs':
                    $sr_id = '101003017' ;
                    $sr_name = 'Tech Concession Logical Instability';
                    if($is_telephonet){
                        $sr_id = '095002011' ;
                    }
                    break;

                case 'bad line quality':
                case 'blq':
                    $sr_id = '101003024' ;
                    $sr_name = 'Tech Concession BLQ';
                    if($is_telephonet){
                        $sr_id = '095002018' ;
                    }
                    break;

                case 'need optimization':
                    $sr_id = '101003025' ;
                    $sr_name = 'Tech Concession Need optimization';
                    if($is_telephonet){
                        $sr_id = '095002019' ;
                    }
                    break;

                case 'slowness':
                case 'speed':
                    $sr_id = '101003022' ;
                    $sr_name = 'Tech Concession Slowness or Utilization';
                    if($is_telephonet){
                        $sr_id = '095002016' ;
                    }
                    break;

                case 'browsing':
                case 'browsing - certain sites':
                    $sr_id = '101003026' ;
                    $sr_name = 'Tech Concession browsing';
                    if($is_telephonet){
                        $sr_id = '095002020' ;
                    }
                    break;

                case 'unable to obtain ip':
                    $sr_id = '101003018' ;
                    $sr_name = 'Tech Concession Unable to Obtain IP';
                    if($is_telephonet){
                        $sr_id = '095002012' ;
                    }
                    break;

                case 'wrong nas port':
                case 'wrong profile':
                    $sr_id = '101003021' ;
                    $sr_name = 'Tech Concession Wrong Matrix or wrong profile';
                    if($is_telephonet){
                        $sr_id = '095002015' ;
                    }
                    break;

                case 'high attenuation':
                    $sr_id = '101003033' ;
                    $sr_name = 'High Attenuation';
                    break;

                case 'option pack':
                    $sr_id = '101003028' ;
                    $sr_name = 'OP Tech Concession';
                    break;

                case 'outage':
                    $sr_id = '101003016' ;
                    $sr_name = 'Tech Concession Global problem or outage';
                     if($is_telephonet){
                        $sr_id = '095002010' ;
                    }
                    break;
                default:
                    break;
            }
            $actions[] = [
                'type'       => $type,
                'label'      => 'CLM',
                'sr_type'    => 'TT',
                'sr_id'      => $sr_id,
                'sr_name'    => $sr_name,
                'sla'        => $sla,
                'quota'      => $quota,
                'amount'     => $amount,
                'expireDays' => 30,
                'serviceContent'=> $serviceContent
            ];

        }
        if($responsibleTeam != 'agent on spot' && $tktStillOpen){
             if($type == 'LE'){
                $serviceContent ='has been Asked CLM Team to compensat CST for his '.$ticketType.' ID :  '.$tkt_id.' with a value of '.$amount.' EGP '.$satisfaction.' for the Valid period due for compensation , which is: '.$validDaysText.', Problem solved but ticket still open , and his phone Number is ';
            }else{
                $serviceContent ='has been Asked CLM Team to compensat CST for his '.$ticketType.' ID :  '.$tkt_id.' with a value of '.$quota.' GB '.$satisfaction.' for the Valid period due for compensation , which is: '.$validDaysText.', Problem solved but ticket still open , and his phone Number is ';
            }
            $actions[] = [
                'type'       => $type,
                'label'      => 'ticket still open',
                'sr_type'    => 'SR',
                'sr_id'      => '100034029',
                'sr_name'    => 'Case solved but ticket still open',
                'sla'        => null,
                'quota'      => $quota,
                'amount'     => $amount,
                'expireDays' => 30,
                'serviceContent'=> $serviceContent
                ];
        }


        return $actions;
    }
}
