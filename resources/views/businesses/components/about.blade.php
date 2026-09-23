<section class="businessAbout">

    @if($business->description)
        <p class="businessAboutDescription">
            {{ $business->description }}
        </p>
    @endif

    @if($business->website_url)
        <a
            class="businessWebsiteLink"
            href="{{ $business->website_url }}"
            target="_blank"
            rel="noopener noreferrer"
        >
            Visit website
        </a>
    @endif

</section>