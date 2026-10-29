@props(['id', 'model', 'options', 'value', 'labelledBy' => null, 'label' => null])

@php
    $labelId = $labelledBy ?? $id.'-label';
    $selectedValue = array_key_exists($value, $options) ? $value : array_key_first($options);
@endphp

<div {{ $attributes->class(['catalog-dropdown']) }} x-data="{
    open: false,
    show(index = null) {
        this.open = true;
        this.$nextTick(() => {
            const options = Array.from(this.$refs.options.querySelectorAll('button'));
            (index === null ? this.$refs.options.querySelector('[aria-checked=true]') ?? options[0] : options.at(index))?.focus();
        });
    },
    move(step) {
        const options = Array.from(this.$refs.options.querySelectorAll('button'));
        options[(options.indexOf(document.activeElement) + step + options.length) % options.length].focus();
    }
}" @click.outside="open = false"
    @focusout="if (!$el.contains($event.relatedTarget)) open = false"
    @keydown.escape.stop.prevent="if (open) { open = false; $refs.trigger.focus() }">
    @if($label)<span id="{{ $labelId }}" class="sr-only">{{ $label }}</span>@endif
    <x-button id="{{ $id }}-trigger" x-ref="trigger" type="button" variant="secondary" @click="open ? (open = false) : show()"
        @keydown.arrow-down.prevent="show()" @keydown.arrow-up.prevent="show(-1)"
        aria-haspopup="menu" aria-expanded="false" x-bind:aria-expanded="open.toString()" aria-controls="{{ $id }}-options" aria-labelledby="{{ $labelId }} {{ $id }}-value">
        <span id="{{ $id }}-value">{{ $options[$selectedValue] ?? '' }}</span><x-icon name="chevron-down" class="size-5" />
    </x-button>
    <div id="{{ $id }}-options" class="catalog-dropdown__options" x-ref="options" x-cloak x-show="open" x-transition.opacity.duration.150ms
        role="menu" aria-labelledby="{{ $labelId }}"
        @keydown.arrow-down.stop.prevent="move(1)" @keydown.arrow-up.stop.prevent="move(-1)"
        @keydown.home.prevent="$refs.options.firstElementChild.focus()" @keydown.end.prevent="$refs.options.lastElementChild.focus()">
        @foreach($options as $optionValue => $optionLabel)
            <button type="button" role="menuitemradio" tabindex="-1" wire:click="$set('{{ $model }}', {{ Illuminate\Support\Js::from($optionValue) }})"
                @click="open = false; $refs.trigger.focus()" aria-checked="{{ (string) $selectedValue === (string) $optionValue ? 'true' : 'false' }}">{{ $optionLabel }}</button>
        @endforeach
    </div>
</div>
