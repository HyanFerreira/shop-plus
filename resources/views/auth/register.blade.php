<x-guest-layout>
    <x-auth-shell title="Crie sua conta" description="Cadastre-se para começar a comprar." :split="true">
        <x-auth-feedback />
        <x-auth-form :action="route('register')">
            <div>
                <x-label for="name" value="Nome" />
                <x-input id="name" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" placeholder="Seu nome" aria-describedby="name-help{{ $errors->has('name') ? ' name-error' : '' }}" :aria-invalid="$errors->has('name') ? 'true' : 'false'" />
                <p id="name-help" class="ds-help">Use até 255 caracteres.</p>
                <x-input-error for="name" id="name-error" />
            </div>
            <div>
                <x-label for="email" value="E-mail" />
                <x-input id="email" type="email" name="email" :value="old('email')" required autocomplete="username" placeholder="seu@email.com" :aria-invalid="$errors->has('email') ? 'true' : 'false'" :aria-describedby="$errors->has('email') ? 'email-error' : null" />
                <x-input-error for="email" id="email-error" />
            </div>
            <x-auth-password autocomplete="new-password" help="Use de 8 a 128 caracteres." />
            <x-auth-password name="password_confirmation" label="Confirmar senha" autocomplete="new-password" />
            @if (Laravel\Jetstream\Jetstream::hasTermsAndPrivacyPolicyFeature())
                <div>
                    <label for="terms" class="auth-terms"><x-checkbox name="terms" id="terms" required :aria-invalid="$errors->has('terms') ? 'true' : 'false'" :aria-describedby="$errors->has('terms') ? 'terms-error' : null" /><span>Li e concordo com os <a target="_blank" rel="noopener" href="{{ route('terms.show') }}">Termos de Serviço</a> e a <a target="_blank" rel="noopener" href="{{ route('policy.show') }}">Política de Privacidade</a>.</span></label>
                    <x-input-error for="terms" id="terms-error" />
                </div>
            @endif
            <x-auth-submit pending="Criando conta…">Criar conta</x-auth-submit>
        </x-auth-form>
        <p class="auth-footer">Já tem uma conta? <a href="{{ route('login') }}" class="auth-link">Fazer login</a></p>
    </x-auth-shell>
</x-guest-layout>
