<x-app-layout>
    <div class="cart-page"><div class="ds-storefront-container cart-container">
        <a href="{{ route('catalog.index') }}" class="cart-back-link"><x-arrow direction="left" />Voltar para a loja</a>
        <nav class="cart-breadcrumb" aria-label="Caminho de navegação"><a href="{{ route('home') }}">Início</a><span aria-hidden="true">›</span><span aria-current="page">Carrinho</span></nav>
        <div class="cart-heading"><h1>Meu carrinho</h1><p>Confira seus produtos e as quantidades antes de continuar.</p></div>
        <livewire:cart-manager />
    </div></div>
</x-app-layout>
