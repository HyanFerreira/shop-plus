<x-guest-layout>
    @php
        $fieldErrors = collect(['name', 'email', 'password', 'password_confirmation'])
            ->mapWithKeys(fn ($name) => [$name => $errors->first($name)])->all();
        // Fortify associates confirmed errors with password; present them at confirmation.
        $confirmationMessage = \Illuminate\Support\Facades\Validator::make(
            ['password' => 'a', 'password_confirmation' => 'b'], ['password' => 'confirmed']
        )->errors()->first('password');
        if ($fieldErrors['password'] === $confirmationMessage) {
            $fieldErrors['password_confirmation'] = $fieldErrors['password_confirmation'] ?: $fieldErrors['password'];
            $fieldErrors['password'] = '';
        }
    @endphp
    <x-auth-shell title="Crie sua conta" description="Cadastre-se para começar a comprar." :split="true" :branded="true">
        <x-slot:heading>Crie sua <span>conta</span></x-slot:heading>
        @foreach ($errors->getMessages() as $field => $messages)
            @if (! array_key_exists($field, $fieldErrors) && $field !== 'terms')
                <x-alert variant="danger" class="auth-feedback auth-login-alert"><x-login-icon name="circle-alert" /><div>@foreach ($messages as $message)<p>{{ $message }}</p>@endforeach</div></x-alert>
            @endif
        @endforeach
        @session('status')<x-alert variant="success" class="auth-feedback">{{ $value }}</x-alert>@endsession
        <x-auth-form :action="route('register')" data-auth-validation="register" :data-auth-errors="json_encode($fieldErrors)">
            <span class="sr-only" data-auth-announcement aria-live="polite" aria-atomic="true"></span>
            <div>
                <x-label for="name" value="Nome" />
                <div class="auth-login-field">
                    <x-icon name="user" class="auth-login-field-icon" />
                    <x-input id="name" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" placeholder="Seu nome" maxlength="255" data-auth-field="name" :aria-invalid="$fieldErrors['name'] !== '' ? 'true' : 'false'" :aria-describedby="$fieldErrors['name'] !== '' ? 'name-error' : null" />
                </div>
                <x-auth-field-error name="name" :message="$fieldErrors['name']" />
            </div>
            <div>
                <x-label for="email" value="E-mail" />
                <div class="auth-login-field">
                    <x-icon name="envelope" class="auth-login-field-icon" />
                    <x-input id="email" type="email" name="email" :value="old('email')" required autocomplete="username" placeholder="seu@email.com" maxlength="255" data-auth-field="email" :aria-invalid="$fieldErrors['email'] !== '' ? 'true' : 'false'" :aria-describedby="$fieldErrors['email'] !== '' ? 'email-error' : null" />
                </div>
                <x-auth-field-error name="email" :message="$fieldErrors['email']" />
            </div>
            <x-auth-password autocomplete="new-password" placeholder="Sua senha" minlength="8" maxlength="128" :icons="true" :error="$fieldErrors['password']" data-auth-field="password" />
            <x-auth-password name="password_confirmation" label="Confirmar senha" autocomplete="new-password" placeholder="Confirme sua senha" maxlength="128" :icons="true" :error="$fieldErrors['password_confirmation']" data-auth-field="password_confirmation" />
            <aside class="auth-password-requirements" aria-labelledby="password-requirements-title">
                <p id="password-requirements-title">Sua senha deve conter:</p>
                <ul>
                    @foreach ([
                        'length' => 'Entre 8 e 128 caracteres.',
                        'lowercase' => 'Uma letra minúscula.',
                        'uppercase' => 'Uma letra maiúscula.',
                        'symbols' => 'Um caractere especial.',
                        'confirmation' => 'As duas senhas devem coincidir.',
                    ] as $requirement => $text)
                        <li data-password-requirement="{{ $requirement }}" data-met="false">
                            <span class="auth-password-requirements__icon"><x-icon name="check" /></span>
                            <span>{{ $text }}<span class="sr-only" data-requirement-status> Pendente.</span></span>
                        </li>
                    @endforeach
                </ul>
            </aside>
            @if (Laravel\Jetstream\Jetstream::hasTermsAndPrivacyPolicyFeature())
                <div>
                    <label for="terms" class="auth-terms"><x-checkbox name="terms" id="terms" required :aria-invalid="$errors->has('terms') ? 'true' : 'false'" :aria-describedby="$errors->has('terms') ? 'terms-error' : null" /><span>Li e concordo com os <a target="_blank" rel="noopener" href="{{ route('terms.show') }}">Termos de Serviço</a> e a <a target="_blank" rel="noopener" href="{{ route('policy.show') }}">Política de Privacidade</a>.</span></label>
                    <x-input-error for="terms" id="terms-error" />
                </div>
            @endif
            <x-auth-submit pending="Criando conta…"><x-login-icon name="user-plus" />Criar conta</x-auth-submit>
        </x-auth-form>
        <p class="auth-footer"><span>Já tem uma conta?</span> <a href="{{ route('login') }}" class="auth-link">Fazer login</a></p>
    </x-auth-shell>
</x-guest-layout>
