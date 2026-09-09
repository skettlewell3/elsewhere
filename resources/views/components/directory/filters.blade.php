@props([
    'categories'
])

<div class="directoryFilters">
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
</div>