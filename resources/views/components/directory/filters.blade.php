@props([
    'categories'
])

<div class="directoryFilters">

    <div class="directoryFilterRow">

        <div class="directoryCategories">

            <button
                type="button"
                class="directoryCategory active"
                data-directory-category="all"
            >
                All
            </button>

            @foreach ($categories as $category)

                <button
                    type="button"
                    class="directoryCategory"
                    data-directory-category="{{ $category->slug }}"
                >
                    {{ $category->name }}
                </button>

            @endforeach

        </div>

        <button
            type="button"
            class="directoryAdvancedFilterButton"
            data-directory-advanced-filters
            aria-label="Open advanced filters"
            title="Advanced filters"
        >
            <svg
                viewBox="0 0 24 24"
                aria-hidden="true"
            >
                <path
                    d="M4 4h10v16H4z"
                />

                <path
                    d="M14 12h6"
                />

                <path
                    d="M17 9l3 3-3 3"
                />
            </svg>
        </button>
    </div>
</div>