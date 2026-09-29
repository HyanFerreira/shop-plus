<?php

namespace App\Models;

use App\Enums\ShipmentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shipment extends Model
{
    use HasFactory;

    protected $fillable = ['order_id', 'method', 'price_cents', 'estimated_days', 'status', 'tracking_code', 'shipped_at', 'delivered_at'];

    protected function casts(): array
    {
        return ['price_cents' => 'integer', 'estimated_days' => 'integer', 'status' => ShipmentStatus::class, 'shipped_at' => 'immutable_datetime', 'delivered_at' => 'immutable_datetime'];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(ShipmentEvent::class)->oldest();
    }
}
