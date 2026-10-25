<?php

namespace App\Http\Middleware;

use App\Support\SecurityAudit;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class ThrottleSensitiveAuthRequests
{
    public function handle(Request $request, Closure $next): Response
    {
        $route = $request->route()?->getName();
        if (! $request->isMethodSafe() && in_array($route, ['login.store', 'register.store', 'password.email', 'password.update', 'password.confirm.store'], true)) {
            // Validate before Fortify's normalization, limiter keys and password hashing.
            $request->validate([
                'email' => ['sometimes', 'required', 'string', 'email', 'max:255'],
                'password' => ['sometimes', 'required', 'string', 'max:128'],
                'password_confirmation' => ['sometimes', 'required', 'string', 'max:128'],
                'token' => ['sometimes', 'required', 'string', 'max:255'],
            ]);
            $ipKey = 'auth-ip:'.$route.':'.$request->ip();
            abort_if(RateLimiter::tooManyAttempts($ipKey, 30), 429);
            RateLimiter::hit($ipKey, 60);
        }

        $limits = ['register.store' => 10, 'password.email' => 5, 'password.update' => 5];
        if ($request->isMethodSafe() || ! isset($limits[$route])) {
            return $next($request);
        }

        $identifier = strtolower((string) $request->input('email'));
        $key = 'sensitive-auth:'.$route.':'.hash('sha256', $identifier.'|'.$request->ip());
        abort_if(RateLimiter::tooManyAttempts($key, $limits[$route]), 429, 'Muitas tentativas. Aguarde antes de tentar novamente.');
        RateLimiter::hit($key, 60);
        if ($route === 'password.email') {
            app(SecurityAudit::class)->record(null, 'auth.password_reset_requested', null, ['identifier_hash' => hash('sha256', $identifier)]);
        }

        return $next($request);
    }
}
