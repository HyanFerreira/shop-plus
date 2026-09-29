<?php

namespace App\Support;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class SecurityAudit
{
    /** @param array<string, bool|int|string|null> $metadata */
    public function record(?User $actor, string $event, ?Model $auditable = null, array $metadata = []): AuditLog
    {
        $request = app()->runningInConsole() ? null : request();
        $key = (string) config('app.key');

        return AuditLog::create([
            'actor_id' => $actor?->id,
            'event' => $event,
            'auditable_type' => $auditable?->getMorphClass(),
            'auditable_id' => $auditable?->getKey(),
            'metadata_encrypted' => $metadata ?: null,
            'ip_hash' => $request?->ip() ? hash_hmac('sha256', $request->ip(), $key) : null,
            'user_agent_hash' => $request?->userAgent() ? hash('sha256', $request->userAgent()) : null,
        ]);
    }
}
