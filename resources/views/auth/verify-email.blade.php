<x-guest-layout>
    <x-auth-shell title="Verifique seu e-mail" description="Confirme seu endereço pelo link enviado para o seu e-mail." >
        @if (session('status') == 'verification-link-sent')
            <x-alert variant="success" class="auth-feedback">Enviamos um novo link de verificação para o endereço informado no seu perfil.</x-alert>
        @endif
        @if ($errors->any())
            <x-alert variant="danger" class="auth-feedback"><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></x-alert>
        @endif
        <p class="auth-security-copy">Se não recebeu a mensagem, solicite um novo link ou confira o e-mail no seu perfil.</p>
        <div class="auth-actions">
            <x-auth-form :action="route('verification.send')"><x-auth-submit>Reenviar e-mail de verificação</x-auth-submit></x-auth-form>
            <div class="auth-actions__links">
                <a href="{{ route('profile.show') }}" class="auth-link">Editar perfil</a>
                <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="auth-link">Sair da conta</button></form>
            </div>
        </div>
    </x-auth-shell>
</x-guest-layout>
