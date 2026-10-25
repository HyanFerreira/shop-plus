<x-guest-layout>
    <x-auth-shell title="Recuperar senha" description="Digite seu e-mail para receber o link de redefinição de senha." :illustration="true">
        <x-auth-feedback />
        <x-auth-form :action="route('password.email')">
            <div>
                <x-label for="email" value="E-mail" />
                <x-input id="email" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" placeholder="seu@email.com" :aria-invalid="$errors->has('email') ? 'true' : 'false'" :aria-describedby="$errors->has('email') ? 'email-error' : null" />
                <x-input-error for="email" id="email-error" />
            </div>
            <x-auth-submit>Enviar link</x-auth-submit>
        </x-auth-form>
        <p class="auth-footer">Lembrou da senha? <a href="{{ route('login') }}" class="auth-link">Fazer login</a></p>
    </x-auth-shell>
</x-guest-layout>
