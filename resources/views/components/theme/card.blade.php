@php
    $background = $theme['background'];
@endphp

<button
    name="theme"
    value="{{ $key }}"
    class="theme-option"
    @if($background)
        style="background-image:url('{{ asset($background) }}')"
    @endif
>
    {{ $theme['name'] }}
</button>