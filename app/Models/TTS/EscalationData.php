<?php

namespace App\Models\TTS;

use Carbon\Carbon;

class EscalationData
{
    public static $counter = 1;

    public $id;

    public $support_group;

    public $SLA;

    public $transfer_time;

    public $close_time;

    public $duration_seconds;

    public $duration_human;

    public $close_code;

    public $close_code_reason;

    public $valid;

    public $reason;

    public $ticketTitle;

    public $delayId;

    public $accelerationId;

    public $ticket_close_time;
    public $compensated;
    public $status;


    public function __construct($support_group, $SLA, $transfer_time, $close_time, $duration_seconds, $close_code, $close_code_reason, $ticketTitle, $delayId, $accelerationId, $ticket_close_time , $compensated , $status)
    {
        // Handle cases where close_code or close_time is not valid
        if ($close_code == 'N/A' || $close_time == null) {
            //$close_time = Carbon::now()->toDateTimeString();
            $closeCodeTime = Carbon::parse($close_time);
            $satrt_time = Carbon::parse($transfer_time);
            $duration_seconds = $satrt_time->diffInSeconds($closeCodeTime);
            $close_code_reason = ' ';
            $close_code == null;
            if($support_group == 'Data Center Unit - DCU'){
                //$duration_seconds = 1;

            }
        }


        // Handle cases where close_code_reason is 'N/A'
        if ($close_code_reason == 'N/A') {
            $close_code_reason = '  ';
        }
        // Adjust SLA and close_code_reason based on close_code values
        if ($close_code == 101){
            $close_code_reason = 'Waiting for IT ';
            $SLA = 259200 ;
        }
        if ($close_code == 102) {
            $close_code_reason = "Outside TEData ";
        }
        if ($close_code == 103) {
            $close_code_reason = "CPE Dual Visit ";
        }
        if ($close_code == 105) {
            $close_code_reason = "Duplicated ";
        }
        //logical cases
        $CstCideProblems = [
            'customer side problem',
            'no issue',
            'no problem',
            'obtained ip',
            'with another CPE',
            'with another cpe',
            'reset and reconfigure',
            'not learned',
            'check after line up',
            'line down',
        ];
        if ($close_code == 99) {
            // Convert text to lowercase and remove spaces
            $formattedText = strtolower($close_code_reason);
            foreach ($CstCideProblems as $word) {
                if (stripos($formattedText, $word) !== false) {
                    $close_code = 88;
                    break;
                }
            }

        }

        // Initialize object properties
        $this->id = self::$counter++;
        $this->support_group = $support_group;
        $this->SLA = $SLA;
        $this->transfer_time = $transfer_time;
        $this->close_time = $close_time;
        $this->duration_seconds = $duration_seconds;
        $this->duration_human = gmdate('H:i:s', $duration_seconds);
        $this->close_code = $close_code;
        $this->close_code_reason = $close_code_reason;
        $this->ticketTitle = $ticketTitle;
        $this->delayId = $delayId;
        $this->accelerationId = $accelerationId;
        $this->ticket_close_time = $ticket_close_time;
        $this->compensated = $compensated;
        $this->status = $status;

        // Validate close_code and initialize validation result

        $validationResult = $this->validateCloseCode($close_code, $duration_seconds, $SLA , $ticket_close_time,$compensated);
        $this->valid = $validationResult['valid'];
        $this->reason = $validationResult['reason'];

    }

    private function validateCloseCode($close_code, $duration_seconds, $SLA , $ticket_close_time , $compensated)
    {
        $result = false;
        $reason = '';
        $validCodes = [9,10, 81 , 12, 13, 11, 14, 18, 59, 82, 99, 35, 6,101,102,67];
        $notvalidCodes = [8,2, 86, 87, 83, 84, 75, 74, 65, 17, 28, 96 , 19 , 29, 23, 4, 30, 7, 27, 73 , 24 , 88];
        $nevervalidCodes = [103,27,104,105,38,26];
        $majorFaultCodes = [20];


        if (in_array($close_code, $validCodes)) {
            $result = true;
            $reason = ' TE problem ';
        }
        if (in_array($close_code, $notvalidCodes)) {
            $result = false;
            $reason = 'CST problem ';
        }
        if (in_array($close_code, $notvalidCodes) && $duration_seconds > $SLA) {
            $result = true;
            $reason = 'CST problem but After SLA';
        }
        if (in_array($close_code, $nevervalidCodes)) {
            $result = false;
            $reason = 'CST problem ';
        }
        if (in_array($close_code, $majorFaultCodes)) {
            $result = true;
            $reason = 'CST has major Fault ';
            $SLA = 259200;
        }
        if ($close_code == 'N/A' && $duration_seconds > $SLA && $ticket_close_time == null) {
            $result = true;
            $reason = 'TKT not close yet but After SLA';
        } elseif ($close_code == 'N/A' && $ticket_close_time == null) {
            $reason = 'TKT not close yet and within SLA';
        }
        if($duration_seconds == 1){
            $reason = 'Not Supported Pool Back to WIKI';
        }
        if($close_code == 26){
            $result = false;
            $reason = 'CST has Engineering inspection';
            $SLA = 259200;
        }elseif($close_code == 38){
            $result = false;
            $reason = 'CST problem';
        }
        if($close_code == 102){
            $reason = 'Has Ticket outside TEData ';
        }
        if($compensated == 1){
            //$result = false;
            //$reason = 'Compensated before';
        }
        return ['valid' => $result, 'reason' => $reason];
    }
}
