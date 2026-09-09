@props([
    'businesses',
    'categories',
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

            <button
                type="button"
                data-directory-show-results
            >
                Show results
            </button>
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

        <button
            type="button"
            class="directoryClearFilters"
            data-directory-clear-filters
        >
            Clear filters
        </button>

    </div>

    <x-directory.list :businesses="$businesses" />

</aside>