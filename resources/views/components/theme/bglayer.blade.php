@php
    $theme = config('themes')[session('theme','default')];
@endphp

<div
    class="theme-background"
    @if($theme['background'])
        style="--background-image:url('{{ asset($theme['background']) }}')"
    @elseif($theme['background_style'])
        style="--background-image:{{ $theme['background_style'] }}"
    @endif
></div>

<div class="theme-overlay"></div>