<?php

namespace App\Http\Controllers;
use App\Imports\UsageDetails;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use Carbon\Carbon;


class UsageDetailsController extends Controller
{
   public function index(Request $request)
    {
        $request->validate([
            'usage_file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        $file = $request->file('usage_file');
        $merge_limit_select = $request->input('merge_limit');
        $merge_limit = 100 ;
        if (!empty($merge_limit_select)) {
            $merge_limit = (float) $merge_limit_select;
        }

        $import = new UsageDetails;
        Excel::import($import, $file);

        $data = array_values($import->dailyUsage);


        $data = array_reverse($data);

        for ($i = 0; $i < count($data) - 1; $i++) {
    $current = $data[$i];
    $next = $data[$i + 1];

    if (
        $current['packageName'] == $next['packageName'] &&
        $current['event_description'] == $next['event_description']
    ) {
        $currEnd = Carbon::parse($current['to_time']);
        $nextStart = Carbon::parse($next['from_time']);
        $diffInMinutes = $currEnd->diffInMinutes($nextStart);
        $limitcheck = $current['total_usage'] + $next['total_usage'];

        if (
            $diffInMinutes === 0 &&
            $limitcheck <= $merge_limit &&
            $currEnd->isSameDay($nextStart)
        ) {
            $data[$i]['to_time'] = $next['to_time'];
            $data[$i]['total_usage'] += $next['total_usage'];
            $data[$i]['free_unit_after'] = $next['free_unit_after'];
            array_splice($data, $i + 1, 1);
            $i--;
        }
    }
}

        $data = array_reverse($data);

        for ($i = 0; $i < count($data) - 1; $i++) {
            $a = $data[$i];
            $b = $data[$i + 1];

            $aTime = Carbon::parse($a['from_time']);
            $bTime = Carbon::parse($b['from_time']);

            if ($aTime->eq($bTime)) {
                $aHasEvent = !empty(trim($a['event_description']));
                $bHasEvent = !empty(trim($b['event_description']));

                if ($aHasEvent && !$bHasEvent) {
                    $data[$i] = $b;
                    $data[$i + 1] = $a;
                    if ($i > 0) {
                        $i -= 2;
                    }
                }
            }
        }
        foreach ($data as &$row) {
            $diff = round($row['free_unit_before'] - $row['free_unit_after'], 2);
            $usage = round($row['total_usage'], 2);

            if ($diff <= $usage) {
                $row['validation'] = 'Normal';
            } else {
                $row['validation'] = 'Check';
            }
        }

        return response()->json([
            'message' => 'File processed successfully!',
            'dsl_number' => $import->dslNumber,
            'data' => $data,
            'merge_limit' => $merge_limit,
        ]);
    }
}
