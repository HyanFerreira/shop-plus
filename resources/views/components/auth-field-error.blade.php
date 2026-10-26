@props(['name', 'message' => ''])
<p id="{{ $name }}-error" class="ds-error auth-login-error" data-auth-error="{{ $name }}" @if ($message === '') hidden @endif>
    <x-login-icon name="circle-alert" />
    <span data-auth-error-text>{{ $message }}</span>
</p>
