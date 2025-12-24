<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ConcessionApiService
{
    public function send($service_number, $tkt_id)
    {
        try {

            $response = Http::asForm()
                ->withoutVerifying()
                ->post('https://10.19.44.2/ireport/api/concession_api.php', [
                    'service_number' => $service_number,
                ]);

            if ($response->failed()) {
                Log::error('Concession API failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return false;
            }

            $data = $response->json();

            $results = collect();

            if (!empty($data['data'])) {
                foreach ($data['data'] as $sectionName => $section) {
                    if (($section['status'] ?? 0) != 1) {
                        continue;
                    }
                    if (!empty($section['data'])) {
                        switch ($sectionName) {
                            case 'Proactive':
                                foreach ($section['data'] as $item) {
                                    $tts = $item['tts_number'] ?? null;
                                    $status = trim(strtolower($item['concession_status'] ?? ''));
                                    $rejected_reason = trim(string: $item['rejected_reason'] ?? '');
                                    $added_on = !empty($item['added_on'])? Carbon::parse(trim($item['added_on'])): null;
                                    if (trim((string)$tts) === (string)$tkt_id) {
                                        $results->push([
                                            'section' => 'proactive',
                                            'status' => $status,
                                            'rejected_reason' => $rejected_reason,
                                            'added_on' => $added_on,
                                        ]);
                                    }
                                }
                                break;

                            case 'tech_concession_audit':
                                foreach ($section['data'] as $item) {
                                    $tickets = [
                                        $item['ticket_id'] ?? null,
                                        $item['ticket_2'] ?? null,
                                        $item['ticket_3'] ?? null,
                                        $item['ticket_4'] ?? null,
                                        $item['ticket_5'] ?? null,
                                    ];

                                    $status = trim(strtolower($item['audit_status'] ?? ''));
                                    $rejected_reason = trim($item['comment'] ?? '');

                                    $section_type = 'audit';
                                    $audit_type = trim(strtolower($item['audit_type'] ?? ''));

                                    if($audit_type == 'mobile - campaign'){
                                        $section_type = 'phone';
                                    }
                                    $added_on = !empty($item['added_on'])? Carbon::parse(trim($item['added_on'])): null;


                                    foreach ($tickets as $ticket) {
                                        if (trim((string)$ticket) === (string)$tkt_id) {
                                            $results->push([
                                                'section' => $section_type,
                                                'status' => 'approved',
                                                'rejected_reason' => $rejected_reason,
                                                'added_on' => $added_on,
                                            ]);
                                        }
                                    }
                                }
                                break;

                            case 'agent_concession_phone_search':
                                foreach ($section['data'] as $item) {
                                    $ticket = $item['tts_number'] ?? null;
                                    $status = trim(strtolower($item['concession_status'] ?? ''));
                                    $rejected_reason = trim($item['rejected_reason'] ?? '');
                                    $added_on = !empty($item['added_on'])? Carbon::parse(trim($item['added_on'])): null;
                                    if (trim((string)$ticket) === (string)$tkt_id) {
                                        $results->push([
                                            'section' => 'phone',
                                            'status' => $status,
                                            'rejected_reason' => $rejected_reason,
                                            'added_on' => $added_on,
                                        ]);
                                    }
                                }
                                break;

                            case 'retroactive_phone_search':
                                foreach ($section['data'] as $item) {
                                    $tickets = [
                                        $item['ticket_1'] ?? null,
                                        $item['ticket_2'] ?? null,
                                        $item['ticket_3'] ?? null,
                                        $item['ticket_4'] ?? null,
                                        $item['ticket_5'] ?? null,
                                    ];

                                    $status = trim(strtolower($item['concession_status'] ?? ''));
                                    $rejected_reason = trim($item['rejected_reason'] ?? '');
                                    if($status != 'approved'){
                                        $status = 'rejected';
                                    }
                                    $added_on = !empty($item['added_on'])? Carbon::parse(trim($item['added_on'])): null;
                                    foreach ($tickets as $ticket) {
                                        if (trim((string)$ticket) === (string)$tkt_id) {
                                            $results->push([
                                                'section' => 'phone',
                                                'status' => $status,
                                                'rejected_reason' => $rejected_reason,
                                                'added_on' => $added_on,
                                            ]);
                                        }
                                    }
                                }
                                break;

                            case 'Platinum and Detractor':
                                foreach ($section['data'] as $item) {
                                    $ticket = $item['ticket_number'] ?? null;
                                    $status = trim(strtolower($item['status'] ?? ''));
                                    $reason = trim(strtolower($item['comment'] ?? ''));

                                    if($status != 'disapproved'){
                                        $status = 'rejected';
                                    }else{
                                        $status = 'approved';
                                    }
                                    $added_on = !empty($item['added_on'])? Carbon::parse(trim($item['added_on'])): null;
                                    if (trim((string)$ticket) === (string)$tkt_id) {
                                        $results->push([
                                            'section' => 'detractor',
                                            'status' => $status,
                                            'reason' => $reason,
                                            'added_on' => $added_on,
                                        ]);
                                    }
                                }
                                break;

                            default:
                                Log::warning('Unknown section name in Concession API', ['section' => $sectionName]);
                                break;
                        }
                    }
                }
            }


            Log::info('Concession API success', $data);
            return $results->toArray();
        } catch (\Throwable $e) {
            Log::error('Concession API error', ['message' => $e->getMessage()]);
            return false;
        }
    }
}
