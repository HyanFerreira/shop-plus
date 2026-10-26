<x-guest-layout>
    @php
        $fieldErrors = ['email' => '', 'password' => ''];
        $generalErrors = [];
        $credentialError = false;
        $throttlePattern = str_replace([':seconds', ':minutes'], '*', trans('auth.throttle'));
        foreach ($errors->getMessages() as $field => $messages) {
            foreach ($messages as $message) {
                if ($message === trans('auth.failed')) {
                    $credentialError = true;
                } elseif (\Illuminate\Support\Str::is($throttlePattern, $message) || ! array_key_exists($field, $fieldErrors)) {
                    $generalErrors[] = $message;
                } elseif ($fieldErrors[$field] === '') {
                    $fieldErrors[$field] = $message;
                }
            }
        }
        if ($credentialError) {
            array_unshift($generalErrors, 'Não foi possível entrar. Verifique suas credenciais e tente novamente.');
        }
    @endphp
    <x-auth-shell title="Bem-vindo de volta!" description="Faça login para continuar acessando sua conta na ShopPlus." :split="true" :login="true">
        <x-slot:heading>Bem-vindo de <span>volta!</span></x-slot:heading>
        <x-slot:intro>Faça login para continuar acessando sua conta<br class="auth-login-intro-break"> na <strong>ShopPlus.</strong></x-slot:intro>
        @if ($generalErrors)
            <x-alert variant="danger" id="login-general-error" class="auth-feedback auth-login-alert" :data-login-credentials="$credentialError ? 'true' : null">
                <x-login-icon name="circle-alert" />
                <div>@foreach (array_unique($generalErrors) as $message)<p>{{ $message }}</p>@endforeach</div>
            </x-alert>
        @endif
        @session('status')<x-alert variant="success" class="auth-feedback">{{ $value }}</x-alert>@endsession
        <x-auth-form :action="route('login')" data-auth-validation="login" :data-auth-errors="json_encode($fieldErrors)">
            <span class="sr-only" data-auth-announcement aria-live="polite" aria-atomic="true"></span>
            <div>
                <x-label for="email" value="E-mail" />
                <div class="auth-login-field">
                    <x-icon name="envelope" class="auth-login-field-icon" />
                    <x-input id="email" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" placeholder="seu@email.com" data-auth-field="email" :aria-invalid="$fieldErrors['email'] !== '' ? 'true' : 'false'" :aria-describedby="$fieldErrors['email'] !== '' ? 'email-error' : null" />
                </div>
                <x-auth-field-error name="email" :message="$fieldErrors['email']" />
            </div>
            <div x-data="{ visible: false }">
                <x-label for="password" value="Senha" />
                <div class="auth-password">
                    <x-icon name="lock-closed" class="auth-login-field-icon" />
                    <x-input id="password" name="password" type="password" x-bind:type="visible ? 'text' : 'password'" autocomplete="current-password" required placeholder="Sua senha" data-auth-field="password" :aria-invalid="$fieldErrors['password'] !== '' ? 'true' : 'false'" :aria-describedby="$fieldErrors['password'] !== '' ? 'password-error' : null" />
                    <button type="button" class="auth-password__toggle" x-cloak x-on:click="visible = !visible" x-bind:aria-pressed="visible" x-bind:aria-label="visible ? 'Ocultar senha: Senha' : 'Mostrar senha: Senha'" aria-controls="password" aria-label="Mostrar senha: Senha">
                        <span x-show="!visible"><x-login-icon name="eye-slash" /></span><span x-show="visible"><x-login-icon name="eye" /></span>
                    </button>
                </div>
                <x-auth-field-error name="password" :message="$fieldErrors['password']" />
            </div>
            <div class="auth-options">
                <label for="remember_me" class="auth-remember"><x-checkbox id="remember_me" name="remember" />Lembrar de mim</label>
                @if (Route::has('password.request'))<a href="{{ route('password.request') }}" class="auth-link">Esqueceu sua senha?</a>@endif
            </div>
            <x-auth-submit pending="Entrando…"><x-login-icon name="enter" />Entrar</x-auth-submit>
        </x-auth-form>
        @if (Route::has('register'))<p class="auth-footer"><x-login-icon name="user-plus" /><span>Não tem uma conta?</span> <a href="{{ route('register') }}" class="auth-link">Cadastre-se</a></p>@endif
    </x-auth-shell>
</x-guest-layout>
