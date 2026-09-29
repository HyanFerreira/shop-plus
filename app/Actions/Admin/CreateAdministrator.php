<?php

namespace App\Actions\Admin;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use App\Support\SecurityAudit;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class CreateAdministrator
{
    public function execute(User $actor, array $data): User
    {
        if (! $actor->isAdmin()) {
            throw new AuthorizationException;
        }
        $validated = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'confirmed', Password::min(12)->mixedCase()->numbers()->symbols()],
        ])->validate();

        $user = new User;
        $user->forceFill(['name' => trim($validated['name']), 'email' => strtolower($validated['email']), 'password' => Hash::make($validated['password']), 'role' => UserRole::Admin, 'status' => UserStatus::Active, 'email_verified_at' => now()])->save();
        app(SecurityAudit::class)->record($actor, 'admin.created', $user);

        return $user;
    }
}
