<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Services\BusinessLocationResolver;

class DirectoryController extends Controller
{
    public function index(BusinessLocationResolver $resolver)
    {
        $businesses = Business::where('is_active', true)
            ->with('location')
            ->get()
            ->map(function ($business) use ($resolver) {

                $resolved = $resolver->resolve($business);

                return [
                    'id' => $business->id,
                    'name' => $business->name,
                    'slug' => $business->slug,
                    'description' => $business->description,
                    'website_url' => $business->website_url,
                    'latitude' => $resolved['latitude'],
                    'longitude' => $resolved['longitude'],
                    'coordinate_source' => $resolved['source'],
                    'location' => $resolved['location'],
                ];
            });

        return view('pages.directory', compact('businesses'));
    }
}