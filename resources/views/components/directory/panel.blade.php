@props([
    'businesses',
    'categories',
    'countryOptions',
    'areaOptions',
    'localityOptions',
    'selectedCountry',
    'selectedArea',
    'selectedLocality',
])

<aside
    class="directoryPanel"
    data-directory-panel
    data-panel-state="expanded"
    data-panel-view="results"
>

    <x-directory.controls />

    <x-directory.filters :categories="$categories" />

    <div class="directorySummary">

        <span
            class="directoryResultCount"
            data-directory-count
        >
            {{ $businesses->count() }} businesses
        </span>

        <span class="directorySort">
            Sort by: <strong>A–Z</strong>
        </span>

    </div>

    <div
        class="directoryAdvancedFilters"
        data-directory-advanced-panel
    >

        <div class="directoryAdvancedFilterHeader">
            <strong>Filters</strong>

            <div class="directoryAdvancedFilterActions">
                <button
                    type="button"
                    class="directoryResetFilters"
                    data-directory-reset-filters
                >
                    Reset filters
                </button>

                <button
                    type="button"
                    data-directory-show-results
                >
                    Show results
                </button>
            </div>
        </div>

        <div class="directoryAdvancedResultSummary">        
            <span
                class="directoryAdvancedResultCount"
                data-directory-count
            >
                {{ $businesses->count() }} businesses
            </span>
        </div>

        <div class="directoryAdvancedFilterSection">
            <span class="directoryAdvancedFilterLabel">
                Location
            </span>

            <div class="directoryLocationFilters">        
                <label class="directoryLocationFilter">
                    <span>Country</span>

                    <select
                        data-directory-country
                    >
                        @foreach ($countryOptions as $option)

                            <option
                                value="{{ $option['slug'] }}"
                                data-id="{{ $option['id'] }}"
                                data-type="{{ $option['type'] }}"
                                @selected(
                                    $selectedCountry->slug === $option['slug']
                                )
                            >
                                @if ($option['type'] === 'nation')
                                    &nbsp;&nbsp;— {{ $option['name'] }}
                                @else
                                    {{ $option['name'] }}
                                @endif
                            </option>                            
                        @endforeach
                    </select>                            
                </label>
                                
                <label class="directoryLocationFilter">                            
                    <span>Area</span>                            
                    <select
                        data-directory-area
                    >
                        <option value="">
                            All areas
                        </option>
                                
                        @foreach ($areaOptions as $area)
                                
                            <option
                                value="{{ $area->slug }}"
                                data-id="{{ $area->id }}"
                                @selected(
                                    $selectedArea?->id === $area->id
                                )
                            >
                                {{ $area->name }}
                            </option>                            
                        @endforeach
                    </select>                            
                </label>
                                
                <label class="directoryLocationFilter">                            
                    <span>Locality</span>                            
                    <select
                        data-directory-locality
                    >
                        <option value="">
                            All localities
                        </option>
                                
                        @foreach ($localityOptions as $locality)
                                
                            <option
                                value="{{ $locality->slug }}"
                                data-id="{{ $locality->id }}"
                                @selected(
                                    $selectedLocality?->id === $locality->id
                                )
                            >
                                {{ $locality->name }}
                            </option>                            
                        @endforeach
                    </select>                            
                </label>
                                
                <div class="directoryLocationAction">                                
                    <span>
                        Apply
                    </span>
                                
                    <button
                        type="button"
                        class="directoryLocationApply"
                        data-directory-location-apply
                        disabled
                    >
                        Update location
                    </button>                                
                </div>                                
            </div>
        </div>
        

        <div class="directoryAdvancedFilterSection">

            <span class="directoryAdvancedFilterLabel">
                Categories
            </span>

            <div class="directoryAdvancedCategoryList">

                @foreach ($categories as $category)

                    <label class="directoryAdvancedCategory">

                        <input
                            type="checkbox"
                            value="{{ $category->slug }}"
                            data-directory-advanced-category
                        >

                        <span>
                            {{ $category->name }}
                        </span>

                    </label>

                @endforeach

            </div>

        </div>

        <div class="directoryAdvancedFilterSection">
            <label class="directoryRewardFilter">
                <input
                    type="checkbox"
                    data-directory-rewards-filter
                >

                <span>
                    Rewards available
                </span>
            </label>
        </div>
    </div>

    <x-directory.list :businesses="$businesses" />
</aside>