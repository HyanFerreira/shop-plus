@props(['title', 'description' => ''])
<x-app-layout>
    <div class="account-page"><div class="ds-storefront-container account-container"><div class="customer-layout">
        <div class="account-content">
            <div class="account-heading"><p class="account-eyebrow">Minha conta</p><h1>{{ $title }}</h1>@if($description)<p>{{ $description }}</p>@endif</div>
            {{ $slot }}
        </div>
    </div></div></div>
</x-app-layout>
