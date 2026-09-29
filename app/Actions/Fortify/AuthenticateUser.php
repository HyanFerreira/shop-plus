<?php

namespace App\Actions\Fortify;

use App\Models\User;
use App\Support\SecurityAudit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;

class AuthenticateUser
{
    public function __invoke(Request $request): ?User
    {
        $username = Fortify::username();
        $value = Str::lower((string) $request->input($username));
        $user = User::query()->where($username, $value)->first();

        if (! $user || ! Hash::check((string) $request->input('password'), $user->password)) {
            app(SecurityAudit::class)->record(null, 'auth.failed', null, ['identifier_hash' => hash('sha256', $value)]);

            return null;
        }

        if (! $user->isActive()) {
            app(SecurityAudit::class)->record($user, 'auth.blocked', $user);

            return null;
        }

        return $user;
    }
}
