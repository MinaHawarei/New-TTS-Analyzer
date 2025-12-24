<?php

namespace App\Models;

class CulcTheDuration
{
    public static function getculcTheDuration($mainpackage, $totalDurationDays , $validation)
    {
        $hwoAddGB = '';
        $hwoAddLE = '';
        $compensationGB = 0;
        $compensationLE = 0;

        $annual = false;
        $AnnualPackages = [13,14,15,16, 17, 18, 19, 20, 21,22,23];
        if (in_array($mainpackage['id'] ?? null, $AnnualPackages)) {
            $annual = true;
        }

        $packagePrice = $mainpackage['new_price'] ;
        $packageQuota = $mainpackage['quota'] ;
        $packageName = $mainpackage['unified_name'] ;

        if($totalDurationDays > 1){
            $totalDurationDays = ceil($totalDurationDays);

        }

        if ($annual === false) {
            $compensationGB = round($packageQuota / 30 * $totalDurationDays);
            $compensationLE = round($packagePrice / 30 * $totalDurationDays);
        } else {
            $compensationGB = round($packageQuota / 365 * $totalDurationDays);
            $compensationLE = round($packagePrice / 365 * $totalDurationDays);
        }

        if ($compensationGB <= 75) {
            $hwoAddGB = ' Agent on spot';
        } elseif ($compensationGB <= 180) {
            $hwoAddGB = ' CLM team SLA 15 min';
        } elseif ($compensationGB > 180) {
            $hwoAddGB = ' CLM team & Billing SLA 75 min (8AM:9PM except friday 2PM:9pm)';
        }

        if ($compensationLE <= 52) {
            $hwoAddLE = ' Agent on spot';
        } elseif ($compensationLE <= 200) {
            $hwoAddLE = ' CLM team SLA 15 min';
        } elseif ($compensationLE > 200) {
            $hwoAddLE = ' CLM team & Billing SLA 75 min (8AM:9PM except friday 2PM:9pm)';
        }

        if($totalDurationDays <= 1 && $totalDurationDays >= 0.5 && $validation != 'Not Valid'){
            $compensationLE = 0;
            $hwoAddLE = '';
            if($packageQuota <= 140){
                $compensationGB = 3;
            }elseif($packageQuota <= 250){
                $compensationGB = 5;
            }else{
                $compensationGB = 10;
            }
        }elseif($validation == 'Not Valid'){
            $compensationGB = 0;
            $compensationLE = 0;
            $hwoAddGB = '';
            $hwoAddLE = '';
        }

        return ['compensationGB' => $compensationGB, 'compensationLE' => $compensationLE, 'hwoAddGB' => $hwoAddGB, 'hwoAddLE' => $hwoAddLE, 'packageName' => $packageName, 'packageQuota' => $packageQuota];
    }
}
