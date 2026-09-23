<?php

namespace App\Services;

use App\Models\Business;
use App\Models\BusinessLocation;
use App\Models\Location;

class BusinessLocationSlugService
{
    public function generate(
        Business $business,
        Location $location,
        ?BusinessLocation $ignore = null
    ): string {
        $canonicalResolver = app(CanonicalLocationResolver::class);

        $canonicalLocation = $canonicalResolver->resolve(
            (int) $location->id
        );

        /*
        |--------------------------------------------------------------------------
        | First choice: parent business slug
        |--------------------------------------------------------------------------
        |
        | Example:
        | solent-coffee-roasters
        |
        */

        $baseSlug = $business->slug;

        if (
            $this->isAvailable(
                $canonicalLocation->id,
                $baseSlug,
                $ignore
            )
        ) {
            return $baseSlug;
        }

        /*
        |--------------------------------------------------------------------------
        | Second choice: append precise location
        |--------------------------------------------------------------------------
        |
        | Example:
        | solent-coffee-roasters-portswood
        |
        */

        $locationSlug = $baseSlug . '-' . $location->slug;

        if (
            $this->isAvailable(
                $canonicalLocation->id,
                $locationSlug,
                $ignore
            )
        ) {
            return $locationSlug;
        }

        /*
        |--------------------------------------------------------------------------
        | Further collision: append incrementing number
        |--------------------------------------------------------------------------
        |
        | Example:
        | solent-coffee-roasters-portswood-2
        |
        */

        $number = 2;

        do {
            $candidate = $locationSlug . '-' . $number;

            $number++;
        } while (
            !$this->isAvailable(
                $canonicalLocation->id,
                $candidate,
                $ignore
            )
        );

        return $candidate;
    }

    private function isAvailable(
        int $canonicalLocationId,
        string $slug,
        ?BusinessLocation $ignore = null
    ): bool {
        $query = BusinessLocation::query()
            ->where(
                'canonical_location_id',
                $canonicalLocationId
            )
            ->where('slug', $slug);

        if ($ignore?->exists) {
            $query->whereKeyNot($ignore->id);
        }

        return !$query->exists();
    }
}