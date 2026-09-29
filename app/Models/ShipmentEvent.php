<?php

namespace App\Models;

use App\Enums\ShipmentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShipmentEvent extends Model
{
    use HasFactory;

    protected $fillable = ['from_status', 'to_status', 'note', 'actor_id'];

    protected function casts(): array
    {
        return ['from_status' => ShipmentStatus::class, 'to_status' => ShipmentStatus::class];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('Shipment events are immutable.'));
        static::deleting(fn () => throw new \LogicException('Shipment events are immutable.'));
    }

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
