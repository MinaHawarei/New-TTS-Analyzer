<?php

namespace App\Models\TTS;

class CuruantSupportPool
{
    public static function processCuruantSupportPool($data , $ticketTitle)
    {
        $supportGroups = [
            'Transfered: CC Xceed Technical',
            'Transfered: CC Xeed Basic',
            'Transfered: CC Service Activation',
            'Transfered: SLS-Archiving',
            'Transfered: CC Online Support',
            'Transfered: Digital Data Chat',
            'Transfered: Business and Special Support',
            'Transfered: ICare',
            'Transfered: CSI Team',
            'Transfered: CSI',
            'Transfered: MCU Field Support',
            'Transfered: Mansoura MCU Field Support',
            'Transfered: Alex MCU Field Support',
            'Transfered: CC-Follow up',
            'Transfered: customer 360',
            'Transfered: SLS-IVR Automation',
            'Transfered: MCU Call Center',
            'Transfered: Installation - Operations',
            'Transfered: IU Maintenance',
            'Transfered: NOC',
            'Transfered: CC Second Level Support',
            'Transfered: Maintenance Visits',
            'Transfered: Data Center Unit - DCU',
            'Transfered: FO-Fiber',
            'Transfered: Fiber(Regions)',
            'Transfered: Installation - Operations',
            'Transfered: FTTH-Support',
            'Transfered: Pilot-SLS',
            'Transfered: Pilot - Follow up',
            'Transfered: Pilot-Follow up',
            'Transfered: Pending Fixing TE - IU',
            'Transfered: Second Level Advanced',
            'Transfered: MCU Field Support',
            'Transfered: customer 360',
            'Transfered: Business Technical Support',
            'Transfered: CC-Service Activation',
            'Transfered: CC-Online Support',
            'Transfered: CC-Xceed Technical',
            'Transfered: CC-VIP',
            'Transfered: I Care',
            'Transfered: SLS-FTTH',
            'Transfered: Openetsec [Operation Network Security]',
            'Transfered: OPNETSEC',
        ];

        if ($ticketTitle == 'Installation' || $ticketTitle == 'technical visits') {
            $supportGroups[] = ['pool' => 'Transfered: MCU Field Support', 'SLA' => 432000];
        }
        // looking for the last match
        $lastMatch = null;
        $lastPosition = -1;

        foreach ($supportGroups as $group) {
            $pos = strrpos($data, $group); // البحث عن **آخر ظهور** للنص داخل `$data`
            if ($pos !== false && $pos > $lastPosition) {
                $lastMatch = str_replace('Transfered: ', '', $group);
                $lastPosition = $pos;
            }
        }

         //looking for all values after "Transfered:" without Date
         preg_match_all('/Transfered:\s*([^\d\n]+)/', $data, $matches);





        return $lastMatch;
    }
}
