@php
    $mode = session('mode', 'dark');
    $nextMode = $mode === 'dark' ? 'light' : 'dark';
@endphp

<form method="POST" action="/mode" class="mode-toggle">
    @csrf

    <button
        name="mode"
        value="{{ $nextMode }}"
        class="mode-toggle-button"
        aria-label="Switch to {{ $nextMode }} mode"
        title="Switch to {{ $nextMode }} mode"
    >
        {{ $mode === 'dark' ? '☀️' : '🌙' }}
    </button>
</form>