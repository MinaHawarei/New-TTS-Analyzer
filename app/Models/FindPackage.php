<?php

namespace App\Models;

class FindPackage
{

    public static array $packages = [
        1 => [
            'unified_name' => 'Super Speed (140)',
            'names' => [
                'super 1 speed (140 gb)',
                'super 2 speed (140 gb)',
                'super 3 speed (140 gb)',
            ],
            'quota' => 140,
            'new_price' => 210,
            'old_price' => 160,
        ],
        2 => [
            'unified_name' => 'Super Speed (200)',
            'names' => [
                'super 1 speed (200 gb)',
                'super 2 speed (200 gb)',
                'super 3 speed (200 gb)',
            ],
            'quota' => 200,
            'new_price' => 290,
            'old_price' => 225,
        ],
        3 => [
            'unified_name' => 'Super Speed (250)',
            'names' => [
                'super 1 speed (250 gb)',
                'super 2 speed (250 gb)',
                'super 3 speed (250 gb)',
            ],
            'quota' => 250,
            'new_price' => 360,
            'old_price' => 280,
        ],
        4 => [
            'unified_name' => 'Super Speed (400)',
            'names' => [
                'super 1 speed (400 gb)',
                'super 2 speed (400 gb)',
                'super 3 speed (400 gb)',
            ],
            'quota' => 400,
            'new_price' => 570,
            'old_price' => 440,
        ],
        5 => [
            'unified_name' => 'Super Speed (600)',
            'names' => [
                'super 1 speed (600 gb)',
                'super 2 speed (600 gb)',
                'super 3 speed (600 gb)',
            ],
            'quota' => 600,
            'new_price' => 850,
            'old_price' => 650,
        ],
        6 => [
            'unified_name' => 'Super Speed (1 TB)',
            'names' => [
                'super 1 speed 1 tb - 1 month',
                'super 2 speed 1 tb - 1 month',
                'super 3 speed 1 tb - 1 month',
            ],
            'quota' => 1024,
            'new_price' => 1360,
            'old_price' => 1050,
        ],
        7 => [
            'unified_name' => 'Mega Speed (250)',
            'names' => [
                'mega 1 speed (250 gb)',
                'mega 2 speed (250 gb)',
            ],
            'quota' => 250,
            'new_price' => 530,
            'old_price' => 410,
        ],
        8 => [
            'unified_name' => 'Mega Speed (600)',
            'names' => [
                'mega 1 speed (600 gb)',
                'mega 2 speed (600 gb)',
            ],
            'quota' => 600,
            'new_price' => 1040,
            'old_price' => 800,
        ],
        9 => [
            'unified_name' => 'Mega Speed (1 TB)',
            'names' => [
                'mega 1 speed 1 tb - 1 month',
                'mega 2 speed 1 tb - 1 month',
            ],
            'quota' => 1024,
            'new_price' => 1560,
            'old_price' => 1200,
        ],
        10 => [
            'unified_name' => 'Ultra Speed (250)',
            'names' => [
                'ultra 1 speed (250 gb)',
                'ultra 2 speed (250 gb)',
            ],
            'quota' => 250,
            'new_price' => 700,
            'old_price' => 540,
        ],
        11 => [
            'unified_name' => 'Ultra Speed (600)',
            'names' => [
                'ultra 1 speed (600 gb)',
                'ultra 2 speed (600 gb)',
            ],
            'quota' => 600,
            'new_price' => 1230,
            'old_price' => 950,
        ],
        12 => [
            'unified_name' => 'Max Speed (1 TB)',
            'names' => [
                'max speed fh',
                'max speed fv',
                'max vl (1 tera)',
                'max speed fh - postpaid',
                'max speed fv - postpaid',
                'max vl (1 tera) - postpaid',
            ],
            'quota' => 1024,
            'new_price' => 1760,
            'old_price' => 1350,
        ],
        13 => [
            'unified_name' => 'Super Speed (3000 GB) Annual',
            'names' => [
                'super 1 speed (3000 gb) - 12 months',
                'super 2 speed (3000 gb) - 12 months',
                'super 3 speed (3000 gb) - 12 months',
            ],
            'quota' => 3000,
            'new_price' => 3960,
            'old_price' => 3220,
        ],
        14 => [
            'unified_name' => 'Super Speed (4800 GB) Annual',
            'names' => [
                'super 1 speed (4800 gb) - 12 months',
                'super 2 speed (4800 gb) - 12 months',
                'super 3 speed (4800 gb) - 12 months',
            ],
            'quota' => 4800,
            'new_price' => 6270,
            'old_price' => 5060,
        ],
        15 => [
            'unified_name' => 'Super Speed (7200 GB) Annual',
            'names' => [
                'super 1 speed (7200 gb) - 12 months',
                'super 2 speed (7200 gb) - 12 months',
                'super 3 speed (7200 gb) - 12 months',
            ],
            'quota' => 7200,
            'new_price' => 8925,
            'old_price' => 7150,
        ],
        16 => [
            'unified_name' => 'Super Speed (12 TB) Annual',
            'names' => [
                'super 1 speed (12 tb) - 12 months',
                'super 2 speed (12 tb) - 12 months',
                'super 3 speed (12 tb) - 12 months',
            ],
            'quota' => 12288,
            'new_price' => 13600,
            'old_price' => 10500,
        ],
        17 => [
            'unified_name' => 'Mega Speed (3000 GB) Annual',
            'names' => [
                'mega 1 speed (3000 gb) - 12 months',
                'mega 2 speed (3000 gb) - 12 months',
            ],
            'quota' => 3000,
            'new_price' => 5830,
            'old_price' => 4715,
        ],
        18 => [
            'unified_name' => 'Mega Speed (7200 GB) Annual',
            'names' => [
                'mega 1 speed (7200 gb) - 12 months',
                'mega 2 speed (7200 gb) - 12 months',
            ],
            'quota' => 7200,
            'new_price' => 10920,
            'old_price' => 8800,
        ],
        19 => [
            'unified_name' => 'Mega Speed (12 TB) Annual',
            'names' => [
                'mega 1 speed (12 tb) - 12 months',
                'mega 2 speed (12 tb) - 12 months',
            ],
            'quota' => 12288,
            'new_price' => 15600,
            'old_price' => 12000,
        ],
        20 => [
            'unified_name' => 'Ultra Speed (3000 GB) Annual',
            'names' => [
                'ultra 1 speed (3000 gb) - 12 months',
                'ultra 2 speed (3000 gb) - 12 months',
            ],
            'quota' => 3000,
            'new_price' => 7700,
            'old_price' => 6210,
        ],
        21 => [
            'unified_name' => 'Ultra Speed (7200 GB) Annual',
            'names' => [
                'ultra 1 speed (7200 gb) - 12 months',
                'ultra 2 speed (7200 gb) - 12 months',
            ],
            'quota' => 7200,
            'new_price' => 12915,
            'old_price' => 10450,
        ],
        22 => [
            'unified_name' => 'Max Speed (12 TB) Annual',
            'names' => [
                'max speed sv-(12 tb)',
                'max speed ftth-(12 tb)',
            ],
            'quota' => 12288,
            'new_price' => 17600,
            'old_price' => 13500,
        ],
        23 => [
            'unified_name' => 'WE SA7EL (1 TB) Annual',
            'names' => [
                'we space sa7el 1-(1tb)',
                'we space sahel 1-(1tb)',
                'we space sa7el 2-(1tb)',
                'we space sahel 2-(1tb)',
                'we space sa7el 3-(1tb)',
                'we space sahel 3-(1tb)',
            ],
            'quota' => 1025,
            'new_price' => 1000,
            'old_price' => 1000,
        ],
    ];
    public static function getPackage($name)
    {
        $name = strtolower(trim($name));

        foreach (self::$packages as $id => $data) {
            $namesLower = array_map('strtolower', $data['names']);
            if (in_array($name, $namesLower)) {
                return ['id' => $id] + $data;
            }
        }

        return null;
    }
    public static function getPackageById($id)
    {
        if (!isset(self::$packages[$id])) {
            return null;
        }
        return ['id' => $id] + self::$packages[$id];
    }
    public static function allPackages()
    {
        $packages = [];

        foreach (self::$packages as $id => $data) {
            $displayName = $data['unified_name'];

            $packages[] = (object)[
                'id'   => $id,
                'name' => ucfirst($displayName),
            ];
        }

        return collect($packages);
}

}
