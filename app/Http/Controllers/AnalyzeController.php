<?php

namespace App\Http\Controllers;

use App\Models\TTS\FilterData;
use App\Models\FindPackage;
use App\Models\TTS\Validation;
use App\Models\TTS\CuruantSupportPool;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AnalyzeController extends Controller
{
    public function create(Request $request)
    {

        $data = $request->input('inputText');
        $orgData = FilterData::FilterData($data);
        $selectedName = $request->input('selectPackage');

        $transferToCount = $this->CountEsclation($orgData);

        $diffFromLastClose = $this->closedDate($orgData);
        $closedfrom = $diffFromLastClose['closedfrom'];
        $mainpackage = FindPackage::getPackageById($selectedName);

        $tkt_id = $request->input('tktID');
        $weMobileValidation = false ;
        $weMobileMessage = '' ;
        $weMobile_Handled_By = null ;
        $weMobile_sr_id = null ;
        $bss_service_code = 1256583058;

        $validationReason = $orgData[0]->reason;
        $problemType = $orgData[0]->ticketTitle;
        $curuantStatus = $orgData[0]->status;
        $packageId = $mainpackage['id'];

        $curuantSupportPool = CuruantSupportPool::processCuruantSupportPool($data,$problemType);
        $validation = Validation::validate($tkt_id, $data , $orgData, $transferToCount, $problemType , $packageId , $curuantSupportPool );

        $totalDuration = $this->culcTheSeconds($validation['totalDuration']);
        $startDate = $validation['startDate'];
        $closeDate = $validation['closeDate'];
        $esclationHistory = $validation['esclationHistory'];
        $slaStatus = $validation['slaStatus'];
        $slaStatus_color = $validation['slaStatus_color'];
        $actionMessage = $validation['actionMessage'];
        $orignalActionMessage = $actionMessage ;
        $DelayMessage = $validation['DelayMessage'];
        $weMobileMessage = $validation['weMobileMessage'];
        $weMobilecompansationQouta = $validation['weMobilecompansationQouta'];
        $weMobilecompansationExpireDays = $validation['weMobilecompansationExpireDays'];
        $transferToCount = $validation['transferToCount'];




        $delayId = $validation['delayId'];
        $reassignId = $validation['reassignId'];
        $reworkId = $validation['reworkId'];
        $accelerationId = $validation['accelerationId'];

        if ($delayId != 'N/A' && $delayId != null && $delayId != '') {
            $delayId = "<a href='https://10.19.44.2/ireport/cases/del_tickets_view.php?editid1=$delayId' target='_blank'>$delayId</a>";
        }else{
            $delayId = null;
        }

        if ($reassignId != 'N/A' && $reassignId != null && $reassignId != '') {
            $reassignId = "<a href='https://10.19.44.2/ireport/cases/del_tickets_view.php?editid1=$reassignId' target='_blank'>$reassignId</a>";
        }else{
            $reassignId = null;
        }

        if ($reworkId != 'N/A' && $reworkId != null && $reworkId != '') {
            $reworkId = "<a href='https://10.19.44.2/ireport/cases/del_tickets_view.php?editid1=$reworkId' target='_blank'>$reworkId</a>";
        }else{
            $reworkId = null;
        }

        if ($accelerationId != 'N/A' && $accelerationId != null && $accelerationId != '') {
            $accelerationId = "<a  href='https://10.19.44.2/ireport/cases/acceleration_team_view.php?editid1=$accelerationId' target='_blank'>$accelerationId</a>";
        }else{
            $accelerationId = null;
        }


        $sla = $validation['sla'];




        if ($validationReason == '' && $validation['slaStatus'] != 'Closed') {
            $validationReason = 'Ticket not closed yet';
        }


        if (empty($mainpackage)) {
            $mainpackage = 'Usage file not included';
        }
        if($curuantSupportPool == null){
            $curuantSupportPool = $validation['curuantSupportPool'];
        } else {
            //$slaStatus = "Solved as per Support Pool";
        }

        if($curuantSupportPool === 'customer 360'){
            $actionMessage = 'inform customer that his case is in progress and he will be notified once system finalize his case as soon as possible.<br><h3>Follow up with CST After 1 Hr</h3>';
            //$slaStatus = '';
            $sla = 'as per bellow';
            $weMobileValidation = false ;
            $weMobileMessage = '<strong  style="color: red;">Not Eligible for WE Mobile Compensation as per Support Pool</strong>';
        } elseif($curuantSupportPool === 'SLS-IVR Automation'){
            $sla = 'as per bellow';
            $weMobileValidation = false ;
            $weMobileMessage = '<strong  style="color: red;">Not Eligible for WE Mobile Compensation as per Support Pool</strong>';
            $actionMessage = 'Handle customer technical problem normally (Logical or physical) according to customer input and automation ticket update.';
        }

        $supportPools = [
            'MCU Call Center',
            'CC-Service Activation',
            'CC-Online Support',
            'Digital Data Chat',
            'Business Technical Support',
            'I Care',
            'CSI'
        ];

        if (in_array($curuantSupportPool, $supportPools) && $validation['slaStatus'] != 'Closed' &&  $validation['outageTKT'] == false ) {
           if($actionMessage == 'in progress . . .'){
                $actionMessage ='';
           }
           if($actionMessage !=''){
                if($validation['optimizationPeriod'] && $slaStatus != 'After SLA'){
                    $actionMessage = '<br><strong  style="color: yellow;">withdraw the Ticket and Inform CST with Update Below :</strong><br>' . $actionMessage ;
                }else{
                    $actionMessage = '<br><strong  style="color: yellow;">withdraw the Ticket and Inform CST with Update Below :</strong><br>' . $actionMessage . '<br><mark>If the Same Problem Re-T.S</mark>' ;
                }
           }else{
                $actionMessage = '<br><strong  style="color: yellow;">withdraw the Ticket and Handle according 3rd Level update If Exist : <br></strong><br>' . $actionMessage ;

           }
            $weMobileValidation = false ;
            $weMobileMessage = '<strong  style="color: red;">Not Eligible for WE Mobile Compensation as per Support Pool</strong>';
            $slaStatus = 'Handle as Per WIKI (According Current Pool)';
            $sla = null;
            $slaStatus_color = 'orange';
        } else {
            if($sla != 'as per bellow' && $curuantSupportPool != 'CC-Follow up' && $curuantSupportPool != 'Second Level Advanced'){
                $actionMessage = $actionMessage . $DelayMessage .'<br> Inform cst SLA ' ;
            }elseif($curuantSupportPool == 'CC-Follow up'){
                $actionMessage = '<strong  style="color: yellow;">Handle according 3rd Level update If Exist :</strong><br>'.$actionMessage ;
            }else{
                $actionMessage = $actionMessage . $DelayMessage;
            }

        }
        if($validation['slaStatus'] != 'Closed' && $sla != null){
            //$slaStatus = $slaStatus .'<strong style="color: yellow;"></br> (' . $sla . ')</strong>';
        }

        if($transferToCount >1 ){
            if($orgData[0]->support_group == 'customer 360' || $orgData[0]->support_group == 'SLS-IVR Automation'){
                $transferToCount --;
            }
        }
        if($curuantSupportPool == 'CC-Follow up'){
            $slaStatus = 'Handle as per wiki';
            $slaStatus_color = 'red';
            $weMobileValidation = false ;
            $weMobileMessage = '<strong  style="color: red;">Not Eligible for WE Mobile Compensation as per Support Pool</strong>';

        }


        if($curuantSupportPool == 'SLS-FTTH' && $validation['slaStatus'] != 'Closed'){
            $actionMessage = 'Wait for the SLA Except
                If the latest update was from 3rd level Act according to the update.
                For all 1st level teams, If SLS advice wasn’t covered in any reference you are able to let the customer to wait for SLS team call with Re-escalating the ticket. It won’t be consider as a mistake if you act [correctly] on any advanced advice.
                In case there is an advice from SLS you will act on it';
        }

        if($curuantSupportPool == 'CC-VIP'){
            $actionMessage = 'Handle case normally <br> If customer ask about a visit for a reason out of the normal process Inform Customer that he will receive a call to handle his/her request [with no specific SLA]
            Create a ticket on <a href="http://ireport/special-customer" target="_blank" style="color:#b28df7; text-decoration:underline;">special customer IR</a>
            under VIP type and mention in TTS.';
            $weMobileValidation = false ;
            $weMobileMessage = '<strong  style="color: red;">Not Eligible for WE Mobile Compensation as per Support Pool</strong>';
            $slaStatus = 'Handle as Per WIKI (According Current Pool)';
            $sla = null;
            $slaStatus_color = 'orange';
        }

        if($validation['Waiting_for_IT']){
            $problemType = 'wrong profile on matrix';
        }


       if($problemType != 'logical instability - no multiple logs' || $problemType != 'logical instability - no multiple logs'){
            if($curuantSupportPool == 'CC Second Level Support' && $validation['slaStatus'] != 'Closed' && $validation['SLSupdate'] == '' && $validation['tktvisit'] == false){
            $actionMessage = $orignalActionMessage. "<br><mark><b>Wait for the SLA Except</b></mark></p>
                                <ul>
                                    <li>•&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<mark>If the latest update was from 3rd level Act according to the update.</mark></li>
                                    <li>•&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<mark>For all 1st level teams, If SLS advice wasn't covered in any reference you are able to let the customer wait for SLS team call with Re-escalating the ticket. It won't be considered as a mistake if you act [correctly] on any advanced advice.</mark></li>
                                    <li>•&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<mark>In case there is an advice from SLS you will act on it</mark></li>
                                </ul>";
            $slaStatus = 'According Below';
            }
        }else{
            if($curuantSupportPool == 'CC Second Level Support' && $validation['slaStatus'] != 'Closed' && $validation['SLSupdate'] == '' ){
                $actionMessage .= "<br><mark><b>Wait for the SLA Except</b></mark></p>
                                <ul>
                                    <li>•&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<mark>If the latest update was from 3rd level Act according to the update.</mark></li>
                                    <li>•&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<mark>For all 1st level teams, If SLS advice wasn't covered in any reference you are able to let the customer wait for SLS team call with Re-escalating the ticket. It won't be considered as a mistake if you act [correctly] on any advanced advice.</mark></li>
                                    <li>•&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<mark>In case there is an advice from SLS you will act on it</mark></li>
                                </ul>";
            }
        }

        $MCU_Field_Support_pools = [
            'MCU Field Support',
            'Mansoura MCU Field Support',
            'Alex MCU Field Support'
        ];
        $FollowUp_pools = [
            'CC-Follow up',
            'Pilot-Follow up',
            'Second Level Advanced',
        ];

        if(in_array($curuantSupportPool, $MCU_Field_Support_pools) && $validation['tktvisit'] == false){
            $actionMessage = 'Handle case normally except that you found an update that there is visit assigned to the customer';
            $slaStatus = '-';
        }elseif(in_array($curuantSupportPool, $FollowUp_pools)){
            $actionMessage .= '<br>for more details check <a href="https://sp-wiki.te.eg:5443/Pages/fbb/Common/Tools/TTS.aspx#Currently_Withdrawing_Tts_Ticket_From_These_Teams_Is_Not_Available" target="_blank" style="color:#b28df7; text-decoration:underline;">WIKI</a>';
        }
        if($weMobilecompansationQouta >= 3){
            $weMobileValidation = true ;
            $weMobile_Handled_By = 'FL Agent'  ;
            $weMobile_sr_id = null ;
            //$weMobileMessage = "<br>offer we mobile compensation <strong style='color: red;'>if not added before</strong><br>we mobile wil be <br> MBB Quota : <strong style='color: green;'>".$weMobilecompansationQouta.'</strong> GB <br> Validity :  '.$weMobilecompansationExpireDays.' Days <br> Handled By : FL Agent</strong>';


            if($weMobilecompansationQouta >= 10){
                $weMobile_Handled_By = 'CLM Tier Team'  ;
                $bss_service_code =null;
                $weMobile_sr_id = 101024018 ;
                //$weMobileMessage = "<br>offer we mobile compensation <strong style='color: red;'>if not added before</strong><br> MBB Quota : <strong style='color: green;'>".$weMobilecompansationQouta.'</strong> GB <br> Validity :  '.$weMobilecompansationExpireDays.' Days <br> Handled By : CLM Tier Team <br> SLA : 15 Min';
                //$weMobileMessage .= '<div><button id="openModalButtonGB" style="background-color: rgba(20, 197, 0, 0.486) !important; margin-left: 10px; min-width: 150px;" class="mt-4 text-white p-2 rounded-lg hover:bg-green-700 transition duration-300 ease-in-out transform hover:scale-105">Take an Action</button>';
            }

        }

        if(in_array($curuantSupportPool, $supportPools) && $validation['slaStatus'] != 'Closed' && $validation['reassign']){

            $actionMessage .= '<br><br><span style="background-color: #fffae6; color: #c0392b; padding: 3px 6px; border-radius: 4px; font-weight: bold;">⚠ and create Re-Assign IR if not exist';
            $actionMessage .= ' <a href="https://10.19.44.2/ireport/cases/del_tickets_add.php" target="_blank" style="color: #2980b9; text-decoration: underline; font-weight: bold;">[Create IR]</a></span>';
        }elseif($curuantSupportPool == 'IU Maintenance' && $validation['reassign']){
            $actionMessage .= '<br><br><span style="background-color: #fffae6; color: #c0392b; padding: 3px 6px; border-radius: 4px; font-weight: bold;">⚠ and create Re-Assign IR if not exist';
            $actionMessage .= ' <a href="https://10.19.44.2/ireport/cases/del_tickets_add.php" target="_blank" style="color: #2980b9; text-decoration: underline; font-weight: bold;">[Create IR]</a></span>';
        }
        $wiki = $validation['wiki'] ?? '';
        if($wiki != ''){
            $actionMessage .='<br> WIKI Reference - <a href='.$wiki.' target="_blank" style="color:#b28df7; text-decoration:underline;">Major Fault</a>';
        }
        if($validation['slaStatus'] == 'Closed'){
            $actionMessage = 'Ticket is closed';
            $weMobileValidation = false ;
            $weMobile_Handled_By = null;
            $weMobile_sr_id = null ;
            $weMobileMessage = "<strong style='color: red;'>not eligible for we mobile compensation .. Ticket is closed</strong>";
        }
        if($weMobilecompansationQouta == 0){
            $weMobileValidation = false ;
        }


        if($curuantSupportPool == 'Second Level Advanced' && $curuantStatus == 'own the case'){
            $actionMessage = '<strong><mark>Refer Back to Available Team Leader to withdraw this ticket, then follow the normal process.</mark></strong><br>' ;
            $actionMessage .= '<br>for more details check <a href="https://sp-wiki.te.eg:5443/Pages/fbb/Common/Tools/TTS.aspx#Other_Teams" target="_blank" style="color:#b28df7; text-decoration:underline;">WIKI</a>';

        }
        switch ($problemType) {
            case 'browsing - certain sites':
            case 'browsing':
                $problemType = 'Browsing';
                break;

            case 'logical instability - no multiple logs':
            case 'logical instability':
                $problemType = 'Logical Instability';
                break;
            case 'physical instability':
                $problemType = 'Physical Instability';
                break;
            case 'bad line quality':
                $problemType = 'Bad Line Quality';
                break;
            case 'blq':
                $problemType = 'Bad Line Quality';
                break;
            case 'slowness':
            case 'speed':
                $problemType = 'Slowness';
                break;
            case 'need optimization':
                $problemType = 'Need Optimization';
                break;
            case 'voice down':
                $problemType = 'Voice Down';
                break;
            case 'data down':
                $problemType = 'Data Down';
                break;
            case 'data and voice down':
                $problemType = 'Data and Voice Down';
                break;
            case 'wcap':
                $problemType = 'WCAP';
                break;
            case 'wrong profile':
                $problemType = 'Wrong Profile';
                break;
            case 'unable to obtain ip':
                $problemType = 'Unable to Obtain IP';
                break;
            case 'wrong nas port':
                $problemType = 'Wrong NAS Port';
                break;
            case 'voice overlapping':
                $problemType = 'Voice Overlapping';
                break;

            default:
                break;
        }


        $weMobile = [
            'valid' => $weMobileValidation,
            'message' => $weMobileMessage,
            'quota' => $weMobilecompansationQouta,
            'expireDays' => $weMobilecompansationExpireDays,
            'Handled_By' => $weMobile_Handled_By,
            'sr_id' => $weMobile_sr_id,
            'sr_name' => "We Mobile Adjustment",
            'bss_service_code' => $bss_service_code,
            'sla' => '15 Minutes',
        ];

        $available_actions = [
            [
                'type' => 'We Mobile Aproval',
                'lable' => 'TT for CLM Team',
                'sr_type' => 'TT',
                'sr_id' => '101024018',
                'sr_name' => 'We Mobile Adjustment',
                'sla' => '15 Minutes',
                'quota' => $weMobilecompansationQouta,
                'amount' => null,
                'expireDays' => $weMobilecompansationExpireDays,
            ]
        ];

        $accelerationId = null;
        return response()->json([

            'message' => 'Data received successfully!',
            'problemType' => $problemType,
            'curuantSupportPool' => $curuantSupportPool,
            'escalationtimes' => $transferToCount,
            'closedDate' => $closedfrom,
            'totalDuration' => "{$totalDuration['days']} days, {$totalDuration['hr']} hours, and {$totalDuration['min']} minutes",
            'mainpackage' => $mainpackage,
            'startDate' => $startDate,
            'closeDate' => $closeDate,
            'esclationHistory' => $esclationHistory,
            'sla' => $sla,
            'slaStatus' => $slaStatus,
            'slaStatus_color' => $slaStatus_color,
            'actionMessage' => $actionMessage,
            'delayId' => $delayId,
            'reworkId' => $reworkId,
            'reassignId' => $reassignId,
            'accelerationId' => $accelerationId,
            'weMobile' => $weMobile,
            'curuantStatus' => $curuantStatus,
            'available_actions' => $available_actions,
        ]);
    }

    private function CountEsclation($orgData)
    {
        return count($orgData);
    }

    private function closedDate($orgData)
    {
        $now = Carbon::now();
        $pos = 0;
        $date = Carbon::parse($orgData[$pos]->ticket_close_time);
        $duration = Carbon::parse($orgData[$pos]->ticket_close_time);
        $diffFromLastClose_to_now = $duration->diffInMinutes($now);

        if ($pos >= 0 || $diffFromLastClose_to_now < 1) {
            $diffFromLastClose = $date->diffForHumans($now);

            if ($diffFromLastClose == '0 seconds before'|| $diffFromLastClose_to_now < 1) {
                $diffFromLastClose = 'Ticket not closed yet';
            }
            $diffFromLastCloseinM = $date->diffInMonths($now);
        }

        return ['closedfrom' => $diffFromLastClose, 'closedinM' => $diffFromLastCloseinM];
    }





    private function culcTheSeconds($seconds)
    {
        $totalDays = floor($seconds / 86400); // Number of days (86400 seconds in a day)
        $totalHours = floor(($seconds % 86400) / 3600); // Remaining hours
        $totalMinutes = floor(($seconds % 3600) / 60); // Remaining minutes
        $totalDurationDays = $seconds / 86400;

        return ['days' => $totalDays, 'hr' => $totalHours, 'min' => $totalMinutes, 'totalDays' => $totalDurationDays];
    }
}
