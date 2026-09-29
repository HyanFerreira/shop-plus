<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['actor_id', 'event', 'auditable_type', 'auditable_id', 'metadata_encrypted', 'ip_hash', 'user_agent_hash'];

    protected $hidden = ['metadata_encrypted', 'ip_hash', 'user_agent_hash'];

    protected function casts(): array
    {
        return ['metadata_encrypted' => 'encrypted:array', 'created_at' => 'immutable_datetime'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('Audit logs are immutable.'));
        static::deleting(fn () => throw new \LogicException('Audit logs are immutable.'));
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }
}
