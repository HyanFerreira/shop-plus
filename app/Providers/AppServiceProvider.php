<?php

namespace App\Providers;

use App\Models\User;
use App\Support\SecurityAudit;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('admin', fn ($request) => Limit::perMinute(60)->by((string) ($request->user()?->id ?? $request->ip())));

        Event::listen(Login::class, function (Login $event) {
            if ($event->user instanceof User) {
                app(SecurityAudit::class)->record($event->user, 'auth.login', $event->user);
            }
        });
        Event::listen(Logout::class, function (Logout $event) {
            if ($event->user instanceof User && User::query()->whereKey($event->user->id)->exists()) {
                app(SecurityAudit::class)->record($event->user, 'auth.logout', $event->user);
            } else {
                app(SecurityAudit::class)->record(null, 'auth.logout', null, ['account_deleted' => true]);
            }
        });
    }
}
