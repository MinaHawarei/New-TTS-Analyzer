<?php

namespace App\Models\TTS;

class LogicalUdates
{
 public static function processLogicalUpdates($data, $ticketTitle)
    {
        $originalData = $data;

        // Define the regex pattern to match "Group Of (NOC, Pilot-NOC, NOC-IPTV) Ticket Info:"
        $NOCFormats = '/(Group Of\s*\([^)]*NOC[^)]*\)\s*Ticket Info:)(?!\s*(?:Ticket|User|we|We|WE|Appreciate|Customer Type|Customer Name|customer follow|Customer Follow|Customer Side|New Ticket)\b)(.*)/m';
        $SLSFormats = '/(Group Of\s*\([^)]*Pilot-SLS[^)]*\)\s*Ticket Info:)(?!\s*(?:Ticket|User|Customer|New Ticket|displayed Mobile|File)\b)(.*)/m';
        $MCUFormats = '/(.*?\d{2}-\d{2}-\d{4}, \d{2}:\d{2} [AP]M)(.*?Group Of\s*\([^)]*MCU Field Support\)\s*Ticket Info:)(?!\s*(?:Ticket|User|Customer|New Ticket)\b)(.*)/m';
        $closeCode = 99 ;


        if ($ticketTitle == 'need optimization') {
            $NOCFormats = '/(Group Of\s*\([^)]*Pilot-SLS[^)]*\)\s*Ticket Info:)(?!\s*(?:Ticket|User|we|We|WE|Appreciate|Customer Type|Customer Name|customer follow|Customer Follow|Customer Side|New Ticket)\b)(.*)/m';
        }
        // if tkt Waiting for IT
        if (strpos($data, 'Status Changed: Waiting for IT') !== false) {
            $closeCode = 101 ;
            $NOCFormats = '/(Group Of\s*\([^)]*(?:Pilot-SLS|NOC)[^)]*\)\s*Ticket Info:)(?!\s*(?:Ticket|User|Customer|New Ticket|displayed Mobile|File|called|Request)\b)(.*)/m';
        }


        // Process data based on ticket title

        if (is_string($data)) {

            if ($ticketTitle == 'need optimization2') {
                preg_match_all($NOCFormats, $data, $matches, PREG_OFFSET_CAPTURE);
                if (! empty($matches[0])) {
                    $lastMatch = end($matches[0]);
                    $lastMatchStart = $lastMatch[1];

                    $data = substr_replace(
                        $data,
                        $matches[1][array_key_last($matches[1])][0]." Close Code ($closeCode):".$matches[2][array_key_last($matches[2])][0],
                        $lastMatchStart,
                        strlen($lastMatch[0])
                    );
                }
            } else {

                $data = preg_replace_callback($NOCFormats, function ($matches)use ($closeCode) {
                    if (isset($matches[1]) && isset($matches[2])) {
                        return $matches[1]." Close Code ($closeCode):".$matches[2];
                    }

                    return $matches[0]; // No match
                }, $data);

            }


        }
        // Handle original data if no changes occurred

        if ($originalData === $data || $ticketTitle == 'logical instability - no multiple logs' || $ticketTitle == 'browsing - certain sites') {
            $SLSFormats = '/(Group Of\s*\([^)]*CC Second Level Support[^)]*\)\s*Ticket Info:)(?!\s*(?:Ticket|User|Customer|New Ticket|displayed Mobile|File)\b)(.*)/m';

            if (is_string($data)) {
                $data = preg_replace_callback($SLSFormats, function ($matches)use ($closeCode) {
                    if (isset($matches[1]) && isset($matches[2])) {
                        return $matches[1]." Close Code ($closeCode):".$matches[2];
                    }

                    return $matches[0]; // No match
                }, $data);
            }
        }
        // Check for MCU Field Support format matches
        if (preg_match($MCUFormats, $data)) {
            $data = preg_replace_callback($MCUFormats, function ($matches) {
                if (isset($matches[1]) && isset($matches[2]) && isset($matches[3])) {
                    return $matches[1] . " Transfered: MCU Field Support " . $matches[1] . $matches[2] . " Close Code (104):" . $matches[3];
                }
                return $matches[0]; // No match
            }, $data);
        }

        return $data;
    }

    public static function addDateTimeBeforeFooter($data)
    {
        $searchText = '©Telecomegypt OSS Team 2016';
        $currentDateTime = date('d-m-Y, h:i A');

        // Add current date-time before footer text if it exists

        if (strpos($data, $searchText) !== false) {
            $data = str_replace($searchText, $currentDateTime."\n".$searchText."\n", $data);
        }

        return $data;
    }
}
