<section class="businessAbout">

    @if($businessPage)

        @if($businessPage->short_description)
            <p class="businessAboutSummary">
                {{ $businessPage->short_description }}
            </p>
        @endif

        @if($businessPage->about)
            <div class="businessAboutContent">

                <h2>
                    About
                </h2>

                <p>
                    {!! nl2br(e($businessPage->about)) !!}
                </p>

            </div>
        @endif

    @elseif($business->description)

        <p class="businessAboutDescription">
            {{ $business->description }}
        </p>

    @endif

</section>