<?php

namespace App\Models\OM;

use Carbon\Carbon;
use Carbon\CarbonInterval;

class FTTH_Migration
{
    public static function Omvalidate($orgData,$problemSolved,$ActivityName ,$errorName,$ActivityCreateTime)
    {
        $test = '';
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





        $ActivityName =  strtolower(trim($ActivityName));
        $errorName =  strtolower(trim($errorName));
        $orderSLAStatus =  strtolower($orgData->orderSLAStatus) ;
        //$product = strtolower( $orgData->product) ;
        $areaCode = $orgData->areaCode ;
        $orderStatus =  strtolower($orgData->orderStatus) ;


        $sla = 864000; //10 Days
        if($areaCode == 2 ||$areaCode == 3){
            $sla = 604800 ; // 7 Days
        }
        if($orderSLAStatus== "normal"){
            $orderSLA = '<strong style="color: '.$slaStatus_color.';">within SLA</strong>';
            if($sla < $diffInSeconds){
                $slaStatus_color = "red";
                $orderSLA = '<strong style="color: '.$slaStatus_color.';">After SLA</strong>';
            }
        }

        $humanReadableDiff = '<strong style="color: '.$slaStatus_color.';"> ( From '.$humanReadableDiff.' ) </strong>';

        if($orderStatus != 'executing'){
            $orderSLA = $orderStatus;
        }

        //$orderSLA = $diffInSeconds;

        $description = '';
        $ActivitySLA = null;
        $withinSR_ID = null ;
        $withinSR = null ;
        $delaySR = null ;
        $delaySR_ID = null ;
        $delayTT = null ;
        $delayTT_ID = null ;
        $AutomaticTT = null;
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
            ["name" => "unreachable", "sla" => 86400],
            ["name" => "payment", "sla" => 86400],
            ["name" => "cancellation", "sla" => 86400],
            ["name" => "outage (global problem)", "sla" => 86400],
            ["name" => "voice bills", "sla" => 86400],
            ["name" => "change offer", "sla" => 86400],
            ["name" => "data down", "sla" => 86400],
            ["name" => "voice down", "sla" => 86400],
            ["name" => "aata and voice down", "sla" => 86400],
            ["name" => "blq", "sla" => 86400],
            ["name" => "wrong card and port", "sla" => 86400],
            ["name" => "unable to obtain ip", "sla" => 1440],
            ["name" => "physical instability", "sla" => 86400],
            ["name" => "cross connection", "sla" => 86400],
            ["name" => "voice overlapping", "sla" => 86400],
            ["name" => "logical instability", "sla" => 86400],
            ["name" => "browsing", "sla" => 1800]
        ];
        if (
            in_array($errorName, $LineProblem) ||
            in_array($errorName, $TEProblem) ||
            in_array($errorName, $ActivationProblems) ||
            array_search($errorName, array_column($HomeVisitproblem, "name")) !== false
        ) {
        }else {
            $errorName = null;
        }
        /*

        foreach ($orgData as $property => $value) {
            $test .= '<p><strong>' . htmlspecialchars($property) . ':</strong> '
                . htmlspecialchars((string)$value) . '</p>';
        }
        $test = $orgData->product ;
        */

        $surveyNames =['survey' , 'resurvey' , 'survey problem','resurvey problem'];

