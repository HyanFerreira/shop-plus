@props(['name' => 'password', 'label' => 'Senha', 'autocomplete' => 'current-password', 'help' => null, 'icons' => false, 'error' => null])
@php($fieldError = $error ?? $errors->first($name))
<div x-data="{ visible: false }">
    <x-label :for="$name" :value="$label" />
    <div class="auth-password">
        @if ($icons)<x-icon name="lock-closed" class="auth-login-field-icon" />@endif
        <x-input :id="$name" :name="$name" type="password" x-bind:type="visible ? 'text' : 'password'" :autocomplete="$autocomplete" required
            :aria-invalid="$fieldError !== '' ? 'true' : 'false'"
            :aria-describedby="trim(($help ? $name.'-help ' : '').($fieldError !== '' ? $name.'-error' : '')) ?: null" {{ $attributes }} />
        <button type="button" class="auth-password__toggle" x-cloak x-on:click="visible = !visible" x-bind:aria-pressed="visible" x-bind:aria-label="(visible ? 'Ocultar senha: ' : 'Mostrar senha: ') + @js($label)" aria-controls="{{ $name }}" aria-label="{{ 'Mostrar senha: '.$label }}">
            @if ($icons)
                <span x-show="!visible"><x-login-icon name="eye-slash" /></span><span x-show="visible"><x-login-icon name="eye" /></span>
            @else
                <span x-show="!visible">Mostrar</span><span x-show="visible">Ocultar</span>
            @endif
        </button>
    </div>
    @if ($help)<p id="{{ $name }}-help" class="ds-help">{{ $help }}</p>@endif
    @if ($icons)
        <x-auth-field-error :name="$name" :message="$fieldError" />
    @else
        <x-input-error :for="$name" :id="$name.'-error'" />
    @endif
</div>
