<?php

namespace App\Http\Middleware;

use App\Support\SecurityAudit;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->isAdmin()) {
            app(SecurityAudit::class)->record($request->user(), 'authorization.admin_denied');
            abort(Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