        if( in_array($ActivityName, $surveyNames)){

            $ActivityName = 'survey' ;
        }elseif($ActivityName == 'unreached survey'){
            $errorName = 'unreachable' ;
        }
        //$test = 'mina' ;
        switch ($ActivityName) {
            case 'customer readiness notification':
                $description = 'المرحلة دي العميل بيوصلة رسالة sms تعرفة ان خطة هيتحول لـ فايبر';
                $actionMessage = " هنبلغه يجهز الـمسار (routing ) و بعد كدا نقفل الـ TT بـ Success  عشان الـorder يتنقل لـ survey activity ";
                $actionMessage .= "<br><mark>في حالة عدم غلق الـ TT هتتقفل تلقائيا بعد 7 ايام و بيتم تكملة باقي المراحل بشكل طبيعي</mark>";
                $AutomaticTT = "Automatic TT-FV FTTH->Technology Migration Customer Readiness->Notification";
                $withinSR = "Readiness - CST Informed" ;
                $withinSR_ID = 103030026 ;
                $delaySR = null ;
                $delaySR_ID = null ;
                $delayTT = null ;
                $delayTT_ID = null ;
                $ActivitySLA = 604800;
                break;
            case 'survey':
                $ActivitySLA = 432000;

                if($errorName == null || $errorName ==""){
                    $description = "فى المرحلة دى بيوصل للعميل فنى من الـ FO-Fiber للتأكد من جاهزية المكان لدخول الخدمة وفى نفس الزيارة بيتم مد كابل الفايبر للعميل من البوكس لحد باب الشقة";
                    $actionMessage = "";
                    $AutomaticTT = null;
                    $withinSR = "Survey within SLA" ;
                    $withinSR_ID = 103030001 ;
                    $delaySR = "Survey After SLA" ;
                    $delaySR_ID = 103030002 ;
                    $delayTT = null ;
                    $delayTT_ID = null ;
                }else{
                    switch ($errorName){
                        case 'get ftth path api failed':
                            $description = "it will be considered as no free port issue";
                            $actionMessage = "فى حاله سؤال العميل عن سبب التأخير في تقديم الخدمة يجب إخبار العميل : بعدم وجود اماكن متاحة بالسنترال ويتم العمل على توفير مكان للتركيب وسيتم المتابعة بمجرد الانتهاء من هذه الاجراءات    ";
                            $AutomaticTT = null;
                            $withinSR = "Survey no estimated time" ;
                            $withinSR_ID = 103025086 ;
                            $delaySR = null ;
                            $delaySR_ID = null ;
                            $delayTT = null ;
                            $delayTT_ID = null ;
                            $ActivitySLA = 'No estimated time';
                            break;

                        case 'unreachable':
                            $description = "هنا الفني حاول يتواصل مع العميل و معرفناش نوصلة ";
                            $actionMessage = "بنتأكد ان العميل بقي متاح اننا نتواصل معاه و بنقفل الـ TT";
                            $AutomaticTT = null;
                            $withinSR = "Unreached Survey within SLA" ;
                            $withinSR_ID = 103030032 ;
                            $delaySR = "Unreached Survey After SLA" ;
                            $delaySR_ID = 103030033 ;

                        default:

                    }


                }
                break;
            case 'survey grace period':
                $description = "في المرحلة دي العميل بيكون عندة مشكلة منعت اكتمال مرحلة الـ survey";
                $actionMessage = "ببلغ العميل بمشكلته و لو كان العميل حل المشكلة بقفل الـ TT";
                $AutomaticTT = null;
                $withinSR = "Survey Grace Period - CST Informed" ;
                $withinSR_ID = 103030027 ;
                $delaySR = null ;
                $delaySR_ID = null ;
                $delayTT = null ;
                $delayTT_ID = null ;
                $ActivitySLA = 432000;
                break;
            case 'survey de-activation':
                $description = "•	الـ order بيتنقل على الـ Activity دى في حالة إن الـ Activation team لم يستطيع الوصول للعميل في الـ Survey grace period activity و هيتم ارسال SMS للعميل لابلاغه إن الـ MSAN service سيتم إلغائها و يجب الاتصال بـ 111 أو التوجه للفرع لاستكمال خطوات الـ FTTH migration.     ";
                $actionMessage = "ببلغ العميل بمشكلته و لو كان العميل حل المشكلة بقفل الـ TT";
                $actionMessage .= "<mark>•	ملحوظة :غير مسموح اننا نقفل الـ TT بـ not Solved Reason . </mark>";
                $AutomaticTT = "Automatic TT-FV FTTH->NON Technical Problem Migration->Survey De-activation";
                $withinSR = "MSAN Deactivation  - CST Informed " ;
                $withinSR_ID = 103030029 ;
                $delaySR = null ;
                $delaySR_ID = null ;
                $delayTT = null ;
                $delayTT_ID = null ;
                $ActivitySLA = 432000;
                break;
            case 'unreached survey':
                $description = "هنا الفني حاول يتواصل مع العميل و معرفناش نوصلة ";

                //$description = "فى المرحلة دى بيوصل للعميل فنى من الـ FO-Fiber للتأكد من جاهزية المكان لدخول الخدمة وفى نفس الزيارة بيتم مد كابل الفايبر للعميل من البوكس لحد باب الشقة";
                $actionMessage = "بنتأكد ان العميل بقي متاح اننا نتواصل معاه و بنقفل الـ TT";
                $AutomaticTT = null;
                $withinSR = "Unreached Survey within SLA" ;
                $withinSR_ID = 103030032 ;
                $delaySR = "Unreached Survey After SLA" ;
                $delaySR_ID = 103030033 ;
                $ActivitySLA = 432000;
                break;

            case 'C':
                // تنفيذ كود C
                break;
            default:
                // حالة افتراضية
        }





        if($problemSolved == 1){
            if(in_array($errorName, $CustomerProblem) ||in_array($errorName, $LineProblem)){
            $actionMessage = 'Add Midway comment in automated TT that problem solved+Create SR + adding comment problem solved from customer side in pervious SR';
            $withinSR = '<br>FBB Non Tech follow up-FTTH Order Installation-TE Problem Solved<br>cst follow up : FBB Non Tech follow up-FTTH Order Installation-TE Problem Solved within SLA';
            $delaySR = '<br>FBB Non Tech follow up-FTTH Order Installation-TE Problem Solved After SLA';
            $ActivitySLA = "1 WDs";
            }elseif(in_array($errorName, $TEProblem)){
                $actionMessage = 'Add Midway comment in automated TT that problem solved+Create SR + adding comment problem solved from customer side in pervious SR';
                $withinSR = '<br>FBB Non Tech follow up-FTTH Order Installation-TE Problem Solved<br>cst follow up : FBB Non Tech follow up-FTTH Order Installation-TE Problem Solved within SLA';
                $delaySR = '<br>FBB Non Tech follow up-FTTH Order Installation-TE Problem Solved After SLA';
                $ActivitySLA = "1 WDs";
            }
        }
        if($sla < $diffInSeconds){
            $withinSR_ID = null ;
            $withinSR = null ;
        }else{
            $delaySR_ID = null ;
            $delaySR = null ;
        }

        $description = '<strong style="color: yellow ;" >'.$description.'</strong>';
        return [

            'ActivityName' => $ActivityName,
            'errorName' => $errorName,
            'ActivityCreateTime' => $ActivityCreateTime,
            'humanReadableDiff' => $humanReadableDiff,
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
            'description' => $description,
            'withinSR_ID' => $withinSR_ID,
            'delaySR_ID' => $delaySR_ID,
            'delayTT_ID' => $delayTT_ID,
        ];
    }
}
