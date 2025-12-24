<?php

namespace App\Models\OM;

use Carbon\Carbon;

class OmFvValidation
{
    public static function Omvalidate($orgData,$problemSolved,$ActivityName ,$errorName,$ActivityCreateTime)
    {

        if (empty($orgData)) {
            return ['validation' => false, 'reason' => 'No data available'];
        }
        $slaStatus = "";
        $sla = 432000;
        $slaStatus_color = "green";
        $actionMessage = "";
        if($ActivityName == null){
            $ActivityName = $orgData->currentActivity ;

        }
        if($errorName == null){
            $errorName = $orgData->errorName ;
        }
        if($ActivityCreateTime == null){
            $ActivityCreateTime = $orgData->currentActivityCreateTime ;
        }
        $ActivityCreateTime = Carbon::parse($ActivityCreateTime);
        $now = Carbon::now();
        $humanReadableDiff = $ActivityCreateTime->diffForHumans();

        $diffInSeconds = $ActivityCreateTime->diffInSeconds($now);

        $ActivityCreateTime = $ActivityCreateTime->format('Y-m-d h:i A');

        $ActivityCreateTime = $ActivityCreateTime . " From: " . $humanReadableDiff ;




        $ActivityName = trim($ActivityName);
        $errorName = trim($errorName);
        $orderSLAStatus = $orgData->orderSLAStatus ;
        $product = $orgData->product ;
        $areaCode = $orgData->areaCode ;
        $orderStatus = $orgData->orderStatus ;
        if($product == 'FV FTTH'){
            $sla = 864000;
            if($areaCode == 2 ||$areaCode == 3){
                $sla = 604800 ;
            }
        }
        if($orderSLAStatus== "Normal"){
            $orderSLA = '<strong style="color: Green;">within SLA</strong>';
            if($sla < $diffInSeconds){
                $orderSLA = '<strong style="color: red;">After SLA</strong>';
            }
        }
        if($orderStatus != 'Executing'){
            $orderSLA = $orderStatus;
        }

        //$orderSLA = $diffInSeconds;


        $ActivitySLA = "";
        $withinSR = '';
        $delaySR = '';
        $delayTT = '';
        $AutomaticTT = '';
        $CustomerProblem = [
            'Delegation required',
            'Already Subscribed',
            'Final Disconnection',
            'ISDN',
            'Landline bill not paid',
            'Office Sales of Service',
            'PBX',
            'Subscribed with another provider',
            'Wrong Number',
            'Final disconnected',
            'Not tested yet',
            'Out of Service',
            'Damaged Port',
            'DID',
            'DOD',
            'Need Address Modification',
            'Refused the visit',
            'Unreachable',
        ];
        $LineProblem = [
            'Connection Cable',
            'Fiber',
            'No copper',
            'Pergain',
            'Wireless',
            'UNO',
        ];
        $TEProblem = [
            'No Data',
            'no data in both system integration',
            'No Financial Data',
            'No Technical Data',
        ];
        $ActivationProblems= [
            'this frame not exist in this pop or used',
            'Field Operation',
            'Customer number already exist',
            'Port damaged or Fixed',
            'Port Fixed',
            'Port damaged',
            'Customer no is not validated',
            'Unexpected database error occurred',
            'Customer not found',
        ];

        $HomeVisitproblem= [
            ["name" => "Unreachable", "sla" => "SLA:24 Hour"],
            ["name" => "Payment", "sla" => "SLA:24 Hour"],
            ["name" => "Cancellation", "sla" => "SLA:24 Hour"],
            ["name" => "Outage (Global problem)", "sla" => "SLA:24 Hour"],
            ["name" => "Voice bills", "sla" => "SLA:24 Hour"],
            ["name" => "change offer", "sla" => "SLA:24 Hour"],
            ["name" => "Data down", "sla" => "SLA:24 Hour"],
            ["name" => "Voice down", "sla" => "SLA:24 Hour"],
            ["name" => "Data and voice down", "sla" => "SLA:24 Hour"],
            ["name" => "BLQ", "sla" => "SLA:24 Hour"],
            ["name" => "Wrong card and port", "sla" => "SLA:24 Hour"],
            ["name" => "Unable to obtain IP", "sla" => "SLA:24 Minutes"],
            ["name" => "physical instability", "sla" => "SLA:24 Hour"],
            ["name" => "cross connection", "sla" => "SLA:24 Hour"],
            ["name" => "voice overlapping", "sla" => "SLA:24 Hour"],
            ["name" => "logical instability", "sla" => "SLA:24 Hour"],
            ["name" => "browsing", "sla" => "SLA:30 Minutes"]
        ];
        if (
            in_array($errorName, $CustomerProblem) ||
            in_array($errorName, $LineProblem) ||
            in_array($errorName, $TEProblem) ||
            in_array($errorName, $ActivationProblems) ||
            array_search($errorName, array_column($HomeVisitproblem, "name")) !== false
        ) {
        }else {
            //$errorName = null;
        }

        $delayTT = '';

        if($errorName != null){
            $AutomaticTT = 'Automatic TT - FBB->WO Problem->'.$errorName ;
        }


        if($ActivityName == 'WO Request'){
            $actionMessage = "طلب أمر الشغل للبدأ فى اجراءات التركيب وفى حالة وجود أى مشكلة خلال المرحلة دى السيستم هيعمل TT أتوماتيك بـ SLA مختلفة حسب نوع المشكلة.";
            $withinSR = 'FBB Non Tech follow up--Installation Order--WO Request Within SLA';
            $delaySR = 'FBB Non Tech follow up--Installation Order--WO Request Within SLA';
            $delayTT = 'Broadband>Non Technical Request>Delay>Installation>Pending TE';
            $ActivitySLA = "1WD";
            $AutomaticTT = '';

        }elseif ($ActivityName == 'WO Problem'){
            if(in_array($errorName, $CustomerProblem) ){
                $actionMessage = 'For the below automatic TTs "Require an action from customer side" and customer called us firstly telling us he solved the problem, CCA will use the forward button and assign the TT to his user to close the TT "Solved';
                $withinSR = 'FBB Non Tech follow up>Installation Order>Customer Problem informed';
                $delaySR = $withinSR;
                $ActivitySLA = '';

            }elseif(in_array($errorName, $LineProblem)){
                $actionMessage = ' ';
                $withinSR = 'FBB Non Tech follow up--Installation Order--WO-Line Problem Within SLA';
                $delaySR = 'FBB Non Tech follow up--Installation Order--WO-Line Problem After SLA';
                $ActivitySLA = "15WDs";
                $orderSLA = $ActivitySLA;

            }elseif(in_array($errorName, $TEProblem)){
                if($problemSolved == 0){
                    $actionMessage = ' ';
                    $withinSR = 'FBB Non Tech follow up->Installation Order->WO-TE Problem Within SLA';
                    $delaySR = 'FBB Non Tech follow up--Installation Order--WO-TE Problem After SLA';
                    $ActivitySLA = "7WDs";
                    $orderSLA = $ActivitySLA;
                }else{
                    $actionMessage = 'Add Midway comment in automated TT that problem solved+Create SR + adding comment problem solved from customer side in pervious SR';
                    $withinSR = '<br>FBB Non Tech follow up--Installation Order--TE Problem Solved<br>cst follow up : FBB Non Tech follow up--Installation Order--TE Problem Solved within SLA';
                    $delaySR = '<br>FBB Non Tech follow up--Installation Order--TE Problem Solved After SLA';
                    $ActivitySLA = "1 WDs";
                }
            }
        }elseif ($ActivityName == 'ADSL Portal Monitor'){
            $withinSR = 'In case order stuck in any automatic status more than 1 H (Executing without a problem and not abnormal), CCA should create the following TT and we will stick to individual cases SLA';
            $actionMessage = $actionMessage . '<br> ADSL Portal Monitor: وهنا بيبقى تانى محاولة من السيستم لطلب أمر الشغل بعد ما المشكلة تتحل والسيستم بينقل على الـ WO Request';
            $delaySR = '';
            $ActivitySLA = "";
            $AutomaticTT = '';

        }elseif ($ActivityName == 'Force Close TT'){
            $actionMessage = 'دي معناها أن الـ CCA عمل Force Close للـ WO Problem TT بعد ما المشكلة اتحلت والـOrder هيبدأ من عند الـ  WO Request مرة أخرى';
            $withinSR = '';
            $delaySR = $withinSR;
            $ActivitySLA = "";
            $AutomaticTT = '';

        }elseif ($ActivityName == 'Check Combo'){
            $withinSR = 'FBB Non Tech follow up --> Installation Order --> Check Combo Within SLA';
            $delaySR = 'FBB Non Tech follow up --> Installation Order --> Check Combo After SLA';
            $actionMessage = "  لو البورت كومبو بيتحول على طول لـ Network Activation";
            $AutomaticTT = '';

        }elseif ($ActivityName == 'Reserve Activation'){
            $actionMessage = "In case order stuck in any automatic status more than 1 H (Executing without a problem and not abnormal), CCA should create the following TT and we will stick to individual cases SLA";
            $withinSR = '';
            $delaySR = '';
            $ActivitySLA = "";
            $AutomaticTT = '';
            if($errorName == 'No Free Ports'){
                $actionMessage = '';
                $withinSR = 'FBB Non Tech follow up --> Installation Order --> Reserve activation no estimated time';
                $delaySR = $withinSR;
                $ActivitySLA = "No Estimated time";
                $orderSLA = $ActivitySLA;
            }elseif ($errorName != null){
                $actionMessage = 'CCA will Follow Individual Case';
                $withinSR = '';
                $delaySR = '';
                $ActivitySLA = "";
            }
            $AutomaticTT = '';

        }elseif ($ActivityName == 'Splitting' || $ActivityName == 'ReSplitting'||$ActivityName == 'Splitting Configuration' || $ActivityName == 'ReSplitting Configuration' || $ActivityName == 'Splitting problem'){
            $actionMessage = '';
            $withinSR = 'FBB Non Tech follow up --> Installation Order --> Splitting Within SLA normal cycle';
            $delaySR = 'FBB Non Tech follow up --> Installation Order --> Splitting After SLA';
            $delayTT = 'Broadband>Non Technical Request>Delay>Installation>Pending Splitting';
            $AutomaticTT = '';
            if(in_array($errorName, $CustomerProblem)){
                $actionMessage = "If cst asks only: <br>In case the problem was 'CST Problem' and hasn't been solved within (28 days for FTTH & 30 days for XDSL new order), we will cancel the order.";
                $withinSR = 'FBB Non Tech follow up--Installation Order--Customer Problem informed';
                $delaySR = '';
                if($errorName == 'Damaged Port'){
                    $ActivitySLA = "2 WD";
                }

            }elseif(in_array($errorName, $LineProblem)){
                $actionMessage = '';
                $withinSR = 'FBB Non Tech follow up --> Installation Order --> Splitting-Line Problem Within SLA';
                $delaySR = 'FBB Non Tech follow up --> Installation Order --> Splitting-Line Problem After SLA';
                $ActivitySLA = "15 WD";
                $delayTT = '';
            }elseif(in_array($errorName, $TEProblem)){
                $withinSR = 'Fixed voice SR follow up>Installation Visit>Within SLA';
                $delaySR = 'Fixed voice SR follow up>Installation Visit>Violated SLA';
                $ActivitySLA = "5 WDs";
                $delayTT = '';
            }
            if($errorName == 'No Free Ports'){
                $withinSR = 'FBB Non Tech follow up--Installation Order--Port Problem';
                $delaySR = '';
                $delayTT = '';
                $ActivitySLA = "No Estimated time";
                $orderSLA = $ActivitySLA;
            }elseif($errorName == 'Outage (Global Problem)' || $errorName == 'Outage'||$errorName == 'Global Problem'){
                $withinSR = 'FBB Non-Tech Inquiry --> Installation Order --> Outage ( Global problem )';
                $delaySR = '';
                $delayTT = '';
                $ActivitySLA = "No Estimated time";
                $orderSLA = $ActivitySLA;
            }

        }elseif($ActivityName == 'Voice Provisioning Configuration' || $ActivityName == 'Network Activation'){
            if($errorName == 'Voice Provisioning Configuration'){
                $actionMessage = "It's an automatic activity before Network Activation";
                $withinSR = 'FBB Non Tech follow up --> Installation Order --> Voice provisioning within SLA';
                $delaySR = 'FBB Non Tech follow up --> Installation Order --> Voice provisioning After SLA';

            }elseif($errorName == 'Network Activation Configuration'){
                $withinSR = 'FBB Non Tech follow up --> Installation Order --> Network Activation Within SLA';
                $delaySR = 'FBB Non Tech follow up --> Installation Order --> Network Activation After SLA';

            }elseif(in_array($errorName, $ActivationProblems)){
                $ActivitySLA = "2 WDs";
            }

        }elseif($ActivityName == 'IM Update'){
            $withinSR = 'FBB Non Tech follow up --> Installation Order --> IM Update Within SLA';
            $delaySR = 'FBB Non Tech follow up --> Installation Order --> IM Update After SLA';

        }elseif($ActivityName == 'Check delivery method'){
            $actionMessage = 'Checking CPE delivery method (delivery- visit)';
            if($errorName =="Device Delivery"){
                $withinSR = '"FBB Non-Tech follow up --> CPE Delivery --> Within SLA<br>ممكن نعمل query عالـ SR دى عشان نتابع أي تعليق او اخر تحديث للإدارة المسئولة لتوصيل الراوتر.<br>FBB Non Tech follow up --> Outlets and logistics --> CPE Delivery"';
                $delaySR = '"Incase CST asked to follow up with logistics team:
                            <br>FBB Non-tech Follow up --> CPE Delivery --> Need Follow Up
                            <br>SLA:1 WD
                            <br>Logistic team added any comment requires follow up with CST for Ex. (we can’t reach the customer)  in below SR:
                            <br>FBB Non Tech follow up --> Outlets and logistics --> CPE Delivery
                            <br>after checking Delivery Process If CST has CPE delivery order and there is no AWB Date in his order with no SR from logistics team within 1 WD
                            <br>FBB Non Technical Complaint --> CPE --> Delay in CPE Delivery
                            <br>In Case of Delay on TT Or CPE with Fedex and exceed SLA Create SR:
                            <br>FBB Non-Tech follow up --> Delay CPE Delivery --> After SLA
                            <br>Order status+علي حسب المنطقة التابع لها العميل:SLA"';
                $ActivitySLA = "علي حسب منطقة العميل";
            }
        }elseif($ActivityName == 'Home Visit'||$ActivityName == 'Survey'||$ActivityName == 'Survey Problem'||$ActivityName == 'Home visit problem'){
            $foundKey = array_search($errorName, array_column($HomeVisitproblem, "name"));
            if ($foundKey !== false) {
                $ActivitySLA = $HomeVisitproblem[$foundKey]["sla"];
            }

            if($errorName == 'Home Visit dispatch'){
                $actionMessage = 'Fee amount regarding the Installation visit for ADSL/ VDSL: 85 LE with VAT.';
                $withinSR = 'FBB Non Tech follow up --> Installation Order --> Home Visit Within SLA';
                $delaySR = 'FBB Non Tech follow up --> Installation Order --> Home Visit After SLA';
                $ActivitySLA = "Cairo and Alex: 1 day<br>Other cities: 5 WDs";
            }
        }elseif($ActivityName == 'Check SN'){
            $actionMessage = 'OM check from the CRM the CPE serial number which will be auto action';
            $withinSR = 'FBB Non Tech follow up --> Installation Order --> Check SN Within SLA';
            $delaySR = 'FBB Non Tech follow up --> Installation Order --> Check SN After SLA';
        }elseif($ActivityName == 'Call Back TO CRM' || $ActivityName == 'Call back to CRM'){
            $withinSR = 'FBB Non Tech follow up --> Installation Order --> Call back to CRM';
            $AutomaticTT = '';
            $actionMessage = 'The Order was completed successfully.';
        }elseif($ActivityName == 'MSAN Network Activation' ){
            $withinSR = '';
            $AutomaticTT = '';
            $actionMessage = 'In this activity, OM system will invoke SA with service number .';
        }elseif($ActivityName == 'Installation Visit Dispatch' ){
            $withinSR = 'Fixed voice SR follow up>Installation Visit>Within SLA';
            $delaySR = 'Fixed voice SR follow up>Installation Visit>Violated SLA';
            $ActivitySLA = "5 WDs";
            $delayTT = 'Fixed Voice Complaint>Escalation>Installation Exchange Complaint <br> and SLA for TT : 48 WH';
            if($errorName == 'Outage "Tool Down"'){
                $actionMessage = 'follow normal process for SLA and if Passed SLA CCA should act according normal violated TTs process.';
            }elseif($errorName == 'No Free Active Port'){
                $withinSR = 'Fixed voice SR follow up>Installation Visit Dispatch>No Free Active Port';
                $delaySR = '';
                $ActivitySLA = "No Estimated time";
                $delayTT = '';
            }elseif($errorName == 'FV Installation - Voice Installation Failed'){
                $actionMessage = 'we will create Delay ticket for installation Visit.';
            }
        }elseif($ActivityName == 'Installation Visit problems' ){
            if(in_array($errorName, $CustomerProblem)){

            }
        }

        if($problemSolved == 1){
            if(in_array($errorName, $CustomerProblem) ||in_array($errorName, $LineProblem)){
            $actionMessage = 'Add Midway comment in automated TT that problem solved+Create SR + adding comment problem solved from customer side in pervious SR';
            $withinSR = '<br>FBB Non Tech follow up--Installation Order--TE Problem Solved<br>cst follow up : FBB Non Tech follow up--Installation Order--TE Problem Solved within SLA';
            $delaySR = '<br>FBB Non Tech follow up--Installation Order--TE Problem Solved After SLA';
            $ActivitySLA = "1 WDs";
            }elseif(in_array($errorName, $TEProblem)){
                $actionMessage = 'Add Midway comment in automated TT that problem solved+Create SR + adding comment problem solved from customer side in pervious SR';
                $withinSR = '<br>FBB Non Tech follow up--Installation Order--TE Problem Solved<br>cst follow up : FBB Non Tech follow up--Installation Order--TE Problem Solved within SLA';
                $delaySR = '<br>FBB Non Tech follow up--Installation Order--TE Problem Solved After SLA';
                $ActivitySLA = "1 WDs";
            }
        }





        return [

            'ActivityName' => $ActivityName,
            'errorName' => $errorName,
            'ActivityCreateTime' => $ActivityCreateTime,
            'slaStatus' => $slaStatus,
            'withinSR' => $withinSR,
            'delaySR' => $delaySR,
            'delayTT' => $delayTT,
            'sla' => $sla,
            'ActivitySLA' => $ActivitySLA,
            'orderSLA' => $orderSLA,
            'slaStatus_color' => $slaStatus_color,
            'actionMessage' => $actionMessage,
            'AutomaticTT' => $AutomaticTT,
            'orderSLAStatus' => $orderSLAStatus,

        ];
    }
}
