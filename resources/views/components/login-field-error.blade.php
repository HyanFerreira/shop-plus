@props(['name', 'message' => ''])
<p id="{{ $name }}-error" class="ds-error auth-login-error" data-login-error="{{ $name }}" @if ($message === '') hidden @endif>
    <x-login-icon name="circle-alert" />
    <span data-login-error-text>{{ $message }}</span>
</p>
