<?php

namespace App\Http\Controllers;

use App\Imports\UsersImport;
use App\Models\CulcTheDuration;
use App\Models\Outage\OutageFilterData;
use App\Models\FindPackage;
use App\Models\Outage\OutageValidation;
use App\Models\Outage\OutageData;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Services\GetActions;


class OutageController extends Controller
{
    public function create(Request $request)
    {
        $usage = [];
        $test = 0 ;
        $request->validate([
            'UsageFile' => 'mimes:xls,xlsx',
        ]);

        $ineligibleDays = $request->input('ineligibleDays');
        $selectedName = $request->input('selectPackage');

        $hwoAddGB = '';
        $hwoAddLE = '';
        $satisfaction = '';
        $total_days = '';
        $warnings = [];

        $selectFollowUp = $request->input('FollowUp');
        if($selectFollowUp){
            $selectFollowUp = true;
        }else{
            $selectFollowUp = false;
        }
        $hasUsagFile = false ;
        if ($request->hasFile('UsageFile')) {
            try {
                $file = $request->file('UsageFile');
                // Import data using UsersImport
                $import = new UsersImport;
                Excel::import($import, $file);
                // Store imported data
                $usage = $import->dailyUsage;
                $hasUsagFile = true ;

            } catch (\Exception $e) {
                $usage = 'Error importing file: '.$e->getMessage();
            }

        } else {
            $usage = [];
        }



        $data = $request->input('inputText');
        $From = $request->input('From');
        $To = $request->input('To');
        $outageType = $request->input('outageType');
        $outages = $request->input('outages');
        $service_number = $request->input('DSLnumber');

        $allValid = collect($outages ?? [])->every(function ($item) {
            return !empty($item['From']) && !empty($item['To']) && !empty($item['outageType']);
        });
        if($allValid){
            foreach($outages as $index => $item){
                $From = Carbon::createFromFormat('Y-m-d\TH:i', $item['From']);
                $To = Carbon::createFromFormat('Y-m-d\TH:i', $item['To']);
                if($item['outageType'] == 'Major Fault - Down'){
                    $item['outageType'] = 'Major Fault';
                }
                $singleData  = new OutageData ([
                'From' => $From,
                'Planned_from' => $From,
                'To' => $To,
                'Planned_to' => $To,
                'Problem_type' => $item['outageType'],
                'ID' => $item['ID'],
                'FollowUp' => $selectFollowUp ,
                ]);
                $allData[] = $singleData;
            }
            $orgData = collect($allData);

        }else{
            $orgData = collect(OutageFilterData::FilterAll($data, $selectFollowUp));


        }

       $orgData = $orgData->sortBy('From')->values();

        $orgData = $orgData->sort(function ($a, $b) use ($orgData) {
            // أولاً: نرتب حسب From
            if ($a->From == $b->From) {
                // ثانياً: لو نفس To كمان
                if ($a->To == $b->To) {
                    // ✅ نفضل اللي نوعه Major Fault - Maintenance أو Major Fault
                    if ($a->Problem_type == 'Major Fault - Maintenance' || $a->Problem_type == 'Major Fault') {
                        return -1; // a قبله
                    } elseif ($b->Problem_type == 'Major Fault - Maintenance' || $b->Problem_type == 'Major Fault') {
                        return 1; // b قبله
                    } else {
                        return 0;
                    }
                } else {
                    if ($a->Duration > $b->Duration) {
                        return -1;
                    } elseif ($a->Duration < $b->Duration) {
                        return 1;
                    } else {
                        return 0;
                    }
                }
            }
            if ($a->Problem_type == 'Major Fault - Maintenance'|| $a->Problem_type == 'Major Fault') {
                foreach ($orgData as $other) {
                    if ($other === $a) continue;

                    $intersects = $other->From < $a->To && $other->To > $a->From;
                    //$durationDiff = abs($other->Duration - $a->Duration);
                    $diffHours = abs((strtotime($a->From) - strtotime($other->From)) / 3600);

                    if ($intersects) {
                        return -1; // نحط عطل الصيانة في الأول
                    }
                }
            } elseif ($b->Problem_type == 'Major Fault - Maintenance' || $b->Problem_type == 'Major Fault') {
                foreach ($orgData as $other) {
                    if ($other === $b) continue;

                    $intersects = $other->From < $b->To && $other->To > $b->From;
                    //$durationDiff = abs($other->Duration - $b->Duration);
                    $diffHours = abs((strtotime($b->From) - strtotime($other->From)) / 3600);

                    if ($intersects) {
                        return 1;
                    }
                }
            }

            return $a->From <=> $b->From;
        });






        $cleanedData = [];
        $prev = null;
        foreach ($orgData as $item) {
            if($item->Problem_type == 'Major Fault - UNMS'){
                $item->Duration = 0 ;
                $item->validation = false ;
                $item->reason = "we compensate for Down outage only" ;
                $item->Valid_duration = $item->Duration;
            }
            if (!$prev) {

                $cleanedData[] = $item;
                $prev = $item;
                continue;
            }else{
                if ($item->From < $prev->To && $prev->Problem_type != 'Major Fault - UNMS') {

                    $item->From = $prev->To; // عدل البداية
                    $item->From = $item->From->copy()->addDay()->startOfDay();
                    $item->reason = "other outage in the same period" ;
                    $item->Duration = $item->From->diffInSeconds($item->To);
                    $item->Valid_duration = $item->Duration;

                    if($item->To > $prev->To){
                        $prev->To = $item->To ;
                    }


                }
                if ($item->To <= $item->From  && $prev->Problem_type != 'Major Fault - UNMS') {
                    $item->Duration = 0 ;
                    $item->validation = false ;
                    $item->reason = "other outage in the same period" ;
                    $item->Valid_duration = $item->Duration;

                }


            }
            $cleanedData[] = $item;
            $prev = $item;
        }
        $orgData = $cleanedData;
        $counter = count($orgData) - 1;
        $mainpackage_is_empty = false ;
        if ($hasUsagFile) {
            $dateStart = Carbon::parse($orgData[0]->From);
            $dateEnd = Carbon::parse($orgData[$counter]->To);

            $filteredUsage = collect($import->dailyUsage)
                ->filter(function ($item) use ($dateStart, $dateEnd) {
                    $date = Carbon::parse($item['date']);
                    return $date->between($dateStart, $dateEnd);
                });


            $mainpackage = $filteredUsage
                ->map(fn($item) => FindPackage::getPackage(strtolower($item['packageName'])))
                ->filter() // يشيل أي null
                ->first();  // ياخد أول باقة موجودة
            if($mainpackage == null){
                $mainpackage = collect($import->dailyUsage)
                ->map(fn($item) => FindPackage::getPackage(strtolower($item['packageName'])))
                ->filter()
                ->first();
                if($mainpackage == null){
                    $mainpackage = FindPackage::getPackageById($selectedName);
                    $mainpackage_is_empty = true ;
                }
            }
        }else{
            $mainpackage = FindPackage::getPackageById($selectedName);
            $mainpackage_is_empty = true ;
        }
        $total_GB = 0;
        $total_LE = 0;
        $total_days = 0;
        $total_satisfaction = 0;
        $startDate = null;
        $closeDate = null;
        $usageMessage = '';
        $filteredUsage = [];
        $total_validation = false;
        $UsageFileIsMissing = false;



        $tkt_id = '';

        foreach($orgData as $item){
            $validation = OutageValidation::validate($data ,$service_number, $item,  $usage);

            $filteredUsage = [...$validation['filteredUsage'], ...$filteredUsage];
            $usageMessage .= $validation['usageMessage'];
            $test = $validation['ValidDuration'] ;
            $totalDuration = $this->culcTheSeconds($validation['totalDuration']);
            $validDuration = $this->culcTheSeconds($validation['ValidDuration']);
            $tkt_id .= $item->ID . ' - ';
            $wrongDSLno = false;
            $problemType = $validation['problemType'];
            if($validation['DSLno'] != ''){
                $DSLno = $validation['DSLno'];
                if($DSLno != $service_number){
                $wrongDSLno = true;
            }
            }else{
                $DSLno = $service_number;
            }

            if ($validation['validation'] == true) {
                $validationMassege = 'valid';
                $validatioColor = 'green';
                $validationReason = $validation['reason'];
            } else {
                $validationMassege = 'Not valid';
                $validatioColor = 'red';
                $validationReason = $validation['reason'];
            }
            $diffFromLastClose = $this->closedDate($item->To);
            $closedinM = $diffFromLastClose['closedinM'];

            if ($closedinM >= 3) {
                $validationMassege = 'Not Valid';
                $validatioColor = 'red';
                $validationReason = ' its closed from more than 3 Months';
            }

            $validation_in_dayes = $validDuration['totalDays'] ;
            if($validDuration['totalDays'] <0.5 && $validDuration['totalDays']>0){
                $validation_in_dayes = 0.5;
            }
            $compensation = CulcTheDuration::getculcTheDuration($mainpackage, $validation_in_dayes , $validationMassege);

            $formattedData = '';

            $usageCollectionData = collect($filteredUsage)->map(function ($data) {
                $isHighUsage = ($data['color'] === 'red');
                return [
                    'date'    => $data['date'],
                    'usage'   => number_format($data['total_usage'], 2),
                    'unit'    => 'GB',
                    'color'   => $data['color'],
                    'note'    => $data['note'] ?? null,
                    'is_high'  => $isHighUsage,
                ];
            })->reverse()->values()->all(); // إضافة values() تحولها لمصفوفة عادية [{}, {}, {}]


            // breifing for 3/2/2025
            if ($validation['totalDuration'] < 86400) {
                $compensation['compensationLE'] = 0;
                $compensation['hwoAddLE'] = 'Not Eligable';
            }

            $readablemainpackage = $mainpackage['unified_name'] ?? 'N/A' ;



            $needToUsage = $validation['needToUsage'];

            if( $needToUsage == true && $hasUsagFile == false && $validation['validation'] == true && $validation['ValidDuration'] > 0){
                $validatioColor = 'red';
                $compensation['compensationGB'] = 0;
                $compensation['compensationLE'] = 0;
                $UsageFileIsMissing = true;

            }

            if($compensation['compensationGB'] != 0 && $validation['totalDuration'] > 43200 ){
                //$satisfaction = '( Double GB as Satisfaction )';
                $satisfactionGB = $compensation['compensationGB'];
            }else{
                //$satisfaction = '';
                $satisfactionGB = 0 ;
            }

            if($validation['ValidDuration'] <43200 && $validation['ValidDuration'] > 0 ){
                //$satisfaction = '( without Satisfaction )';
                $satisfactionGB = 0 ;
            }

            if ($compensation['compensationGB'] < 1) {
                $compensation['hwoAddGB'] = 'Not Eligable';
                $compensation['hwoAddLE'] = 'Not Eligable';

            }

            if($problemType == 'Major Fault - Maintenance'){
                $now = Carbon::now();
                $closeTime = $item->To;
                if (!($closeTime instanceof Carbon)) {
                    $closeTime = Carbon::createFromFormat('Y-m-d g:i A', $closeTime);
                }
                $diffInSeconds = $closeTime->diffInSeconds($now);
                if($diffInSeconds < 172800 && $orgData[0]->Duration > 86400){
                    $compensation['compensationGB'] = 0;
                    $compensation['compensationLE'] = 0;
                    $compensation['hwoAddGB'] = '';
                    $compensation['hwoAddLE'] = '';
                    $validationReason = 'Informed CST to await auto-compensation';
                    $validatioColor = 'red';

                }
            }

            $iteamStart = $item->From;
            if (!($iteamStart instanceof Carbon)) {
                $iteamStart = Carbon::createFromFormat('Y-m-d g:i A', $iteamStart);
            }

            if (is_null($startDate) || $iteamStart->lt($startDate)) {
                $startDate = $iteamStart;
            }

            $iteamEnd = $item->To;

            if (!($iteamEnd instanceof Carbon)) {
                $iteamEnd = Carbon::createFromFormat('Y-m-d g:i A', $iteamEnd);
            }

            if (is_null($closeDate) || $iteamEnd->gt($closeDate)) {
                $closeDate = $iteamEnd;
            }

            $item->GB =  $compensation['compensationGB'];
            $item->LE =  $compensation['compensationLE'];
            $item->satisfaction = $satisfactionGB;
            $item->reason = $validationReason;
            $item->validation = $validationMassege;
            $ValidDuration = $item->Valid_duration;
            $item->Valid_duration = $this->formatDuration($ValidDuration );
            $total_GB += $item->GB;
            $total_LE += $item->LE;
            $total_satisfaction += $item->satisfaction;
            if($item->GB > 0){
                $total_days += $ValidDuration;
                $total_validation = true;
            }



        }
        $tkt_id = rtrim($tkt_id, ' - ');
        if($total_validation){
            $validationMassege = 'valid';
            $validatioColor = 'green';

        }
        if($wrongDSLno){
            $validationMassege = 'Wrong Usage File';
            $validatioColor = 'red';
            $satisfaction = 0;
            $validationReason = 'The DSL number provided does not match the CDR usage file.';
            $formattedData = '';
            $total_GB = 0;
            $total_LE = 0;
            $total_days = 0;
            $hwoAddGB = '';
            $hwoAddLE = '';
            $satisfaction = 0;
        }


        if($ineligibleDays > 0 && $validationMassege == 'valid' && $total_days > $ineligibleDays){
            $ineligibleDays_compensation = CulcTheDuration::getculcTheDuration($mainpackage, $ineligibleDays , $validationMassege);
            $ineligibleDays = $ineligibleDays * 86400 ;
            $total_days = $total_days - $ineligibleDays ;
            $ineligibleDays_GB =  $ineligibleDays_compensation['compensationGB'];
            $ineligibleDays_LE=  $ineligibleDays_compensation['compensationLE'];
            $total_LE =  $total_LE - $ineligibleDays_LE;
            $total_GB =  $total_GB - $ineligibleDays_GB;
            $total_satisfaction = $total_satisfaction - $ineligibleDays_GB;

            if($total_days <= 0){
                $validationMassege = 'unPaid Days begger than valid duration';
                $validatioColor = 'red';
                $total_GB = 0;
                $total_LE = 0;
                $total_days = 0;
                $hwoAddGB = '';
                $hwoAddLE = '';
                $satisfaction = 0;
            }

        }

        $total_days = $total_days/86400;


        if($total_satisfaction!= 0){
            $satisfaction = $total_satisfaction;

        }else{
            $satisfaction = 0;
        }
        $TotalDiffInSeconds = $startDate->diffInSeconds($closeDate);
        $totalDuration = $this->culcTheSeconds($TotalDiffInSeconds);
        $startDate = $startDate->format('Y-m-d g:i A');
        $closeDate = $closeDate->format('Y-m-d g:i A');
        $diffFromLastClose = $this->closedDate($closeDate);
        $closedfrom = $diffFromLastClose['closedfrom'];

        if ($mainpackage_is_empty) {
            if($hasUsagFile){
                $warnings[] = [
                    'level' => 2,
                    'message' => 'Package manually selected – CDR usage file is empty'
                ];
            }else{
                $warnings[] = [
                    'level' => 2,
                    'message' => 'Package manually selected – CDR usage file missing'
                ];
            }
        }

        if ($total_GB <= 75) {
            $hwoAddGB = ' Agent on spot';
            if($total_GB == 0){
                $hwoAddGB = 'Not Eligable';
            }
        } elseif ($total_GB <= 180) {
            $hwoAddGB = ' CLM team SLA 15 min';
        } elseif ($total_GB > 180) {
            $hwoAddGB = ' CLM team & Billing SLA 75 min (8AM:9PM except friday 2PM:9pm)';
        }

        if ($total_LE <= 52) {
            $hwoAddLE = ' Agent on spot';
            if($total_LE == 0){
                $hwoAddLE = 'Not Eligable';
            }
        } elseif ($total_LE <= 200) {
            $hwoAddLE = ' CLM team SLA 15 min';
        } elseif ($total_LE > 200) {
            $hwoAddLE = ' CLM team & Billing SLA 75 min (8AM:9PM except friday 2PM:9pm)';
        }

        if($UsageFileIsMissing){
            $validationMassege = 'include the Usage File';
            $validatioColor = 'red';
            $total_GB = 0;
            $total_LE = 0;
            $hwoAddGB = '';
            $hwoAddLE = '';
            $satisfaction = 0;
            $total_days = '';

        }



        $responsbleTeam = '';
        $is_telephonet = false;



        if(str_contains($compensation['packageName'], 'Telephonet')){
            $is_telephonet = true;
        }
        $specialHandling = '' ;
        if($hwoAddLE == ' CLM team SLA 15 min' && $hwoAddGB == ' Agent on spot'){
            $specialHandling = 'CLMLE Agent on spot' ;
        }elseif($hwoAddLE == ' Agent on spot' && $hwoAddGB == ' CLM team SLA 15 min'){
            $specialHandling = 'CLMGB Agent on spot' ;
        }

        $available_actions = GetActions::GetActions(
            'compensation',
            'outage' ,
            $total_LE ,
            $total_GB,
            $hwoAddGB ,
            $hwoAddLE ,
            $specialHandling,
            false,
            $is_telephonet ,
            $tkt_id ,
            $satisfaction ,
            $total_days

        );

        return response()->json([

            'message' => 'Data received successfully!',
            'problemType' => $problemType,
            'closedDate' => $closedfrom,
            'totalDuration' => "{$totalDuration['days']} days, {$totalDuration['hr']} hours, and {$totalDuration['min']} minutes",
            'validDuration' => $total_days,
            'mainpackage' => $readablemainpackage,
            'compensationGB' => $total_GB,
            'hwoAddGB' => $hwoAddGB,
            'satisfaction' => $satisfaction,
            'compensationLE' => $total_LE,
            'hwoAddLE' => $hwoAddLE,
            'satisfactionLE' => $satisfaction,
            'validation' => $validationMassege,
            'validationcolor' => $validatioColor,
            'validationReason' => $validationReason,
            'usageMessage' => $usageMessage,
            'usage' => $formattedData,
            'startDate' => $startDate,
            'closeDate' => $closeDate,
            'DSLno' => $DSLno,
            'orgData' => $orgData,
            'responsbleTeam' => $responsbleTeam,
            'specialHandling' => $specialHandling,
            'warnings' => $warnings,
            'available_actions' => $available_actions,
            'usageCollectionData' => $usageCollectionData,


        ]);
    }
     private function closedDate($date)
    {
        $now = Carbon::now();
        if (!($date instanceof Carbon)) {
            $date = Carbon::createFromFormat('Y-m-d g:i A', $date);
        }
        $diffFromLastClose_to_now = $date->diffInMinutes($now);

        if ($diffFromLastClose_to_now > 1) {
            $diffFromLastClose = $date->diffForHumans($now);

            if ($diffFromLastClose == '0 seconds before'|| $diffFromLastClose_to_now < 1) {
                $diffFromLastClose = '';
            }
            $diffFromLastCloseinM = $date->diffInMonths($now);
        }

        return ['closedfrom' => $diffFromLastClose, 'closedinM' => $diffFromLastCloseinM];
    }

    private function formatDuration($seconds)
    {
        if ($seconds >= 86400) {
            $days = floor($seconds / 86400);
            return "$days day" . ($days > 1 ? "s" : "");
        } else {
            $hours = floor($seconds / 3600); // بنقرب لأقرب 0.1 ساعة مثلاً
     return "$hours hour" . ($hours != 1 ? "s" : "");     }
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
