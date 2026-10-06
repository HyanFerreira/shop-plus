@props(['type' => 'button'])

<x-button variant="success" :type="$type" {{ $attributes }}>{{ $slot }}</x-button>
