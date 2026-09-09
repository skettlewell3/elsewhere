@props([
    'businesses'
])

<aside
    class="directoryPanel"
    data-directory-panel
    data-panel-state="expanded"
>

    <x-directory.controls />

    <x-directory.filters />

    <div class="directorySummary">
        <strong>Directory</strong>

        <span
            class="directoryResultCount"
            data-directory-count
        >
            {{ $businesses->count() }} businesses
        </span>
    </div>

    <x-directory.list :businesses="$businesses" />

</aside>