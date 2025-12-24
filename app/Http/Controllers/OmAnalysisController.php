<?php

namespace App\Http\Controllers;


use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\OM\OmFilterData;
use App\Models\OM\Omvalidation;
use App\Models\OM\OmFvValidation;
use App\Models\OM\OmFtthValidation;
use App\Models\OM\FTTH_Migration;

class OmAnalysisController extends Controller
{
    public function create(Request $request)
    {
        $data = $request->input('inputText');
        $problemSolved = $request->input('proplemSolved');
        $orgData = OmFilterData::FilterData($data);
        $ActivityName = $request->input('ActivityName');
        $errorName = $request->input('ErrorName');
        $ActivityCreateTime = $request->input('CreateTime');
        $datatest = $orgData;

        if($orgData->product == 'FV MSAN'){
            $validation = OmFvValidation::Omvalidate($orgData,$problemSolved ,$ActivityName ,$errorName,$ActivityCreateTime);
        }elseif($orgData->product == 'FV FTTH' || $orgData->product == 'FV FTTH Migration OM Only' || $orgData->product == 'FTTH'){
            $validation = FTTH_Migration::Omvalidate($orgData,$problemSolved ,$ActivityName ,$errorName,$ActivityCreateTime);
        }elseif($orgData->product == 'FV FTTH Migration FV Only'){
            //$validation = FTTH_Migration::Omvalidate($orgData,$problemSolved ,$ActivityName ,$errorName,$ActivityCreateTime);
        }else{
            $validation = Omvalidation::Omvalidate($orgData,$problemSolved ,$ActivityName ,$errorName,$ActivityCreateTime);
        }



        $ActivityName = $validation['ActivityName'];

        $ActivityCreateTime = $validation['ActivityCreateTime'];
        $humanReadableDiff = $validation['humanReadableDiff'];
        $withinSR = $validation['withinSR'];
        $delaySR = $validation['delaySR'];
        $delayTT = $validation['delayTT'];
        $ActivitySLA = $validation['ActivitySLA'];
        $orderSLA = $validation['orderSLA'] ;
        $actionMessage = $validation['actionMessage'] ;
        $AutomaticTT = $validation['AutomaticTT'] ;
        $errorName = $validation['errorName'];
        $orderSLAStatus = $validation['orderSLAStatus'];
        $description = $validation['description'];
        $withinSR_ID = $validation['withinSR_ID'];
        $delaySR_ID = $validation['delaySR_ID'];
        $delayTT_ID = $validation['delayTT_ID'];
        if($errorName != ''){
            if($ActivitySLA != ' ' && $ActivitySLA != '' && $ActivitySLA != null){
                //$errorName = $validation['errorName'] .'<strong style="color: yellow;">( '.$ActivitySLA . ' )</strong>';
            }
        }

        //$withinSR_ID = 100034007;
        //$delaySR_ID = 100034007;
        //$delayTT_ID = 100034007;
        //$withinSR = 'Within SLA';
        //$delaySR = 'No Delay';
        //$delayTT = 'No Delay';

        if (is_numeric($ActivitySLA)) {
        $secondsInDay = 86400;
        $secondsInHour = 3600;

        if ($ActivitySLA >= $secondsInDay) {
            $days = floor($ActivitySLA / $secondsInDay);
            $ActivitySLA = $days . " Day" . ($days > 1 ? "s" : "");
        } elseif ($ActivitySLA >= $secondsInHour) {
            $hours = floor($ActivitySLA / $secondsInHour);
            $ActivitySLA = $hours . " Hour" . ($hours > 1 ? "s" : "");
        } else {
            $minutes = floor($ActivitySLA / 60);
            $ActivitySLA = $minutes . " Minute" . ($minutes > 1 ? "s" : "");
        }
    }

        return response()->json([

            'message' => 'Data received successfully!',
            'data' => $datatest,
            'ActivityName' => $ActivityName,
            'ActivitySLA' => $ActivitySLA,
            'errorName' => $errorName,
            'withinSR' => $withinSR,
            'delaySR' => $delaySR,
            'delayTT' => $delayTT,
            'orderSLA' => $orderSLA,
            'actionMessage' => $actionMessage,
            'ActivityCreateTime' => $ActivityCreateTime,
            'humanReadableDiff' => $humanReadableDiff,
            'AutomaticTT' => $AutomaticTT,
            'orderSLAStatus' => $orderSLAStatus,
            'description' => $description,
            'withinSR_ID' => $withinSR_ID,
            'delaySR_ID' => $delaySR_ID,
            'delayTT_ID' => $delayTT_ID,

        ]);
    }


}
