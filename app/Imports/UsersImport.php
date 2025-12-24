<?php

namespace App\Imports;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class UsersImport implements ToCollection
{
    public $dailyUsage = []; // Array to collect usage data for each day

    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            if (isset($row[14]) && trim($row[14]) !== 'Bytes') {
                continue;
            }
            // Verify that the required columns have data before processing
            if (isset($row[1],$row[4], $row[5], $row[13], $row[23], $row[34]) && is_numeric($row[13])) {
                // Extract required data from each row
                $date = Carbon::createFromFormat('d/m/Y H:i:s', trim($row[4]))->toDateString();
                $usage = (int) $row[13] / 1073741824; // Ensure usage is a valid number
                $freeUnitBefore = (int) $row[34] / 1073741824;
                $freeUnitAfter = (int) $row[35] / 1073741824;
                $billCycle = $row[23];
                $packageName = $row[37];
                $dslNumber = $row[0] ;
                $dslNumber = preg_replace('/^FBB/', '0', $dslNumber);
                $exceededQouta = false;
                if ($freeUnitBefore < 1) {
                    $exceededQouta = true;
                }
                // Ensure there is an entry for the current day, if not initialize it
                if (! isset($this->dailyUsage[$date])) {
                    $this->dailyUsage[$date] = [
                        'date' => $date,
                        'total_usage' => 0,
                        'free_unit_before' => $freeUnitBefore,
                        'free_unit_after' => $freeUnitAfter,
                        'packageName' => $packageName,
                        'exceeded_qouta' => $exceededQouta,
                        'bill_cycle' => $billCycle,
                        'dsl_number' => $dslNumber ,
                    ];
                }
                // Update the total usage for the current day
                $this->dailyUsage[$date]['total_usage'] += $usage;
                $this->dailyUsage[$date]['packageName'] = $packageName;
                $this->dailyUsage[$date]['dsl_number'] = $dslNumber;
            }
        }
    }
}
