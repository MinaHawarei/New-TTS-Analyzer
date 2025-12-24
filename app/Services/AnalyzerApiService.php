<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AnalyzerApiService
{
    public function send($ticketId)
    {
        try {
            $response = Http::asForm()
                ->withoutVerifying()
                ->post('https://10.19.44.2/ireport/api/tts_analyzer_api.php', [
                    'ticket_id' => $ticketId,
                ]);

            if ($response->failed()) {
                Log::error('Analyzer API failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return false;
            }

            $data = $response->json();


            $delayData = data_get($data, 'data.delay_ir.data', []);
            $accelerationData = data_get($data, 'data.acceleration_ir.data', []);

            $reworkRecords = [];
            $delayRecords = [];
            $reassignRecords = [];

            foreach ($delayData as $record) {
                $type = strtolower($record['record_type'] ?? '');

                if ($type == 'rework'|| $type == 'Rework') {
                    $reworkRecords[] = $record;
                } elseif ($type == 'delayed' || $type == 'Delayed') {
                    $delayRecords[] = $record;
                } elseif($type == 're-assign'|| $type == 'Re-Assign') {
                    $reassignRecords[] = $record;
                }
            }

            $result = [
                'rework' => $reworkRecords,
                'delay' => $delayRecords,
                'reassign' => $reassignRecords,
                'acceleration' => $accelerationData,
            ];

            return $result;
        } catch (\Throwable $e) {
            Log::error('Analyzer API error', [
                'message' => $e->getMessage(),
                'ticket_id' => $ticketId,
            ]);
            return false;
        }
    }
}
