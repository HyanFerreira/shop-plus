<x-guest-layout>
    <x-auth-shell title="Verificação em duas etapas" description="Confirme o acesso à sua conta." >
        <div x-data="{ recovery: false }">
            <p id="code-help" class="auth-security-copy" x-show="! recovery">Digite o código fornecido pelo seu aplicativo autenticador.</p>
            <p id="recovery-code-help" class="auth-security-copy" x-cloak x-show="recovery">Digite um dos seus códigos de recuperação de emergência.</p>
            <x-auth-feedback />
            <x-auth-form :action="route('two-factor.login')">
                <div x-show="! recovery">
                    <x-label for="code" value="Código de autenticação" />
                    <x-input id="code" type="text" inputmode="numeric" name="code" autofocus x-ref="code" autocomplete="one-time-code" aria-describedby="code-help{{ $errors->has('code') ? ' code-error' : '' }}" :aria-invalid="$errors->has('code') ? 'true' : 'false'" />
                    <x-input-error for="code" id="code-error" />
                </div>
                <div x-cloak x-show="recovery">
                    <x-label for="recovery_code" value="Código de recuperação" />
                    <x-input id="recovery_code" type="text" name="recovery_code" x-ref="recovery_code" autocomplete="one-time-code" aria-describedby="recovery-code-help{{ $errors->has('recovery_code') ? ' recovery-code-error' : '' }}" :aria-invalid="$errors->has('recovery_code') ? 'true' : 'false'" />
                    <x-input-error for="recovery_code" id="recovery-code-error" />
                </div>
                <x-auth-submit pending="Verificando…">Entrar</x-auth-submit>
                <button type="button" class="auth-link" x-show="! recovery" x-on:click="recovery = true; $nextTick(() => { $refs.recovery_code.focus() })">Usar código de recuperação</button>
                <button type="button" class="auth-link" x-cloak x-show="recovery" x-on:click="recovery = false; $nextTick(() => { $refs.code.focus() })">Usar código do autenticador</button>
            </x-auth-form>
        </div>
    </x-auth-shell>
</x-guest-layout>
