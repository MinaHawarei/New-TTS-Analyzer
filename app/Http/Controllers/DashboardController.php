<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Log;

class DashboardController extends Controller
{
 public function index()
    {
        // Fetch ALL logs from the database, ordered by the latest first.
        // This collection will be the primary data source for the frontend JavaScript
        // to filter, count, and render charts dynamically.
        $allLogs = Log::orderBy('created_at', 'desc')->get();

        // Calculate Key Performance Indicators (KPIs) based on the fetched actual logs.
        // These will be used for the initial display of the KPI cards.
        $totalLogs = $allLogs->count();
        $errorLogs = $allLogs->where('type', 'error')->count();
        $successLogs = $allLogs->where('type', 'success')->count();

        // The frontend JavaScript is designed to aggregate and filter data
        // from the `allLogs` collection. Therefore, `latestLogs`, `logsByModel`,
        // `logsOverTime`, and `models` are redundant to pass directly
        // and can be derived from `allLogs` on the client-side.
        return view('dashboard', [ // Ensure 'dashboard' is the correct view name
            'allLogs'     => $allLogs,      // Essential: Pass ALL logs for JS to process
            'totalLogs'   => $totalLogs,    // Initial total logs count for KPI card
            'errorLogs'   => $errorLogs,    // Initial error logs count for KPI card
            'successLogs' => $successLogs,  // Initial success logs count for KPI card
        ]);
    }

   public function error(Request $request)
    {
        try {
            Log::create([
                'tkt_id'      => $request->input('tkt_id') ?? null,
                'type'        => 'error',
                'model'       => $request->input('model') ?? null,
                'description' => $request->input('description') ?? null,

            ]);
        return response()->json(['status' => 'ok'], 200);

        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }

    }

}
