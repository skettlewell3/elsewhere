<?php

namespace App\Http\Controllers;

use App\Enums\LocationType;
use App\Models\BusinessLocation;
use App\Models\BusinessCategory;
use App\Models\Country;
use App\Models\Location;
use App\Services\BusinessLocationResolver;
use App\Services\LocationScopeService;
use Illuminate\Http\Request;

class DirectoryController extends Controller
{
    public function index(
        Request $request,
        BusinessLocationResolver $resolver,
        LocationScopeService $locationScope
    ) {
        $defaultCountry = $this->resolveDefaultCountry($request);

        $selectedCountry = $this->resolveSelectedCountry(
            $request,
            $defaultCountry
        );

        $selectedArea = $this->resolveSelectedLocation(
            $request->query('area'),
            'area',
            $selectedCountry,
            $locationScope
        );

        $selectedLocality = $this->resolveSelectedLocation(
            $request->query('locality'),
            'locality',
            $selectedArea ?? $selectedCountry,
            $locationScope
        );

        $activeLocationScope =
            $selectedLocality
            ?? $selectedArea
            ?? $selectedCountry;
            
        $mapScope = [
            'name' => $activeLocationScope->name,
            'slug' => $activeLocationScope->slug,
            'latitude' => $activeLocationScope->latitude,
            'longitude' => $activeLocationScope->longitude,
            'zoom' => $activeLocationScope->map_zoom,
        ];

        $businessLocationQuery = BusinessLocation::query()
            ->where('is_active', true)
            ->whereHas('business', function ($query) {
                $query->where('is_active', true);
            })
            ->with([
                'location',
                'canonicalLocation',
                'business.categories' => function ($query) {
                    $query
                        ->where('is_active', true)
                        ->orderBy('sort_order');
                },
                'business.page',
            ]);
        
        /*
        |--------------------------------------------------------------------------
        | Location scope
        |--------------------------------------------------------------------------
        |
        | Lowest active filter wins:
        |
        | locality
        | area
        | country/nation
        |
        */
        
        if ($selectedLocality) {
            $locationIds = $locationScope
                ->descendantIds($selectedLocality);
        
            $businessLocationQuery->whereIn(
                'location_id',
                $locationIds
            );
        } elseif ($selectedArea) {
            $locationIds = $locationScope
                ->descendantIds($selectedArea);
        
            $businessLocationQuery->whereIn(
                'location_id',
                $locationIds
            );
        } elseif ($selectedCountry instanceof Location) {
            /*
             * A nation such as England.
             */
            $locationIds = $locationScope
                ->descendantIds($selectedCountry);
        
            $businessLocationQuery->whereIn(
                'location_id',
                $locationIds
            );
        } elseif ($selectedCountry instanceof Country) {
            /*
             * A true country such as United Kingdom.
             */
            $businessLocationQuery->whereHas(
                'location',
                function ($query) use ($selectedCountry) {
                    $query->where(
                        'country_id',
                        $selectedCountry->id
                    );
                }
            );
        }

        $businesses = $businessLocationQuery
            ->get()
            ->sortBy(fn ($businessLocation) =>
                $businessLocation->business->name
            )
            ->values()
            ->map(function ($businessLocation) use ($resolver) {
                $business = $businessLocation->business;
            
                $resolved = $resolver->resolve(
                    $businessLocation
                );
            
                return [
                    /*
                     * The map entry now represents a branch/location,
                     * not the abstract business.
                     */
                    'id' => $businessLocation->id,
                    'business_id' => $business->id,
                
                    'name' => $business->name,
                    'slug' => $business->slug,
                    'branch_name' => $businessLocation->name,
                
                    'description' => $business->description,
                    'website_url' => $business->website_url,
                
                    'latitude' => $resolved['latitude'],
                    'longitude' => $resolved['longitude'],
                    'coordinate_source' => $resolved['source'],
                    'location' => $resolved['location'],
                
                    'canonical_location' => [
                        'id' => $businessLocation->canonicalLocation->id,
                        'name' => $businessLocation->canonicalLocation->name,
                        'slug' => $businessLocation->canonicalLocation->slug,
                    ],
                
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
                        
                    'is_primary' =>
                        $businessLocation->is_primary,
                        
                    'is_mobile' =>
                        $businessLocation->is_mobile,
                ];
            });

        $categories = BusinessCategory::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $countryOptions = $this->countryOptions();

        $areaOptions = $this->areaOptions(
            $selectedCountry
        );

        $localityOptions = $this->localityOptions(
            $selectedArea,
            $selectedCountry,
            $locationScope
        );

        $locationFilterData = Location::query()
            ->orderBy('name')
            ->get()
            ->map(fn ($location) => [
                'id' => $location->id,
                'name' => $location->name,
                'slug' => $location->slug,
                'type' => $location->type->value,
                'filter_group' => $location->type->filterGroup(),
                'parent_id' => $location->parent_id,
                'country_id' => $location->country_id,
            ])
            ->values()
        ;

        return view(
            'pages.directory',
            compact(
                'businesses',
                'categories',
                'countryOptions',
                'areaOptions',
                'locationFilterData',
                'localityOptions',
                'selectedCountry',
                'selectedArea',
                'selectedLocality',
                'mapScope'
            )
        );
    }

