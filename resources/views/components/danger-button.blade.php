@props(['type' => 'button'])

<x-button variant="danger" :type="$type" {{ $attributes }}>{{ $slot }}</x-button>
