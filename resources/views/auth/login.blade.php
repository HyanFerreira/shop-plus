<x-guest-layout>
    <x-auth-shell title="Bem-vindo de volta" description="Faça login para continuar." :split="true">
        <x-auth-feedback />
        <x-auth-form :action="route('login')">
            <div>
                <x-label for="email" value="E-mail" />
                <x-input id="email" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" placeholder="seu@email.com" :aria-invalid="$errors->has('email') ? 'true' : 'false'" :aria-describedby="$errors->has('email') ? 'email-error' : null" />
                <x-input-error for="email" id="email-error" />
            </div>
            <x-auth-password />
            <div class="auth-options">
                <label for="remember_me" class="auth-remember"><x-checkbox id="remember_me" name="remember" />Lembrar de mim</label>
                @if (Route::has('password.request'))<a href="{{ route('password.request') }}" class="auth-link">Esqueceu sua senha?</a>@endif
            </div>
            <x-auth-submit pending="Entrando…">Entrar</x-auth-submit>
        </x-auth-form>
        @if (Route::has('register'))<p class="auth-footer">Não tem uma conta? <a href="{{ route('register') }}" class="auth-link">Cadastre-se</a></p>@endif
    </x-auth-shell>
</x-guest-layout>
