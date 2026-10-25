<x-account-shell title="Meus dados" description="Organize sua identificação, seus telefones e seus endereços.">
    <section class="account-identity ds-panel" aria-label="Identificação da conta"><span class="customer-navigation__avatar" aria-hidden="true">{{ mb_substr(Auth::user()->name, 0, 1) }}</span><div><h2>{{ Auth::user()->name }}</h2><p>{{ Auth::user()->email }}</p><a href="{{ route('profile.show') }}" class="account-text-link">Editar nome e e-mail →</a></div></section>
    <livewire:personal-data.manager />
</x-account-shell>