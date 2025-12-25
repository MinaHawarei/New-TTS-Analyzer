<?php

namespace App\Http\Controllers;

use App\Imports\UsersImport;
use App\Models\CulcTheDuration;
use App\Models\TTS\FilterData;
use App\Models\FindPackage;
use App\Models\TTS\CompensationValidation;
use App\Models\TTS\CuruantSupportPool;
use App\Models\Log;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class CompensationController extends Controller
{
    public function create(Request $request)
    {
        $usage = [];

        $request->validate([
            'UsageFile' => 'mimes:xls,xlsx',
        ]);
        $voiceImpacted = false;

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
        $voicetype = $request->input('voiceimpacttype');
        $orgData = FilterData::FilterData($data);
        $outageCloseTime = $request->input('outageCloseTime');

        if($outageCloseTime != null){
            $outageCloseTime = Carbon::parse($outageCloseTime);
            $esclationTime = count($orgData) ;
            if($esclationTime  >= 1){
                $i = 0;
                if($esclationTime > 1){
                    $i = count($orgData) - 1 ;
                }

                $orgData[$i]->close_time = $outageCloseTime;
                $orgData[$i]->ticket_close_time = $outageCloseTime;
            }
        }

        $transferToCount = $this->CountEsclation($orgData);
        $mainpackage_is_empty = false ;
        $selectedName = $request->input('selectPackage');
        $diffFromLastClose = $this->closedDate($orgData);
        $closedfrom = $diffFromLastClose['closedfrom'];
        $closedinM = $diffFromLastClose['closedinM'];
        if ($hasUsagFile) {
            $dateStart = Carbon::parse($orgData[0]->transfer_time);
            $dateEnd = Carbon::parse($orgData[0]->ticket_close_time);

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


        $selectFollowUp = $request->input('FollowUpcase');
        $voiceInstability = $request->input('voiceinstability');
        $ineligibleDays = $request->input('ineligibleDays');
        $tktopen_form = $request->input('tktopen');
        $tkt_id = $request->input('tktID');
        $service_number = $request->input('DSLnumber');

        $validationMassege = 'un defined';
        $i = $transferToCount - 1;
        if($selectFollowUp === 'yes'){
            $selectFollowUp = true;
        }elseif($selectFollowUp === 'no'){
            $selectFollowUp = false;
        }else{
            $selectFollowUp = null;
        }
        $validationReason = $orgData[0]->reason;
        $problemType = $orgData[0]->ticketTitle;
        $validatioColor = 'red';

        if($problemType == 'voice down' && $voiceInstability != null){
            $voiceImpacted = true;
            if($voicetype == 'Down'){
                $problemType = 'voice down (Data Down impacted)';
            }elseif($voicetype == 'Instability'){
                $problemType = 'voice down (Data Instability impacted)';
            }
        }
        $curuantSupportPool = CuruantSupportPool::processCuruantSupportPool($data,$problemType);
        $validation = CompensationValidation::validate($data ,$tkt_id,$service_number, $orgData, $transferToCount, $usage, $problemType, $selectFollowUp, $ineligibleDays , $voiceInstability , $curuantSupportPool , $voicetype);

        $usage = $validation['filteredUsage'];
        $usageMessage = $validation['usageMessage'];
        $needToCheekFollowUP = $validation['needToCheekFollowUP'];
        $totalDuration = $this->culcTheSeconds($validation['totalDuration']);
        $validDuration = $this->culcTheSeconds($validation['ValidDuration']);
        $startDate = $validation['startDate'];
        $closeDate = $validation['closeDate'];
        $transferToCount = $validation['transferToCount'];
        $wrongDSLno = false;
        if($validation['DSLno'] != ''){
            $DSLno =$validation['DSLno'];
            if($DSLno != $request->input('DSLnumber')){
                $wrongDSLno = true;
            }
        }else{
            $DSLno = $request->input('DSLnumber');
        }

        if ($validation['validation'] === true) {
            $validationMassege = 'Valid';
            $validatioColor = 'green';
            $validationReason = $validation['reason'];

        } else {
            $validationMassege = 'Not Valid';
            $validationReason = $validation['reason'];
        }


        if ($closedinM >= 3) {
            $validationMassege = 'Not Valid';
            $validatioColor = 'red';
            $validationReason = ' its closed from more than 3 Months';
        }
        if (strpos($data, 'Engineering Inspection') == true) {
            $validationMassege = 'Not Valid';
            $validatioColor = 'red';
            $validationReason = 'CST Has Engineering Inspection';
        }
        if ($validationReason == '' && $validation['tktStillOpen'] == true) {
            $validationReason = 'Ticket not closed yet';
        }
        if ($validationReason == 'CST has major Fault ' && $validation['tktStillOpen'] == true) {
            $validationMassege = 'Close Ticket';
            $validatioColor = 'yellow';
        }
        if ($validationReason == 'Not Supported Pool Back to WIKI') {
            $validationMassege = 'Not Supported';
            $validatioColor = 'red';
        }
        $compensation = CulcTheDuration::getculcTheDuration($mainpackage, $validDuration['totalDays'] , $validationMassege);



        $formattedData = '';
        foreach ($usage as $data) {
            $color = $data['color'];
            $note = $data['note'] ?? ' ';
            $formattedData .= "<span style='color: $color;'>".$data['date'].':......';
            $formattedData .= ''.number_format($data['total_usage'], 2).' GB';
            $formattedData .= '  '.$note.' </span><br>';
        }

        $usageCollectionData = collect($usage)->map(function ($data) {
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
        if ($mainpackage_is_empty) {
            if($hasUsagFile){
                $readablemainpackage .= '<br><span class="blinking" style="display: inline-block; margin-right: 6px; padding: 4px 8px; background-color: #ff0000ff; color: #212529; font-size: 1.1em; border-radius: 4px;">
                    ⚠️ Package manually selected – CDR usage file is empty
                    </span>';
            }else{
                $readablemainpackage .= '<br><span class="blinking" style="display: inline-block; margin-right: 6px; padding: 4px 8px; background-color: #ffffff; color: #212529; font-size: 1.1em; border-radius: 4px;">
                    ⚠️ Package manually selected – CDR usage file missing
                    </span>';
            }
        }

        if($curuantSupportPool == null){
            $curuantSupportPool = $validation['curuantSupportPool'];
        }



        if($transferToCount >1 ){
            if($orgData[0]->support_group == 'customer 360' || $orgData[0]->support_group == 'SLS-IVR Automation'){
                $transferToCount --;
            }
        }



        $needToUsage = $validation['needToUsage'];
        if( $needToUsage == true && $hasUsagFile == false && $validation['validation'] == true && $validation['ValidDuration'] > 0){
            $validationMassege = 'include the Usage File';
            $validatioColor = 'red';
            $compensation['compensationGB'] = 0;
            $compensation['compensationLE'] = 0;

        }
        if($validation['ValidDuration'] == 0 && $validation['validation'] == true){
            $validationMassege = 'Handle it Manual';
            $validatioColor = 'red';
            $compensation['compensationGB'] = 0;
            $compensation['compensationLE'] = 0;
            $validation['ValidDuration'] = 0 ;
            $validation['validation'] == false;
        }
        if($compensation['compensationGB'] != 0){
            $satisfaction = '( Double GB as Satisfaction )';
        }else{
            $satisfaction = '';
        }

        $satisfactionLE = '( and '. $compensation['compensationGB'] . ' GB as Satisfaction )';

        if($compensation['compensationLE'] == 0){
            $satisfactionLE = '';
        }
        if ($compensation['compensationGB'] < 1) {
            $compensation['hwoAddGB'] = 'Not Eligable';
            $compensation['hwoAddLE'] = 'Not Eligable';

        }




        if(($problemType == 'voice down' || $problemType == 'Voice overlapping') && $validation['validation'] == true){
            $validationMassege = '<span style="font-size: 1.0em;">Not Eligable as Case Voice Down unless the data was impacted</span>';
            $validatioColor = 'red';
            $compensation['compensationGB'] = 0;
            $compensation['hwoAddGB'] = '';
            $compensation['compensationLE'] = 0 ;
            $compensation['hwoAddLE'] = '';
            $voiceImpacted = true;
        }
        if($tktopen_form == null && $validation['tktStillOpen'] == true && $validation['validation'] == true){
            $validationMassege = 'ticket not closed yet check if problem solved.';
            $validatioColor = 'red';
            $compensation['compensationGB'] = 0;
            $compensation['hwoAddGB'] = '';
            $compensation['compensationLE'] = 0 ;
            $compensation['hwoAddLE'] = '';

        }




        $LEresponsbleTeam = '';
        $GBresponsbleTeam = '';



        if($compensation['hwoAddGB'] == ' CLM TIER and SLA 15min'){
            $GBresponsbleTeam = 'CLM TIER';
        }
        if($compensation['hwoAddLE'] == ' CLM TIER and SLA 15min'){
            $LEresponsbleTeam = 'CLM TIER';
        }
        if(str_contains($compensation['packageName'], 'Telephonet')){
            if($compensation['hwoAddGB'] != ' CLM TIER and SLA 15min'){
                $GBresponsbleTeam = 'CLM Telephonet';
            }
            if($compensation['hwoAddLE'] != ' CLM TIER and SLA'){
                $LEresponsbleTeam = 'CLM Telephonet';
            }
        }


        $validDurationinDayes = $validDuration['days'] ;
        if($validDurationinDayes == 1){
            //$validDurationinDayes = "1 Day";
        }elseif($validDurationinDayes > 1){
            //$validDurationinDayes .= " Days";
        }elseif($validDurationinDayes == 0 && $validDuration['hr']>0){
            //$validDurationinDayes = $validDuration['hr'] . " hours";
        }
        $outage = false ;
        if($validation['outageTKT_onADF'] && $outageCloseTime == null){
            $validationMassege = 'include the Outage Close Time from CST 360';
            $validatioColor = 'orange';
            $compensation['compensationGB'] = 0;
            $compensation['hwoAddGB'] = '';
            $satisfaction = '';
            $compensation['compensationLE'] = 0;
            $compensation['hwoAddLE'] = '';
            $validationReason = 'Check outage & Calculated from Ticket Escalation .. <a href="https://sp-wiki.te.eg:5443/Pages/Customer%20Service%20Dept/Contact%20Center%20Division/Sales%20Contact%20Center/Wiki%20BSS/Account%20Management/Concession/Technical%20Concession.aspx#Ticket_Conditions" target="_blank" style="color:#b28df7; text-decoration:underline;">WIKI</a>';
            $outage = true ;
        }

        if($needToCheekFollowUP && $selectFollowUp === null){
            $validationMassege = 'check if cst follow up with us or not';
            $validatioColor = 'red';
            $compensation['compensationGB'] = 0;
            $compensation['hwoAddGB'] = '';
            $satisfaction = '';
            $compensation['compensationLE'] = 0;
            $compensation['hwoAddLE'] = '';
            $validationReason = '';
            $satisfaction = '';
            $formattedData = '';
            $validDurationinDayes = 0 ;
        }
        $tktId = $request->input('tktID');
        $LogsDescription = '';
        if($wrongDSLno){
            $validationMassege = 'Wrong Usage File';
            $validatioColor = 'red';
            $compensation['compensationGB'] = 0;
            $compensation['hwoAddGB'] = '';
            $satisfaction = '';
            $satisfactionLE = '';
            $compensation['compensationLE'] = 0;
            $compensation['hwoAddLE'] = '';
            $validationReason = 'The DSL number provided does not match the CDR usage file.';
            $formattedData = '';
            $validDurationinDayes = 0 ;
        }



        switch ($validationMassege) {
            case 'Valid':
                $LogsDescription = 'Valid tkt with Valid days '.$validDurationinDayes. ' and ' . $compensation['compensationGB'] . ' GB ' .$compensation['hwoAddGB'] . ' and ' . $compensation['compensationLE'] . ' LE ' . $compensation['hwoAddLE'] . ' and usage is ' . $formattedData;
                break;

            case 'Not Valid':
                $LogsDescription = 'Not Valid and reson was is ' . $validationReason;
                break;

            default:
                $LogsDescription = '';
                break;
        }

        if($LogsDescription != ''){
            try {
                Log::create([
                    'tkt_id'      => $tktId ?? null,
                    'type'        => 'success',
                    'model'       => 'compensation',
                    'description' => $LogsDescription,
                ]);
            } catch (\Exception $e) {
            }
        }
        $duplecatedWarning = '';
        if($validation['cst_has_tkt_before'] == true){
            if($validation['cst_compansated_before'] == true){
                $validationMassege .= '<br>
                    <span style="flex-shrink: 0 !important; color: black !important; background-color: yellow !important; font-size: 0.5em !important; padding: 2px 6px !important; border-radius: 4px !important;">
                        Customer has been compensated before
                    </span>
                    <span class="blinking" style="font-size: 0.5em !important;">⚠️</span>

                ';
                $validationReason = '';
                $duplecatedWarning = 'Customer has been compensated before';
            }else{
                if($validation['compansated_status'] =='pending'){
                    if($validation['api_section'] =='proactive'){
                        $duplecatedWarning = 'Check TTs/SRs and limit query transactions if the customer was compensated before';
                        $validation['cst_has_tkt_before'] = false ;
                        $validation['cst_compansated_before'] = false ;
                        $validationMassege .= ' <span class="blinking" style="font-size: 1.0em !important;">⚠️</span>
                            <br>
                            <span style="flex-shrink: 0 !important; color: black !important; background-color: yellow !important; font-size: 0.4em !important; padding: 2px 6px !important; border-radius: 4px !important; display: inline-block !important;">
                                Check TTs/SRs and limit query transactions if the customer was compensated before
                            </span>
                        ';


                    }else{
                         $validationMassege .= '<br>
                        <span style="flex-shrink: 0 !important; color: black !important; background-color: yellow !important; font-size: 0.5em !important; padding: 2px 6px !important; border-radius: 4px !important;">
                            this Ticket has Pending TT </span>
                        <span class="blinking" style="margin-right: 6px; font-size: 0.5em;">⚠️</span>

                        ';
                        $compansated_added_on = $validation['compansated_added_on'] ;
                        if($compansated_added_on != null){
                            try {
                                $humanDiff = $compansated_added_on->diffForHumans();
                                $validationReason ="Added on : ". $compansated_added_on . " ( ".$humanDiff." ).";
                            }catch (\Exception $e){
                                $validationReason = "Added on: N/A";
                            }
                        }
                        $duplecatedWarning = 'this Ticket has Pending TT';
                    }


                }else{
                    $validationMassege .= '<br>
                        <span style="flex-shrink: 0 !important; color: black !important; background-color: yellow !important; font-size: 0.5em !important; padding: 2px 6px !important; border-radius: 4px !important;">
                            this Ticket has Rejected TT Before </span>
                        <span class="blinking" style="margin-right: 6px; font-size: 0.5em;">⚠️</span>

                    ';
                    $validationReason ="with Rejected Reason : ". $validation['tkt_rejected_reason'];
                    $duplecatedWarning = 'this Ticket has Rejected TT Before';

                }
            }

        }

        $specialHandling = '' ;
        if($compensation['hwoAddLE'] == ' CLM team SLA 15 min' && $compensation['hwoAddGB'] == ' Agent on spot'){
            $specialHandling = 'CLMLE Agent on spot' ;
        }elseif($compensation['hwoAddLE'] == ' Agent on spot' && $compensation['hwoAddGB'] == ' CLM team SLA 15 min'){
            $specialHandling = 'CLMGB Agent on spot' ;
        }

        return response()->json([
            'message' => 'Data received successfully!',
            'problemType' => $problemType,
            'curuantSupportPool' => $curuantSupportPool,
            'escalationtimes' => $transferToCount,
            'closedDate' => $closedfrom,
            'totalDuration' => "{$totalDuration['days']} days, {$totalDuration['hr']} hours, and {$totalDuration['min']} minutes",
            'validDuration' => $validDurationinDayes,
            'mainpackage' => $readablemainpackage,
            'compensationGB' => "{$compensation['compensationGB']} GB",
            'hwoAddGB' => "{$compensation['hwoAddGB']}",
            'satisfaction' => $satisfaction,
            'compensationLE' => "{$compensation['compensationLE']} LE",
            'hwoAddLE' => "{$compensation['hwoAddLE']}",
            'satisfactionLE' => $satisfactionLE,
            'validation' => $validationMassege,
            'validationcolor' => $validatioColor,
            'validationReason' => $validationReason,
            'usageMessage' => $usageMessage,
            'usage' => $formattedData,
            'usageCollectionData' => $usageCollectionData,
            'startDate' => $startDate,
            'closeDate' => $closeDate,
            'DSLno' => $DSLno,
            'LEresponsbleTeam' => $LEresponsbleTeam,
            'GBresponsbleTeam' => $GBresponsbleTeam,
            'tktStillOpen' => $validation['tktStillOpen'],
            'needToCheekFollowUP' => $needToCheekFollowUP,
            'voiceImpacted' => $voiceImpacted,
            'cst_has_tkt_before' => $validation['cst_has_tkt_before'],
            'cst_compansated_before' => $validation['cst_compansated_before'],
            'duplecatedWarning' => $duplecatedWarning,
            'outage' => $outage,
            'specialHandling' => $specialHandling,
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
