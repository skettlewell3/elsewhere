<a
    href="{{ $href }}"
    data-name="{{ strtolower($slot) }}"
    class="navlink {{ $active ? 'active' : '' }}"
>
    {{ $slot }}
</a>