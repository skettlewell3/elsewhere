<?php

namespace App\Services;

use App\Models\BusinessLocation;

class BusinessLocationResolver
{
    public function resolve(BusinessLocation $businessLocation): array
    {
        if (
            $businessLocation->latitude !== null
            && $businessLocation->longitude !== null
        ) {
            return [
                'latitude' => (float) $businessLocation->latitude,
                'longitude' => (float) $businessLocation->longitude,
                'source' => 'business_location',
                'location' => $businessLocation->location,
            ];
        }

        $location = $businessLocation->location;

        while ($location) {
            if (
                $location->latitude !== null
                && $location->longitude !== null
            ) {
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