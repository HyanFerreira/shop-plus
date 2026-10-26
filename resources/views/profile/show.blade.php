<x-account-shell title="Perfil" description="Gerencie seus dados pessoais, endereços e a segurança da sua conta.">
    <nav class="account-profile-nav" aria-label="Seções do perfil">
        <a href="#dados-pessoais" class="ds-button ds-button--secondary ds-button--md">Dados pessoais</a>
        <a href="#enderecos" class="ds-button ds-button--secondary ds-button--md">Endereços</a>
        <a href="#seguranca" class="ds-button ds-button--secondary ds-button--md">Segurança</a>
    </nav>
    <livewire:personal-data.manager />
    <section id="seguranca" class="account-security" aria-labelledby="security-title" tabindex="-1">
        <h2 id="security-title">Segurança</h2>
            @if (Laravel\Fortify\Features::canUpdateProfileInformation())
                @livewire('profile.update-profile-information-form')


            @endif

            @if (Laravel\Fortify\Features::enabled(Laravel\Fortify\Features::updatePasswords()))
                <div class="account-security-section">
                    @livewire('profile.update-password-form')
                </div>


            @endif

            @if (Laravel\Fortify\Features::canManageTwoFactorAuthentication())
                <div class="account-security-section">
                    @livewire('profile.two-factor-authentication-form')
                </div>


            @endif

            <div class="account-security-section">
                @livewire('profile.logout-other-browser-sessions-form')
            </div>

            @if (Laravel\Jetstream\Jetstream::hasAccountDeletionFeatures())


                <div class="account-security-section">
                    @livewire('profile.delete-user-form')
                </div>
            @endif
    </section>
</x-account-shell>
