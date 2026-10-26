<x-guest-layout>
    @php
        $fieldErrors = ['email' => ''];
        $generalErrors = [];
        foreach ($errors->getMessages() as $field => $messages) {
            foreach ($messages as $message) {
                if ($field === 'email' && $message !== trans('passwords.throttled')) {
                    if ($fieldErrors['email'] === '') {
                        $fieldErrors['email'] = $message;
                    }
                } else {
                    $generalErrors[] = $message;
                }
            }
        }
    @endphp
    <x-auth-shell title="Recuperar senha" description="Digite seu e-mail para receber o link de redefinição de senha." :recovery="true">
        <x-slot:heading>Recuperar <span>senha</span></x-slot:heading>
        @if ($generalErrors)
            <x-alert variant="danger" id="recovery-general-error" class="auth-feedback auth-login-alert">
                <x-login-icon name="circle-alert" />
                <div>@foreach (array_unique($generalErrors) as $message)<p>{{ $message }}</p>@endforeach</div>
            </x-alert>
        @endif
        @session('status')
            <x-alert variant="success" id="recovery-status" class="auth-feedback auth-login-alert"><x-icon name="check" /><span>{{ $value }}</span></x-alert>
        @endsession
        <x-auth-form :action="route('password.email')" data-auth-validation="recovery" :data-auth-errors="json_encode($fieldErrors)">
            <span class="sr-only" data-auth-announcement aria-live="polite" aria-atomic="true"></span>
            <div>
                <x-label for="email" value="E-mail" />
                <div class="auth-login-field">
                    <x-icon name="envelope" class="auth-login-field-icon" />
                    <x-input id="email" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" placeholder="seu@email.com" maxlength="255" data-auth-field="email" :aria-invalid="$fieldErrors['email'] !== '' ? 'true' : 'false'" :aria-describedby="$fieldErrors['email'] !== '' ? 'email-error' : null" />
                </div>
                <x-auth-field-error name="email" :message="$fieldErrors['email']" />
            </div>
            <x-auth-submit pending="Enviando…"><x-icon name="envelope" />Enviar link</x-auth-submit>
        </x-auth-form>
        <p class="auth-footer"><span>Lembrou da senha?</span><a href="{{ route('login') }}" class="auth-link"><x-icon name="arrow-left" class="auth-recovery-back-icon" />Fazer login</a></p>
    </x-auth-shell>
</x-guest-layout>
