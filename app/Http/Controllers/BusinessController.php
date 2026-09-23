<?php

namespace App\Http\Controllers;

use App\Enums\BusinessLocationRole;
use App\Models\Business;
use App\Models\BusinessLocation;
use App\Models\Location;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class BusinessController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Business / organisation page
    |--------------------------------------------------------------------------
    */

    public function showBusiness(string $business): View|RedirectResponse
    {
        $business = Business::query()
            ->with([
                'categories',
                'page',
                'locations' => function ($query) {
                    $query
                        ->where('is_active', true)
                        ->with([
                            'location',
                            'canonicalLocation',
                        ]);
                },
            ])
            ->where('slug', $business)
            ->where('is_active', true)
            ->firstOrFail();

        $businessLocations = $business->locations
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Single-location businesses
        |--------------------------------------------------------------------------
        |
        | A normal single-location business does not need a separate
        | organisation page. Its branch/location page is its public page.
        |
        | A head office is different: it can act as a hybrid organisation
        | and location page.
        |
        */

        if ($businessLocations->count() === 1) {
            $onlyLocation = $businessLocations->first();

            if (
                $onlyLocation->role !==
                BusinessLocationRole::HeadOffice
            ) {
                return redirect()->route(
                    'directory.businesses.show',
                    [
                        'location' =>
                            $onlyLocation->canonicalLocation->slug,

                        'business' =>
                            $onlyLocation->slug,
                    ]
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Head office
        |--------------------------------------------------------------------------
        */

        $headOffice = $businessLocations->first(
            fn (BusinessLocation $location) =>
                $location->role ===
                BusinessLocationRole::HeadOffice
        );

        /*
        |--------------------------------------------------------------------------
        | Page mode
        |--------------------------------------------------------------------------
        |
        | business = organisation page with branch/location gallery
        |
        | hybrid   = organisation page using the head office as its
        |            featured location context
        |
        */

        $pageMode = $headOffice
            ? 'hybrid'
            : 'business';

        return view('businesses.show', [
            'business' => $business,
            'businessLocation' => $headOffice,
            'businessLocations' => $businessLocations,
            'pageMode' => $pageMode,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Branch / location page
    |--------------------------------------------------------------------------
    */

    public function showBranch(
        Location $location,
        string $business
    ): View|RedirectResponse {
        $businessLocation = BusinessLocation::query()
            ->with([
                'location',
                'canonicalLocation',
                'business.categories',
                'business.page',
            ])
            ->where(
                'canonical_location_id',
                $location->id
            )
            ->where('slug', $business)
            ->where('is_active', true)
            ->whereHas('business', function ($query) {
                $query->where('is_active', true);
            })
            ->firstOrFail();

        $business = $businessLocation->business;

        /*
        |--------------------------------------------------------------------------
        | Head office URLs
        |--------------------------------------------------------------------------
        |
        | The head office is represented on the organisation page rather
        | than exposing a duplicate standalone public page.
        |
        */

        if (
            $businessLocation->role ===
            BusinessLocationRole::HeadOffice
        ) {
            return redirect()->route(
                'directory.businesses.overview',
                [
                    'business' => $business->slug,
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | All active locations
        |--------------------------------------------------------------------------
        |
        | Branch pages also receive the full location collection so they
        | can display the business's other branches/location gallery.
        |
        */

        $business->load([
            'locations' => function ($query) {
                $query
                    ->where('is_active', true)
                    ->with([
                        'location',
                        'canonicalLocation',
                    ]);
            },
        ]);

        return view('businesses.show', [
            'business' => $business,
            'businessLocation' => $businessLocation,
            'businessLocations' => $business->locations->values(),
            'pageMode' => 'branch',
        ]);
    }
}