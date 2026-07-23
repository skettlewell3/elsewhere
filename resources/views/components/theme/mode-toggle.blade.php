<form method="POST" action="/mode" class="mode-toggle">

    @csrf

    <button
        name="mode"
        value="light"
    >
        ☀️
    </button>

    <button
        name="mode"
        value="dark"
    >
        🌙
    </button>

</form>