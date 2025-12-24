<?php
namespace App\Models\Outage;

use Carbon\Carbon;

class OutageFilterData
{
    public static function FilterAll($rawData, $selectFollowUp)
    {
        $records = [];
        $lines = preg_split("/\r\n|\n|\r/", trim($rawData));
        $block = '';

        foreach ($lines as $line) {
            $block .= trim($line) . "\t";

            if (preg_match('/\b(Historical|Current)\b$/i', trim($line))) {
                $records[] = self::FilterData(trim($block), $selectFollowUp);
                $block = '';
            }
        }

        return $records;
    }

    public static function FilterData($data, $selectFollowUp = null)
    {
        $columns = explode("\t", trim($data));

        $last4 = array_slice($columns, -4);
        $mainCols = array_slice($columns, 0, count($columns) - 4);

        $dates = [];
        foreach ($mainCols as $index => $col) {
            if (preg_match('/\d{4}-\d{2}-\d{2} \d{1,2}:\d{2} (AM|PM)/', $col)) {
                $dates[] = ['index' => $index, 'value' => $col];
            }
        }

        $from = $dates[0]['value'] ?? null;
        $to = $dates[1]['value'] ?? null;
        $planned_from = $dates[2]['value'] ?? null;
        $planned_to = $dates[3]['value'] ?? null;

        // نحذف الأعمدة اللي فيها تواريخ من المصفوفة الأصلية
        foreach ($dates as $d) {
            unset($mainCols[$d['index']]);
        }
        $mainCols = array_values($mainCols);

        // نحاول تحويل كل تاريخ إلى كائن Carbon
        $from = self::toCarbon($from);
        $to = self::toCarbon($to);
        $planned_from = self::toCarbon($planned_from);
        $planned_to = self::toCarbon($planned_to);

        $mapped = [
            'ID' => $mainCols[0] ?? null,
            'Problem_in' => $mainCols[1] ?? null,
            'Problem_type' => $mainCols[2] ?? null,
            'Outage_type' => $mainCols[3] ?? null,
            'Effect_on' => $mainCols[4] ?? null,
            'Dslam' => $mainCols[5] ?? null,
            'Card' => $mainCols[6] ?? null,
            'Port' => $mainCols[7] ?? null,
            'Frame' => $mainCols[8] ?? null,
            'From' => $from,
            'To' => $to,
            'Planned_from' => $planned_from,
            'Planned_to' => $planned_to,
            'Comment_Added_by' => $last4[0] ?? null,
            'Added_by' => $last4[1] ?? null,
            'Added_on' => $last4[2] ?? null,
            'Current_status' => $last4[3] ?? null,
            'FollowUp' => $selectFollowUp ?? null,
        ];

        return new OutageData($mapped);
    }

    /**
     * translate text to Carbon object
     */
    protected static function toCarbon($value)
    {
        if (empty($value)) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Exception $e) {
            return null;
        }
    }
}
