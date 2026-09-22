<?php

namespace App\Services;

use App\Models\Location;

class CanonicalLocationResolver
{
    public function resolve(Location|int $location): Location
    {
        if (is_int($location)) {
            $location = Location::findOrFail($location);
        }

        $hierarchy = collect();
        $current = $location;

        while ($current) {
            $hierarchy->push($current);
            $current = $current->parent;
        }

        $city = $hierarchy->first(
            fn (Location $location) => $this->type($location) === 'city'
        );

        if ($city) {
            return $city;
        }

        $town = $hierarchy->first(
            fn (Location $location) => $this->type($location) === 'town'
        );

        if ($town) {
            return $town;
        }

        $county = $hierarchy->first(
            fn (Location $location) => $this->type($location) === 'county'
        );

        if ($county) {
            return $county;
        }

        return $location;
    }

    private function type(Location $location): string
    {
        return $location->type instanceof \BackedEnum
            ? $location->type->value
            : (string) $location->type;
    }
}