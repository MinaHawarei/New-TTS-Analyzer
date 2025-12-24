<?php
namespace App\Models\Outage;

use Carbon\Carbon;

class OutageData
{
    // Properties
    public $ID;
    public $Problem_in;
    public $Problem_type;
    public $Outage_type;
    public $Effect_on;
    public $Dslam;
    public $Card;
    public $Port;
    public $Frame;
    public $From;
    public $To;
    public $Planned_from;
    public $Planned_to;
    public $Comment_Added_by;
    public $Added_by;
    public $Added_on;
    public $Current_status;
    public $Duration;
    public $Valid_duration;
    public $usageLimit;
    public $GB;
    public $LE;
    public $reason;
    public $validation;
    public $satisfaction;
    public $needToUsage;
    public $FollowUp;
    public $FollowUpStartDate;


    public function __construct($data = [])
    {
        // Verify that $data is an array or object
        if (!is_array($data) && !is_object($data)) {
            throw new \InvalidArgumentException("the data \$data must be an array or object.");
        }

        // Set default values for properties
        $this->setDefaultValues();

        // Assign values from the passed data

        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $this->sanitizeValue($key, $value);
            } else {
                error_log(" Warning: The key '$key'  does not exist in OmData object.");
            }
        }
        $this->calculateDerivedFields();

    }

    /**
     * Set default values for properties.
     */

    private function setDefaultValues()
    {
        foreach (get_class_vars(__CLASS__) as $property => $value) {
            $this->$property = null;
        }
    }

    /**
     * Sanitize values before assigning them.
     */

    private function sanitizeValue($key, $value)
    {
        // Assign default value if the value is empty
        if (empty($value)) {
            return null;
        }

        // Process fields that require date formatting
        if (in_array($key, ['createTime', 'finishedTime', 'currentActivityCreateTime'])) {
            try {
                return Carbon::parse($value)->toDateTimeString();
            } catch (\Exception $e) {
                error_log("Error parsing the value '$value' into date for the field '$key'.");
                 return null; // Avoid crash when invalid values are passed

            }
        }

        return $value;
    }
    private function calculateDerivedFields()
    {
        $InstabilityOutages = [
            'Logical Instability',
            'Slowness',
            'Unstable',
            'Service Degradation',
            'Ehlal Cabin MSAN',
            'Physical instability',
            'Replacement',
        ];
        $MajorFaults = [
            'Major Fault',
            'Major Fault - Maintenance'
        ];

        if ($this->From && $this->To) {
            try {
                $from = Carbon::parse($this->From);
                $to = Carbon::parse($this->To);
                $this->Duration = $from->diffInSeconds($to);
            } catch (\Exception $e) {
                $this->Duration = null;
            }
        }

        $ValidDuration = ($from->copy()->startOfDay()->diffInDays($to->copy()->startOfDay()) + 1)*86400;

        $oldValidDuration = $from->diffInSeconds($to);
        if($oldValidDuration <= 86400 && $ValidDuration > 1){
            $ValidDuration = 86400;
        }
        if($oldValidDuration < 21600){
            $ValidDuration = 0;
        }
        $this->Valid_duration = $ValidDuration  ;
        $this->needToUsage = false;
        $this->validation = false;

        if($this->Duration >= 21600){
            $this->validation = true;
        }
        $this->FollowUpStartDate = $from;

        if(in_array($this->Problem_type , $InstabilityOutages)){
            $this->usageLimit = 5;
            //new Rouls
            if($this->Duration > 86400){
                $this->needToUsage = true;
                if($this->Duration > 864000 && $this->FollowUp == true){
                    $followUpDate = Carbon::parse($this->From)->addDays(10);
                    $this->FollowUpStartDate = $followUpDate;
                }
            }
        }elseif(in_array($this->Problem_type , $MajorFaults)){
            $this->usageLimit = 1;
            $this->needToUsage = false;
            if($this->Duration > 43200){
                $this->needToUsage = true;
            }

        }else{
            if($this->Duration > 43200){
                $this->needToUsage = true;
            }
            $this->usageLimit = 1;

            //new Rouls
            if($this->Duration < 864000){
                $this->needToUsage = false;
            }else{
                $this->needToUsage = true;
                if($this->FollowUp == true){
                    $followUpDate = Carbon::parse($this->From)->addDays(10);
                    $this->FollowUpStartDate = $followUpDate;
                }
            }
        }

        if($this->needToUsage == true){
            $this->validation = false;
            $this->Valid_duration = 0 ;
        }

        $this->GB = null;
        $this->LE = null;
        $this->satisfaction = false;
        $this->reason = null;

    }

}
