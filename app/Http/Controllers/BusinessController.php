<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Location;
use Illuminate\View\View;

class BusinessController extends Controller
{
    public function show(Location $location, string $business): View
    {
        $business = Business::query()
            ->with([
                'location',
                'canonicalLocation',
            ])
            ->where('canonical_location_id', $location->id)
            ->where('slug', $business)
            ->firstOrFail();

        return view('businesses.show', [
            'business' => $business,
        ]);
    }
}