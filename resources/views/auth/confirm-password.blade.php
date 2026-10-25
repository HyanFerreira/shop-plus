<x-guest-layout>
    <x-auth-shell title="Confirme sua senha" description="Esta é uma área segura. Confirme sua senha para continuar." >
        <x-auth-feedback />
        <x-auth-form :action="route('password.confirm')">
            <x-auth-password autofocus />
            <x-auth-submit pending="Confirmando…">Confirmar senha</x-auth-submit>
        </x-auth-form>
    </x-auth-shell>
</x-guest-layout>
