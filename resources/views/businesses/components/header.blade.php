<header class="businessHeader">

    <h1 class="businessHeaderName">
        {{ $business->name }}
    </h1>

    @if($pageMode === 'branch' && $businessLocation)
        <p class="businessHeaderContext">
            {{ $businessLocation->name }}
        </p>
    @elseif($pageMode === 'hybrid')
        <p class="businessHeaderContext">
            Head Office
        </p>
    @endif

</header>