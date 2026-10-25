<x-account-shell title="Perfil e segurança" description="Gerencie seu perfil e os recursos de proteção disponíveis para a sua conta.">
    <div class="account-security">
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
    </div>
</x-account-shell>
