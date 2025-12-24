<?php
namespace App\Models\OM;


class OmFilterData
{
    public static function FilterData($data)
    {
        // Split the line into columns using Tab as a delimiter
        $columns = explode("\t", trim($data));

        // Fixed headers (must match the column order in the line)
        $headers = [
            'currentActivity',
            'errorName',
            'domainName',
            'overallTimeOutSLA',
            'orderSLAStatus',
            'overallPreWarningSLAEndTime',
            'overallTimeOutSLAEndTime',
            'oldServiceNumber',
            'oldAreaCode',
            'oldExchangeName',
            'serviceType',
            'cpeDeliveryMethod',
            'cpeType',
            'cpeSN',
            'cpeOldSN',
            'macAddress',
            'commercialLine',
            'areaCode',
            'exchangeName',
            'bandwidthType',
            'optionPack',
            'familyADSL',
            'msanFlag',
            'priority',
            'governorate',
            'area',
            'cityCode',
            'lineOwnerName',
            'compoundName',
            'corporateName',
            'subscriberType',
            'secondFTTHPhone',
            'villaNo',
            'crmPackageRemark',
            'teSoldOrHouseRental',
            'currentActivityCreateTime',
            'sector',
            'customerSegment',
            'customerLevel',
            'secondPhoneNumberAreaCode',
            'orderStatus',
            'customerMobileNumber',
            'emailAddress',
            'overallPreWarningSLA',
            'serviceOrderID',
            'createTime',
            'finishedTime',
            'customerOrderID',
            'customerName',
            'product',
            'serviceNumber',
            'installAddress',
            'serviceName',
            'zoneName',
            'lastUpdateDate',
            'fccExchange',
            'msanCode',
            'serialNumber',
            'vasName',
            'fvFBBFlag',
            'subscriberCategory',
            'availabilityResult',
            'changePhoneNoOptions',
            'cancelFlag',
            'fvnoFlag',
        ];

        // Trim columns if the number of columns exceeds the number of headers
        if (count($columns) > count($headers)) {
            $columns = array_slice($columns, 0, count($headers));
        }

        // Pad columns with default values if the number of columns is less than the number of headers
        if (count($columns) < count($headers)) {
            $columns = array_pad($columns, count($headers), null);
        }

        // Create an OmData object from the columns
        $record = new OmData(array_combine($headers, $columns));

        return $record;
    }
}
