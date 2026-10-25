<x-account-shell :title="'Olá, '.Str::before(Auth::user()->name, ' ')" description="Bem-vindo ao ShopPlus. Acompanhe suas compras e cuide da sua conta.">
    <livewire:account.overview />
</x-account-shell>