@props(['title', 'description', 'split' => false, 'illustration' => false, 'login' => false, 'branded' => false, 'recovery' => false])
<main @class(['auth-page', 'auth-page--login' => $login, 'auth-page--branded' => $login || $branded || $recovery, 'auth-page--recovery' => $recovery, 'auth-page--register' => $branded && ! $login])>
    <section @class(['auth-card', 'auth-card--split' => $split]) aria-labelledby="auth-title">
        @if ($split)<x-auth-benefits-panel />@endif
        @if ($recovery)
            <header class="auth-recovery-header"><x-shop-logo :accented="true" /></header>
        @endif
        <div class="auth-content">
            @unless ($recovery)
                <div @class(['auth-logo', 'auth-logo--mobile' => $split])><x-shop-logo :accented="$login || $branded" /></div>
            @endunless
            @if ($recovery)
                <img class="auth-recovery-illustration" src="{{ asset('images/forgout-password.png') }}" width="1536" height="1024" alt="" aria-hidden="true" loading="eager" fetchpriority="high" decoding="sync">
            @elseif ($illustration)
                <x-auth-illustration kind="recovery" />
            @endif
            <div class="auth-heading">
                <h1 id="auth-title">{{ $heading ?? $title }}</h1>
                <p>{{ $intro ?? $description }}</p>
            </div>
            {{ $slot }}
        </div>
    </section>
</main>
