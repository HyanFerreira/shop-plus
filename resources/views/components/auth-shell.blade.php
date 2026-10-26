@props(['title', 'description', 'split' => false, 'illustration' => false, 'login' => false])
<main @class(['auth-page', 'auth-page--login' => $login])>
    <section @class(['auth-card', 'auth-card--split' => $split]) aria-labelledby="auth-title">
        @if ($split)<x-auth-benefits-panel :detailed="$login" />@endif
        <div class="auth-content">
            <div @class(['auth-logo', 'auth-logo--mobile' => $split])><x-shop-logo :accented="$login" /></div>
            @if ($illustration)<x-auth-illustration kind="recovery" />@endif
            <div class="auth-heading">
                <h1 id="auth-title">{{ $heading ?? $title }}</h1>
                <p>{{ $intro ?? $description }}</p>
            </div>
            {{ $slot }}
        </div>
    </section>
</main>
