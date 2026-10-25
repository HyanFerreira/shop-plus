<form method="POST" {{ $attributes->class(['auth-form']) }} x-data="{ submitting: false }" x-on:submit="submitting = true" x-on:pageshow.window="submitting = false" x-bind:aria-busy="submitting">
    @csrf
    {{ $slot }}
</form>
