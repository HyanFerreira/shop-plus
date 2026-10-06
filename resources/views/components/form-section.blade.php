@props(['submit'])

<div {{ $attributes->merge(['class' => 'md:grid md:grid-cols-3 md:gap-6']) }}>
    <x-section-title>
        <x-slot name="title">{{ $title }}</x-slot>
        <x-slot name="description">{{ $description }}</x-slot>
    </x-section-title>

    <div class="mt-5 md:mt-0 md:col-span-2">
        <form wire:submit="{{ $submit }}">
            <div class="border border-slate-200 bg-white px-4 py-5 shadow-sm {{ isset($actions) ? 'rounded-t-xl sm:rounded-t-2xl' : 'rounded-xl sm:rounded-2xl' }} sm:p-6">
                <div class="grid grid-cols-6 gap-6">
                    {{ $form }}
                </div>
            </div>

            @if (isset($actions))
                <div class="flex items-center justify-end rounded-b-xl border border-t-0 border-slate-200 bg-slate-50 px-4 py-3 text-end shadow-sm sm:rounded-b-2xl sm:px-6">
                    {{ $actions }}
                </div>
            @endif
        </form>
    </div>
</div>
