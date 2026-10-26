@props(['product', 'icon' => 'heart'])
@php($favorited = (bool) ($product->is_favorited ?? false))
<div {{ $attributes->class(['favorite-control']) }}>
    @auth
        <form method="POST" action="{{ route('favorites.store', $product->id) }}"
            x-data="favoriteButton(@js(route('favorites.store', $product->id)), @js($favorited), @js($product->name))"
            @submit.prevent="submit"
            @favorite-changed.window="if ($event.detail.url === url) favorited = $event.detail.favorited">
            @csrf
            <input type="hidden" name="_method" value="{{ $favorited ? 'DELETE' : 'POST' }}" :value="favorited ? 'DELETE' : 'POST'">
            <button type="submit" class="favorite-button" aria-label="{{ $favorited ? 'Remover dos favoritos: ' : 'Adicionar aos favoritos: ' }}{{ $product->name }}"
                aria-pressed="{{ $favorited ? 'true' : 'false' }}" :aria-pressed="favorited.toString()" :aria-label="label" :disabled="busy">
                <x-icon :name="$icon" class="size-5" />
            </button>
            <span class="favorite-feedback" :class="{ 'favorite-feedback--error': hasError }" role="status" aria-atomic="true" x-text="message"></span>
        </form>
    @else
        <a href="{{ route('login') }}" class="favorite-button" aria-label="Entre para favoritar: {{ $product->name }}"><x-icon :name="$icon" class="size-5" /></a>
    @endauth
</div>
