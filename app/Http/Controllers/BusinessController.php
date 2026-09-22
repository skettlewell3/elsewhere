<?php

namespace App\Http\Controllers;

use App\Models\BusinessLocation;
use App\Models\Location;
use Illuminate\View\View;

class BusinessController extends Controller
{
    public function show(Location $location, string $business): View
    {
        $businessLocation = BusinessLocation::query()
            ->with([
                'location',
                'canonicalLocation',
                'business.categories',
                'business.page',
            ])
            ->where('canonical_location_id', $location->id)
            ->where('is_active', true)
            ->whereHas('business', function ($query) use ($business) {
                $query
                    ->where('slug', $business)
                    ->where('is_active', true);
            })
            ->firstOrFail();

        return view('businesses.show', [
            'business' => $businessLocation->business,
            'businessLocation' => $businessLocation,
        ]);
    }
}