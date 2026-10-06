@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center border-b-2 border-slate-900 px-1 pt-1 text-sm font-semibold leading-5 text-slate-900 transition focus:outline-none focus:border-slate-900'
            : 'inline-flex items-center border-b-2 border-transparent px-1 pt-1 text-sm font-medium leading-5 text-slate-500 transition hover:border-slate-300 hover:text-slate-900 focus:border-slate-400 focus:text-slate-900 focus:outline-none';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
