@props(['for'])

@error($for)
    <p {{ $attributes->class(['ds-error']) }}>{{ $message }}</p>
@enderror
