<?php

namespace App\Services;

class GetActions
{
    public static function getActions(
        string $type,
        string $problemType,
        ?float $amount,
        ?int $quota,
        string $hwoAddGB,
        string $hwoAddLE
    ): array {


        $actions = [];

        switch ($type) {

            case 'TTSCompensation':
                $GBactions = self::ttsCompensationActions(
                    'GB',
                    $problemType,
                    $amount,
                    $quota,
                    $hwoAddGB
                );
                $LEactions = self::ttsCompensationActions(
                    'LE',
                    $problemType,
                    $amount,
                    $quota,
                    $hwoAddLE
                );
                break;

            case 'AnotherType':
                // future types handled here
                break;
        }

        return $actions;
    }


    private static function ttsCompensationActions(
        string $type,
        string $problemType,
        ?float $amount,
        ?int $quota,
        string $responsbleTeam
    ): array {

        $actions = [];
        if ($responsbleTeam === 'Not Eligable') {

            $actions[] = [
                'type'       => 'Not Eligable',
                'label'      => '',
                'sr_type'    => 'TT',
                'sr_id'      => '101024018',
                'sr_name'    => 'We Mobile Adjustment',
                'sla'        => '15 Minutes',
                'quota'      => $quota,
                'amount'     => $amount,
                'expireDays' => 30,
            ];
        }

        // لو فيه GB Compensation
        if ($quota > 0) {
            $actions[] = [
                'type'       => 'Quota Compensation',
                'label'      => 'Add Quota to Customer',
                'sr_type'    => 'SR',
                'sr_id'      => '1020304',
                'sr_name'    => 'Quota Adjustment',
                'sla'        => '10 Minutes',
                'quota'      => $quota,
                'expireDays' => 30,
            ];
        }

        // لو فيه LE Compensation
        if ($amount > 0) {
            $actions[] = [
                'type'       => 'LE Compensation',
                'label'      => 'Financial Compensation',
                'sr_type'    => 'Bill',
                'sr_id'      => '302020',
                'sr_name'    => 'Billing Adjustment',
                'sla'        => '20 Minutes',
                'amount'     => $amount,
                'expireDays' => 90,
            ];
        }

        return $actions;
    }
}
