<?php

namespace App\Services;

use App\Models\Business;
use App\Models\Location;

class BusinessLocationResolver
{
    public function resolve(Business $business): array
    {
        if ($business->latitude !== null && $business->longitude !== null) {
            return [
                'latitude' => (float) $business->latitude,
                'longitude' => (float) $business->longitude,
                'source' => 'business',
                'location' => null,
            ];
        }

        $location = $business->location;

        while ($location) {

            if ($location->latitude !== null && $location->longitude !== null) {
                return [
                    'latitude' => (float) $location->latitude,
                    'longitude' => (float) $location->longitude,
                    'source' => 'location',
                    'location' => $location,
                ];
            }

            $location = $location->parent;
        }

        return [
            'latitude' => null,
            'longitude' => null,
            'source' => null,
            'location' => null,
        ];
    }
}