<?php
namespace App\Helpers;

class LocationHelpers
{
    public static function haversineDistance($location1, $location2)
    {
        // Radius of the Earth in meters.
        $earthRadius = 6371000;

        $lat1 = deg2rad($location1[0]);
        $lon1 = deg2rad($location1[1]);
        $lat2 = deg2rad($location2[0]);
        $lon2 = deg2rad($location2[1]);

        $dlat = $lat2 - $lat1;
        $dlon = $lon2 - $lon1;

        $a = sin($dlat / 2) * sin($dlat / 2) + cos($lat1) * cos($lat2) * sin($dlon / 2) * sin($dlon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        $distance = $earthRadius * $c;

        return $distance;
    }
}