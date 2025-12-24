<?php

namespace App\Models\TTS;

use Carbon\Carbon;

class FilterData
{
    public static function FilterData($data)
    {
        $data = LogicalUdates::addDateTimeBeforeFooter($data);
        $compensated = 0 ;
        $pattern = '/\d{2}-\d{2}-\d{4}, \d{2}:\d{2} (AM|PM)/';
        preg_match_all($pattern, $data, $matches, PREG_OFFSET_CAPTURE);
        $orgData = [];
        $lastPos = 0;
        $currrentDataTime = null;
        $totalDuration = null; // Variable to store the total time
        $supportGroups = [
            ['pool' => 'Transfered: IU Maintenance', 'SLA' => 86400],
            ['pool' => 'Transfered: NOC', 'SLA' => 7200],
            ['pool' => 'Transfered: Maintenance Visits', 'SLA' => 86400],
            ['pool' => 'Transfered: Data Center Unit - DCU', 'SLA' => 7200],
            ['pool' => 'Transfered: FO-Fiber', 'SLA' => 86400],
            ['pool' => 'Transfered: Fiber(Regions)', 'SLA' => 86400],
            ['pool' => 'Transfered: Installation - Operations', 'SLA' => 86400],
            ['pool' => 'Transfered: FTTH-Support', 'SLA' => 86400],
            ['pool' => 'Transfered: SLS-FTTH', 'SLA' => 7200],
            ['pool' => 'Transfered: Pilot-SLS', 'SLA' => 3600],
            ['pool' => 'Transfered: Pilot - Follow up', 'SLA' => 7200],
            ['pool' => 'Transfered: Pilot-Follow up', 'SLA' => 7200],
            ['pool' => 'Transfered: Pending Fixing TE - IU', 'SLA' => 259200],
            ['pool' => 'Transfered: Second Level Advanced', 'SLA' => 7200],
            ['pool' => 'Transfered: customer 360', 'SLA' => 3600],
            ['pool' => 'Transfered: SLS-IVR Automation', 'SLA' => 3600],
            ['pool' => 'Transfered: OPNETSEC', 'SLA' => 9999999999999999],
            ['pool' => 'Transfered: Openetsec [Operation Network Security]', 'SLA' => 9999999999999999],
        ];
        $supportGroup = '';
        $closeCodeKeyword = '/Close Code\s*\((\d+)\):/';
        $problemTypeKeyword = '/Ticket Info: Ticket Title is\s*(.+)/';
        $StatusKeyword = '/Status Changed:\s*(.+)/';
        $problemTypeChangeKeyword = '/Problem has changed from .* To (.+)/';
        $curuantSupportPool = '/Transfered: \s*(.+)/';
        $compensation_pattern = '/\bconcession\b/i' ;
        $ticket_close_time = null;
        $status = '';
        $logicalPoolsFormats = '/Group Of \(NOC, Pilot-NOC, NOC-IPTV\) Ticket Info:\s*(.+)/';
        $physicalCases = ['data down','voice down' , 'data and voice down', 'wcap','wrong card and port', 'wrong nas port', 'physical instability', 'bad line quality', 'blq', 'installation', 'technical visits','voice and data down','dataonly','DataOnly'];
        $logicalCases = ['wrong nas port', 'unable to obtain ip', 'browsing', 'logical instability', 'slowness', 'speed', 'need optimization', 'wrong profile','adsl/vdsl', 'option pack' , 'Unsupported Services','unsupported services'];
        $firts_ticketTitle = null;
        $allTicketTitles = [];
        $logicalCodes = [101,88,99,104];
        if (preg_match_all($problemTypeKeyword, $data, $problemMatches)) {
            $allTicketTitles = array_map('strtolower', array_map('trim', $problemMatches[1]));
            $ticketTitle = trim(end($problemMatches[1]));
            $ticketTitle = strtolower(trim($ticketTitle));
            $firts_ticketTitle = $ticketTitle;

        }

        if (preg_match_all($StatusKeyword, $data, $statusMatches)) {
            $status = trim(end($statusMatches[1]));
            $status = strtolower(trim($status));
        }





        $changeticketTitle = '/Ticket Info:\s*Problem has changed from\s*(.+?)\s*To\s*(.+)/i';

        //check if ticket title is Changed or not

        if (preg_match_all($changeticketTitle, $data, $problemMatches) && !empty($problemMatches[0])) {
            $newticketTitle = trim(end($problemMatches[2]));
            $newticketTitle = strtolower(trim($newticketTitle));

            $newMatches = array_map('strtolower', array_map('trim', $problemMatches[2]));
            $allTicketTitles = array_merge($allTicketTitles, $newMatches);

            if(in_array($newticketTitle, $physicalCases) || in_array($newticketTitle, $logicalCases )){
                $ticketTitle = $newticketTitle ;
            }
        }







        if ($ticketTitle == 'need optimization' || $ticketTitle == 'logical instability') {
             //array_push($supportGroups, ['pool' => 'Transfered: CC Second Level Support', 'SLA' => 7200]);
        }



        $ticket_close_time_Keyword = '/(\d{2}-\d{2}-\d{4}, \d{2}:\d{2} (AM|PM))(?=.*Status Changed: Closed)/s';

        if ($ticketTitle == 'speed') {
            $ticketTitle = 'slowness';
        }
        if ($firts_ticketTitle == 'speed') {
            $firts_ticketTitle = 'slowness';
        }
        if ($ticketTitle == 'bad line quality') {
            $ticketTitle = 'blq';
        }
        if ($ticketTitle == 'dataonly' ||$ticketTitle == 'data down system'||$ticketTitle == 'data only') {
            $ticketTitle = 'data down';
        } elseif ($ticketTitle == 'dataandvoice' || $ticketTitle == 'dataonly+voice down' || $ticketTitle == 'data and voice down system' ){
            $ticketTitle = 'data and voice down';
        } elseif ($ticketTitle == 'Instability' || $ticketTitle == 'instability'){
            $ticketTitle = 'physical instability';
        }

        $isLogicalCase = false;
        foreach ($allTicketTitles as $title) {
            if (!in_array($title, $physicalCases)) {

                $isLogicalCase = true;
                break;
            }
        }

        // logical casses filtering

        if (!in_array($ticketTitle, $physicalCases) || !in_array($firts_ticketTitle, $physicalCases) || $isLogicalCase == true) {
            preg_match_all('/Transfered:\s*([^\n\d]+)/', $data, $Trans, PREG_OFFSET_CAPTURE);
            $firstTransferedPool = null;
            if (!empty($Trans[1])) {
                $firstTransferedPool = trim($Trans[1][0][0]);
                if ($firstTransferedPool == 'CC Second Level Support') {
                    if ($ticketTitle == 'logical instability') {
                        $ticketTitle = 'logical instability - no multiple logs';
                        array_push($supportGroups, ['pool' => 'Transfered: CC Second Level Support', 'SLA' => 18000]);
                    }elseif ($ticketTitle == 'browsing') {
                        $ticketTitle = 'browsing - certain sites';
                        array_push($supportGroups, ['pool' => 'Transfered: CC Second Level Support', 'SLA' => 18000]);
                    }else{
                        array_push($supportGroups, ['pool' => 'Transfered: CC Second Level Support', 'SLA' => 7200]);
                    }
                }
            }


            $data = LogicalUdates::processLogicalUpdates($data, $ticketTitle);
            $isLogicalCase = true;
        }
        $data = outSideTEDATA::processoutSideTEDATA($data);
        //$ticketTitle = $data ;

        if ($ticketTitle == 'Installation' || $ticketTitle == 'technical visits' || str_contains($ticketTitle, 'visit')) {
            $supportGroups[] = ['pool' => 'Transfered: MCU Field Support', 'SLA' => 432000];
            $supportGroups[] = ['pool' => 'Transfered: Mansoura MCU Field Support', 'SLA' => 432000];
            $supportGroups[] = ['pool' => 'Transfered: Alex MCU Field Support', 'SLA' => 432000];
        }
        // Delayed ID
        $delayedIdPatterns = [
            '/Delayed tickets, View record \[Ireport ID:(\d+)\]/',
            '/Tickets delayed, check entry \[Ireport ID:(\d+)\]/',
        ];

        // Acceleration ID
        $accelerationPatterns = [
            '/Acceleration Team, View record \[Ireport ID:(\d+)\]/',
            '/Acceleration Team, View record \[Id:(\d+)\]/',
        ];

        $lastTransferTime = null;
        $delayId = null;
        $accelerationId = null;

        $orgData = []; // Array to store the final objects
        $lastTransferTime = null; // Track the start time of the current supportGroup
        $supportGroup = null; // Track the current supportGroup
        $SLA = 0; // Track the SLA of the current supportGroup



        //new block filter

        $blockPattern = '/(\d{2}-\d{2}-\d{4}, \d{2}:\d{2} (?:AM|PM))[\s\S]*?(?=\s*\d{2}-\d{2}-\d{4}, \d{2}:\d{2} (?:AM|PM)|$)/s';

        // Use preg_match_all to find all blocks in the original data.
        preg_match_all($blockPattern, $data, $blocks, PREG_SET_ORDER);

        $closeCodePattern = '/Close Code\s*\((\d+)\)\s*:\s*(.*)/';
        $groupOfPattern = '/Group Of \((.*?)\) Ticket Info/';
        $transferBlocks = [];
        $closeCodeBlocks = [];
        $filteredBlocks = []; // This array will hold the blocks that pass our filter.
        $recognizedPools = array_column($supportGroups, 'pool');
        $transferPattern = '/Transfered: \s*(.+)/';
        $merged = [];
        $curuantSupportPool_block = null;

        // Loop through each identified block.
        foreach ($blocks as $block) {
            $fullBlockText = trim($block[0]);
            $timestamp = $block[1];

            if (
                stripos($fullBlockText, 'concession') &&
                stripos($fullBlockText, 'solved')
            ) {
                $compensated = 1;
            }
            // --- Condition 1: Check if the block contains a tracked transfer. ---
            if (preg_match($transferPattern, $fullBlockText, $transferMatches)) {
                $transferredPool = 'Transfered: ' . trim($transferMatches[1]);
                if (in_array($transferredPool, $recognizedPools)) {
                    //$transferBlocks[] = $fullBlockText;
                    $transferBlocks[] = [
                    'start' => $timestamp,
                    'pool' => trim($transferMatches[1]),
                    'block' => $fullBlockText
                    ];
                    $curuantSupportPool_block = trim($transferMatches[1]) ;
                }
            }

            if ($curuantSupportPool_block === 'IU Maintenance') {
                if (preg_match('/admin Group Of\s*\(.*?\)/i', $fullBlockText)) {
                    // لو موجود Group Of، هنبدله بـ IU Maintenance
                    $fullBlockText = preg_replace('/Group Of\s*\(.*?\)/i', 'Group Of (IU Maintenance)', $fullBlockText);
                }

            }


            // --- Condition 2: Check if the block contains a close code. ---
            if (preg_match($closeCodePattern, $fullBlockText, $codeMatches) ) {
                if(str_contains(strtolower($fullBlockText), strtolower($curuantSupportPool_block)) || !in_array($codeMatches[1], $logicalCodes)){
                    $groupOfMatch = [];
                    preg_match($groupOfPattern, $fullBlockText, $groupOfMatch);

                    $closeCodeBlocks[] = [
                        'end' => $timestamp,
                        'close_code' => $codeMatches[1], // The number inside the parentheses (e.g., '99').
                        'reason' => trim($codeMatches[2]), // The reason text after the colon.
                        'group_info' => $groupOfMatch[1] ?? 'N/A', // The Group Of content, or 'N/A' if not found.
                        'block' => $fullBlockText
                    ];
                }
            }elseif(preg_match($closeCodePattern, $fullBlockText, $codeMatches) && in_array($codeMatches[1], ['101', '102' , '103','104','105'])){
                $groupOfMatch = [];
                preg_match($groupOfPattern, $fullBlockText, $groupOfMatch);

                $closeCodeBlocks[] = [
                    'end' => $timestamp,
                    'close_code' => $codeMatches[1], // The number inside the parentheses (e.g., '99').
                    'reason' => trim($codeMatches[2]), // The reason text after the colon.
                    'group_info' => $groupOfMatch[1] ?? 'N/A', // The Group Of content, or 'N/A' if not found.
                    'block' => $fullBlockText
                ];
            }
        }


        foreach ($transferBlocks as $block) {
             $merged[] = [
                'timestamp' => $block['start'],
                'type' => 'transfer',
                'pool' => str_replace('Transfered: ', '', $block['pool']),
                'block' => $block['block']
            ];
        }
        // 2. ادمج أكواد الإغلاق
        foreach ($closeCodeBlocks as $block) {
             $merged[] = [
                'timestamp' => $block['end'],
                'type' => 'close',
                'close_code' => $block['close_code'],
                'reason' => $block['reason'],
                'block' => $block['block']
            ];
        }

        $firstTransferKey = null;
        $firstTransfer = null;
        foreach ($merged as $key => $item) {
            if ($item['type'] === 'transfer') {
                $firstTransferKey = $key;
                break;
            }
        }

        if (!is_null($firstTransferKey)) {
            $firstTransfer = $merged[$firstTransferKey];
            unset($merged[$firstTransferKey]);
        }


        usort($merged, function ($a, $b) {
            $timeA = strtotime($a['timestamp']);
            $timeB = strtotime($b['timestamp']);

            if ($timeA == $timeB) {
                $excludedCodes = [20, 105, 102];
                // A = close و B = transfer
                if ($a['type'] === 'close' && $b['type'] === 'transfer') {
                    if (!in_array((int)($a['close_code'] ?? 0), $excludedCodes)) {
                        return -1; // خلي close قبل transfer
                    }
                }

                // A = transfer و B = close
                if ($a['type'] === 'transfer' && $b['type'] === 'close') {
                    if (!in_array((int)($b['close_code'] ?? 0), $excludedCodes)) {
                        return 1; // خلي close قبل transfer
                    }
                }

            }

            return $timeA <=> $timeB;
        });
        if ($firstTransfer) {
            array_unshift($merged, $firstTransfer);
        }

        $sortedBlocks = $merged;
        $finalText = '';
        foreach ($sortedBlocks as $blockData) {
            $block = $blockData['block'];
            $block = preg_replace('/(Ticket Info)/i', "\n$1", $block);     // قبل Ticket Info
            $block = preg_replace('/(Transfered)/i', "\n$1", $block);      // قبل Transfered
            $finalText .= $blockData['block'] . "\n";
        }






        $orgData = [];
        $lastTransfer = null;
        $ticket_close_time = null;
        $delayId = 'N/A';
        $accelerationId = null;



        // Search for Ireport ID
        foreach ($delayedIdPatterns as $reportPattern) {
            if (preg_match($reportPattern, $data, $reportMatches)) {
                $delayId = $reportMatches[1];
                break;
            }
        }
        // Search for Acceleration ID
        foreach ($accelerationPatterns as $reportPattern) {
            if (preg_match($reportPattern, $data, $reportMatches)) {
                $accelerationId = $reportMatches[1];
                break;
            }
        }
        // Search for ticket_close_time
        if (preg_match_all($ticket_close_time_Keyword, $data, $matches)) {
            // Check if there are matches in the correct group
            if (! empty($matches[1])) {
                $lastMatch = end($matches[1]); // get last Matche

                try {
                    // Parse the date from the last match
                    $ticket_close_time = Carbon::parse($lastMatch);
                } catch (\Exception $e) {
                    // Handle exceptions by setting ticket_close_time to null
                    $ticket_close_time = null;
                }
            } else {
                // Set ticket_close_time to null if no matches are found in the group
                $ticket_close_time = null;
            }
        } else {
            // Set ticket_close_time to null if there are no matches at all
            $ticket_close_time = null;
        }


        $orgData = [];
        $totalItems = count($sortedBlocks);
        for ($i = 0; $i < $totalItems; $i++) {
            $current = $sortedBlocks[$i];

            if ($current['type'] === 'transfer') {
                $start = Carbon::parse($current['timestamp']);
                $pool = $current['pool'];
                $block = $current['block'];
                $end = null;
                $closeCode = 'N/A';
                $reason = null;

                $nextTransferIndex = null;
                for ($j = $i + 1; $j < $totalItems; $j++) {
                    if ($sortedBlocks[$j]['type'] === 'transfer') {
                        $nextTransferIndex = $j;
                        break;
                    }
                }

                if ($nextTransferIndex !== null) {
                    $end = Carbon::parse($sortedBlocks[$nextTransferIndex]['timestamp']);
                } else {
                    $end = null;
                }

                $searchLimit = $nextTransferIndex ?? $totalItems;

                $lastClose = null;

                for ($k = $i + 1; $k < $searchLimit; $k++) {
                    if ($sortedBlocks[$k]['type'] === 'close') {
                        $closeBlock = $sortedBlocks[$k]['block'] ?? '';
                        $end = Carbon::parse($sortedBlocks[$k]['timestamp']);

                        if (stripos($closeBlock, $pool) !== false
                            || !in_array($sortedBlocks[$k]['close_code'], $logicalCodes)
                        ) {

                            if ($pool === 'IU Maintenance') {

                                if(!in_array($sortedBlocks[$k]['close_code'],$logicalCodes) ){
                                    // أول close فقط
                                    $lastClose = $sortedBlocks[$k];

                                    break;
                                }

                            } else {
                                // نحفظ دايمًا آخر close
                                $lastClose = $sortedBlocks[$k];


                            }
                        }
                    }

                }
                if ($lastClose !== null) {
                    $closeCode = $lastClose['close_code'];
                    $reason = $lastClose['reason'];

                    if ($end === null) {
                        $end = Carbon::parse($lastClose['timestamp']);
                    }
                } elseif ($end !== null) {
                    $reason = 'Transfered';
                }

                try {
                    $duration = $start->diffInSeconds($end);
                } catch (\Exception $e) {
                    $duration = 0;
                }

                $SLA = 0;
                foreach ($supportGroups as $group) {
                    if (str_replace('Transfered: ', '', $group['pool']) == $pool) {
                        $SLA = $group['SLA'];
                        break;
                    }
                }

                $escalationData = new EscalationData(
                    $pool,
                    $SLA,
                    $start->toDateTimeString(),
                    $end ? $end->toDateTimeString() : null,
                    $duration,
                    $closeCode,
                    $reason,
                    $ticketTitle ?? 'N/A',
                    $delayId ?? 'N/A',
                    $accelerationId ?? null,
                    $ticket_close_time ?? null,
                    $compensated ?? 0,
                    $status
                );

                $orgData[] = $escalationData;
            }
    }



        return $orgData;
    }
}
