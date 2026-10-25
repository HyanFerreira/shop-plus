@props(['title', 'description', 'split' => false, 'illustration' => false])
<main class="auth-page">
    <section @class(['auth-card', 'auth-card--split' => $split]) aria-labelledby="auth-title">
        @if ($split)<x-auth-benefits-panel />@endif
        <div class="auth-content">
            <div @class(['auth-logo', 'auth-logo--mobile' => $split])><x-shop-logo /></div>
            @if ($illustration)<x-auth-illustration kind="recovery" />@endif
            <div class="auth-heading">
                <h1 id="auth-title">{{ $title }}</h1>
                <p>{{ $description }}</p>
            </div>
            {{ $slot }}
        </div>
    </section>
</main>
