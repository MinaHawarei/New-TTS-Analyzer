<?php

namespace App\Imports;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class UsageDetails implements ToCollection
{
    public $dailyUsage = [];
    public $dslNumber = null;

    public function collection(Collection $rows)
    {
        $lastKnownPackage = null;

        foreach ($rows as $row) {
            if (isset($row[1], $row[4], $row[5], $row[13], $row[23], $row[34], $row[35], $row[37]) && is_numeric($row[13])) {

                try {
                    $dateTime = Carbon::createFromFormat('d/m/Y H:i:s', trim($row[4]));
                    $StartTime = Carbon::createFromFormat('d/m/Y H:i:s', trim($row[4]));
                    $EndTime = Carbon::createFromFormat('d/m/Y H:i:s', trim($row[5]));
                } catch (\Exception $e) {
                    continue;
                }

                $hourStart = $StartTime->copy()->second(0);
                $hourEnd = $EndTime->copy()->second(0);


                $usage = (int) $row[13] / 1073741824;
                $freeUnitBefore = (int) $row[34] / 1073741824;
                $freeUnitAfter = (int) $row[35] / 1073741824;
                $billCycle = $row[23];
                $packageName = rtrim(trim($row[37]), ',');
                $eventDescription = isset($row[28]) ? trim($row[28]) : null;
                if($eventDescription != null){
                    $eventDescription = 'Renewal : '. $eventDescription ;

                }
                if (!empty($packageName)) {
                    $lastKnownPackage = $packageName;
                } else {
                    $packageName = $lastKnownPackage ?? 'Unknown';
                }
                $toTime = $hourEnd;
                if ($toTime->format('H:i:s') === '00:00:00' && $eventDescription == null) {
                    $toTime = $toTime->subMinute();
                }

                if (is_null($this->dslNumber) && isset($row[0])) {
                    $dsl = trim($row[0]);
                    $this->dslNumber = preg_replace('/^FBB/', '0', $dsl);
                }

                $exceededQouta = $freeUnitBefore < 1;


                $this->dailyUsage[] = [
                    'datetime_hour' => $hourStart->format('Y-m-d H:00:00'),
                    'from_time' => $hourStart->format('Y-m-d H:i:00'),
                    'to_time' => $toTime->format('Y-m-d H:i:00'),
                    'total_usage' => $usage,
                    'free_unit_before' => $freeUnitBefore,
                    'free_unit_after' => $freeUnitAfter,
                    'packageName' => $packageName,
                    'exceeded_qouta' => $exceededQouta,
                    'bill_cycle' => $billCycle,
                    'event_description' => $eventDescription,
                ];

            }
        }
    }
}