    private function resolveDefaultCountry(
        Request $request
    ): Country {
        $host = $request->getHost();

        /*
         * For now:
         *
         * elsewhere.uk.com
         * *.elsewhere.uk.com
         *
         * both resolve to United Kingdom.
         */

        if (
            $host === 'elsewhere.uk.com'
            || str_ends_with($host, '.elsewhere.uk.com')
            || in_array($host, ['localhost', '127.0.0.1'])
        ) {
            return Country::where(
                'slug',
                'united-kingdom'
            )->firstOrFail();
        }

        /*
         * Temporary fallback while UK is the only
         * configured Elsewhere country.
         */
        return Country::firstOrFail();
    }

    private function resolveSelectedCountry(
        Request $request,
        Country $defaultCountry
    ): Country|Location {
        $selected = $request->query('country');

        if (!$selected) {
            return $defaultCountry;
        }

        /*
         * First try a real country.
         */
        $country = Country::where(
            'slug',
            $selected
        )->first();

        if ($country) {
            return $country;
        }

        /*
         * Then allow nation-level locations such
         * as England, Scotland, Wales or NI.
         */
        $nation = Location::query()
            ->where('slug', $selected)
            ->where(
                'type',
                LocationType::Nation->value
            )
            ->first();

        return $nation ?? $defaultCountry;
    }

    private function resolveSelectedLocation(
        ?string $slug,
        string $filterGroup,
        Country|Location $parentScope,
        LocationScopeService $locationScope
    ): ?Location {
        if (!$slug) {
            return null;
        }
    
        $location = Location::query()
            ->where('slug', $slug)
            ->first();
    
        if (!$location) {
            return null;
        }
    
        if ($location->type->filterGroup() !== $filterGroup) {
            return null;
        }
    
        /*
         * If the parent scope is a full country,
         * the location simply needs to belong to it.
         */
        if ($parentScope instanceof Country) {
            return $location->country_id === $parentScope->id
                ? $location
                : null;
        }
    
        /*
         * If the parent scope is another location,
         * this location must exist somewhere beneath it.
         */
        $scopeIds = $locationScope->descendantIds($parentScope);
    
        return $scopeIds->contains($location->id)
            ? $location
            : null;
    }

    private function countryOptions()
    {
        $countries = Country::query()
            ->orderBy('name')
            ->get();

        $nations = Location::query()
            ->where(
                'type',
                LocationType::Nation->value
            )
            ->orderBy('name')
            ->get();

        return collect()
            ->concat(
                $countries->map(fn ($country) => [
                    'type' => 'country',
                    'id' => $country->id,
                    'name' => $country->name,
                    'slug' => $country->slug,
                    'country_id' => $country->id,
                    'parent_id' => null,
                ])
            )
            ->concat(
                $nations->map(fn ($nation) => [
                    'type' => 'nation',
                    'id' => $nation->id,
                    'name' => $nation->name,
                    'slug' => $nation->slug,
                    'country_id' => $nation->country_id,
                    'parent_id' => $nation->parent_id,
                ])
            )
            ->values();
    }

    private function areaOptions(
        Country|Location $countryScope
    ) {
        $countryId = $countryScope instanceof Country
            ? $countryScope->id
            : $countryScope->country_id;

        $query = Location::query()
            ->where(
                'country_id',
                $countryId
            )
            ->orderBy('name');

        return $query
            ->get()
            ->filter(
                fn ($location) =>
                    $location->type->filterGroup()
                    === 'area'
            )
            ->filter(function ($location) use (
                $countryScope
            ) {
                if ($countryScope instanceof Country) {
                    return true;
                }

                return $this->isDescendantOf(
                    $location,
                    $countryScope
                );
            })
            ->values();
    }

    private function localityOptions(
        ?Location $selectedArea,
        Country|Location $countryScope,
        LocationScopeService $locationScope
    ) {
        /*
         * If an area is selected, only localities
         * beneath that area should appear.
         */
        if ($selectedArea) {
            $ids = $locationScope
                ->descendantIds($selectedArea);

            return Location::query()
                ->whereIn('id', $ids)
                ->orderBy('name')
                ->get()
                ->filter(
                    fn ($location) =>
                        $location->type->filterGroup()
                        === 'locality'
                )
                ->values();
        }

        /*
         * Otherwise show all localities available
         * within the selected country/nation scope.
         */
        $countryId = $countryScope instanceof Country
            ? $countryScope->id
            : $countryScope->country_id;

        $locations = Location::query()
            ->where(
                'country_id',
                $countryId
            )
            ->orderBy('name')
            ->get()
            ->filter(
                fn ($location) =>
                    $location->type->filterGroup()
                    === 'locality'
            );

        if ($countryScope instanceof Country) {
            return $locations->values();
        }

        return $locations
            ->filter(
                fn ($location) =>
                    $this->isDescendantOf(
                        $location,
                        $countryScope
                    )
            )
            ->values();
    }

    private function isDescendantOf(
        Location $location,
        Location $ancestor
    ): bool {
        $current = $location;

        while ($current) {
            if ($current->id === $ancestor->id) {
                return true;
            }

            $current = $current->parent;
        }

        return false;
    }
}