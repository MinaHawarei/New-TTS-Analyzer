<?php

namespace App\Models\TTS;

class outSideTEDATA
{
    public static function processOutsideTedata($data)
    {
        // Ensure the input is a non-empty string
        if (!is_string($data) || empty(trim($data))) {
            return $data;
        }

        // Required pattern: searching for the exact text
        $outsideTEDataPattern = '/Ticket Info: ticket update customer already has FCC ticket outside TEData/u';
        $DuplicatedPattern = '/Ticket Info: Duplicated/u';
        $PendingFCCPattern = '/Status Changed: Pending FCC/u';
        $DualVisitPattern = '/Ticket Info: CPE Dual Visit/u';

        // Process outside TEData
        preg_match_all($outsideTEDataPattern, $data, $matches, PREG_OFFSET_CAPTURE);

        // If matches are found
        if (!empty($matches[0])) {
            $replacements = [];

            foreach ($matches[0] as $match) {
                $matchText = $match[0];
                $matchOffset = $match[1];

                $replacements[] = [
                    'offset' => $matchOffset,
                    'length' => strlen($matchText),
                    'new_text' => 'Group Of (IU Maintenance) Ticket Info: Close Code (102): ticket update customer already has FCC ticket outside TEData'
                ];
            }

            usort($replacements, fn($a, $b) => $b['offset'] - $a['offset']);

            foreach ($replacements as $rep) {
                $data = substr_replace($data, $rep['new_text'], $rep['offset'], $rep['length']);
            }
        }

        // Process outside TEData
        preg_match_all($DuplicatedPattern, $data, $matches, PREG_OFFSET_CAPTURE);

        // If matches are found
        if (!empty($matches[0])) {
            // Get the last match
            $lastMatch = end($matches[0]);
            $lastMatchText = $lastMatch[0]; // Matching text
            $lastMatchOffset = $lastMatch[1]; // Position of the matching text

            // Replace the last match
            $newText = 'Group Of (IU Maintenance, CC Second Level Support, Maintenance Visits, CC-Follow up, CC-Reapers, Pending Fixing TE - IU, customer 360, Second Level Advanced, Pilot-SLS, Pilot-Follow UP, FO-Fiber, SLS-IVR Automation, SLS-Archive, SLS-FTTH, NOC, Pilot-NOC, NOC-IPTV ) Ticket Info: Close Code (105): Duplicated';
            //$newText = 'Ticket Info: Duplicated';
            $data = substr_replace($data, $newText, $lastMatchOffset, strlen($lastMatchText));
        }

        // Process Dual Visit
        preg_match_all($DualVisitPattern, $data, $matches, PREG_OFFSET_CAPTURE);

        // If matches are found
        if (!empty($matches[0])) {
            // Get the last match
            $lastMatch = end($matches[0]);
            $lastMatchText = $lastMatch[0]; // Matching text
            $lastMatchOffset = $lastMatch[1]; // Position of the matching text

            // Replace the last match
            $newText = 'Group Of (IU Maintenance, CC Second Level Support, Maintenance Visits, CC-Follow up, CC-Reapers, Pending Fixing TE - IU, customer 360, Second Level Advanced, Pilot-SLS, Pilot-Follow UP, FO-Fiber, SLS-IVR Automation, SLS-Archive, SLS-FTTH, NOC, Pilot-NOC, NOC-IPTV, MCU, MCU Call Center, CC-Xceed Technical, MCU Field Support, Mansoura MCU Field Support, Alex MCU Field Support )Ticket Info: Close Code (103): CPE Dual Visit';
            $data = substr_replace($data, $newText, $lastMatchOffset, strlen($lastMatchText));
        }

        // Process Pending FCC pattern
        preg_match_all($PendingFCCPattern, $data, $matches, PREG_OFFSET_CAPTURE);

        return $data;
    }
}
