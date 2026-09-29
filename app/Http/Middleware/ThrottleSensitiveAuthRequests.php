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
        $limits = ['register.store' => 10, 'password.email' => 5, 'password.update' => 5];
        $route = $request->route()?->getName();
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
