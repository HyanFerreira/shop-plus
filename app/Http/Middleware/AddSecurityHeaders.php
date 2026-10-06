<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AddSecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');
        $response->headers->set('Cross-Origin-Resource-Policy', 'same-origin');
        $viteHttpSource = app()->environment('local') ? ' http://127.0.0.1:5173' : '';
        $viteConnectSources = app()->environment('local')
            ? ' http://127.0.0.1:5173 ws://127.0.0.1:5173'
            : '';

        $response->headers->set(
            'Content-Security-Policy',
            "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'{$viteHttpSource}; style-src 'self' 'unsafe-inline' https://fonts.bunny.net{$viteHttpSource}; font-src 'self' https://fonts.bunny.net data:; img-src 'self' data: blob:; connect-src 'self'{$viteConnectSources}; object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'"
        );

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
