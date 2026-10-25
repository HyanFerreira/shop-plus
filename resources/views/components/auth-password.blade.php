@props(['name' => 'password', 'label' => 'Senha', 'autocomplete' => 'current-password', 'help' => null])
<div x-data="{ visible: false }">
    <x-label :for="$name" :value="$label" />
    <div class="auth-password">
        <x-input :id="$name" :name="$name" type="password" x-bind:type="visible ? 'text' : 'password'" :autocomplete="$autocomplete" required
            :aria-invalid="$errors->has($name) ? 'true' : 'false'"
            :aria-describedby="trim(($help ? $name.'-help ' : '').($errors->has($name) ? $name.'-error' : '')) ?: null" {{ $attributes }} />
        <button type="button" class="auth-password__toggle" x-cloak x-on:click="visible = !visible" x-bind:aria-pressed="visible" x-bind:aria-label="(visible ? 'Ocultar senha: ' : 'Mostrar senha: ') + @js($label)" aria-controls="{{ $name }}" aria-label="{{ 'Mostrar senha: '.$label }}">
            <span x-show="!visible">Mostrar</span><span x-show="visible">Ocultar</span>
        </button>
    </div>
    @if ($help)<p id="{{ $name }}-help" class="ds-help">{{ $help }}</p>@endif
    <x-input-error :for="$name" :id="$name.'-error'" />
</div>
