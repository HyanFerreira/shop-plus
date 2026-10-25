<x-guest-layout>
    <x-auth-shell title="Redefinir senha" description="Escolha uma nova senha para acessar sua conta." >
        <x-auth-feedback />
        <x-auth-form :action="route('password.update')">
            <input type="hidden" name="token" value="{{ $request->route('token') }}">
            <div>
                <x-label for="email" value="E-mail" />
                <x-input id="email" type="email" name="email" :value="old('email', $request->email)" required autofocus autocomplete="username" placeholder="seu@email.com" :aria-invalid="$errors->has('email') ? 'true' : 'false'" :aria-describedby="$errors->has('email') ? 'email-error' : null" />
                <x-input-error for="email" id="email-error" />
            </div>
            <x-auth-password label="Nova senha" autocomplete="new-password" help="Use de 8 a 128 caracteres." />
            <x-auth-password name="password_confirmation" label="Confirmar nova senha" autocomplete="new-password" />
            <x-auth-submit pending="Salvando senha…">Redefinir senha</x-auth-submit>
        </x-auth-form>
        <p class="auth-footer"><a href="{{ route('login') }}" class="auth-link">Voltar ao login</a></p>
    </x-auth-shell>
</x-guest-layout>
