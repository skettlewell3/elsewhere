<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\BusinessCategory;
use App\Services\BusinessLocationResolver;

class DirectoryController extends Controller
{
    public function index(BusinessLocationResolver $resolver)
    {
        $businesses = Business::where('is_active', true)
            ->with([
                'location',
                'categories' => function ($query) {
                    $query
                        ->where('is_active', true)
                        ->orderBy('sort_order');
                },
            ])
            ->orderBy('name')
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

                    'categories' => $business->categories
                        ->map(fn ($category) => [
                            'id' => $category->id,
                            'name' => $category->name,
                            'slug' => $category->slug,
                            'colour_key' => $category->colour_key,
                        ])
                        ->values(),

                    'offers_ep_redemption' =>
                        $business->offers_ep_redemption,
                ];
            });

        $categories = BusinessCategory::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return view(
            'pages.directory',
            compact(
                'businesses',
                'categories'
            )
        );
    }
}