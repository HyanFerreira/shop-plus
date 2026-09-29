<?php

namespace App\Actions\Admin;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use App\Support\SecurityAudit;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SetUserStatus
{
    public function execute(User $actor, User $target, UserStatus $status): User
    {
        if (! $actor->isAdmin()) {
            throw new AuthorizationException;
        }

        return DB::transaction(function () use ($actor, $target, $status) {
            $target = User::query()->lockForUpdate()->findOrFail($target->id);
            if ($actor->is($target) && $status === UserStatus::Disabled) {
                throw ValidationException::withMessages(['user' => 'Você não pode desativar sua própria conta.']);
            }
            if ($target->role === UserRole::Admin && $status === UserStatus::Disabled && User::query()->where('role', UserRole::Admin)->where('status', UserStatus::Active)->lockForUpdate()->count() <= 1) {
                throw ValidationException::withMessages(['user' => 'O último administrador ativo não pode ser desativado.']);
            }
            $target->forceFill(['status' => $status])->save();
            app(SecurityAudit::class)->record($actor, $status === UserStatus::Active ? 'user.enabled' : 'user.disabled', $target);

            return $target->refresh();
        }, 3);
    }
}
