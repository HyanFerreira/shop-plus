@props(['pending' => 'Enviando…'])
<x-button size="lg" class="auth-submit" x-bind:disabled="submitting">
    <span x-show="!submitting">{{ $slot }}</span>
    <span x-cloak x-show="submitting" role="status">{{ $pending }}</span>
</x-button>
