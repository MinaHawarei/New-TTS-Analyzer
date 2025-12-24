<?php
namespace App\Models\OM;

use Carbon\Carbon;

class OmData
{
    // Properties
    public $currentActivity;
    public $errorName;
    public $domainName;
    public $overallTimeOutSLA;
    public $orderSLAStatus;
    public $overallPreWarningSLAEndTime;
    public $overallTimeOutSLAEndTime;
    public $oldServiceNumber;
    public $oldAreaCode;
    public $oldExchangeName;
    public $serviceType;
    public $cpeDeliveryMethod;
    public $cpeType;
    public $cpeSN;
    public $cpeOldSN;
    public $macAddress;
    public $commercialLine;
    public $areaCode;
    public $exchangeName;
    public $bandwidthType;
    public $optionPack;
    public $familyADSL;
    public $msanFlag;
    public $priority;
    public $governorate;
    public $area;
    public $cityCode;
    public $lineOwnerName;
    public $compoundName;
    public $corporateName;
    public $subscriberType;
    public $secondFTTHPhone;
    public $villaNo;
    public $crmPackageRemark;
    public $teSoldOrHouseRental;
    public $currentActivityCreateTime;
    public $sector;
    public $customerSegment;
    public $customerLevel;
    public $secondPhoneNumberAreaCode;
    public $orderStatus;
    public $customerMobileNumber;
    public $emailAddress;
    public $overallPreWarningSLA;
    public $serviceOrderID;
    public $createTime;
    public $finishedTime;
    public $customerOrderID;
    public $customerName;
    public $product;
    public $serviceNumber;
    public $installAddress;
    public $serviceName;
    public $zoneName;
    public $lastUpdateDate;
    public $fccExchange;
    public $msanCode;
    public $serialNumber;
    public $vasName;
    public $fvFBBFlag;
    public $subscriberCategory;
    public $availabilityResult;
    public $changePhoneNoOptions;
    public $cancelFlag;
    public $fvnoFlag;

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
}
