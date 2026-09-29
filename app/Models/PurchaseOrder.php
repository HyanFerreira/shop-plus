<?php

namespace App\Models;

use App\Enums\PurchaseOrderStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrder extends Model
{
    use HasFactory;

    protected $fillable = ['supplier_id', 'public_number', 'status', 'total_cents', 'placed_at', 'cancelled_at', 'received_at'];

    protected function casts(): array
    {
        return ['status' => PurchaseOrderStatus::class, 'total_cents' => 'integer', 'placed_at' => 'immutable_datetime', 'cancelled_at' => 'immutable_datetime', 'received_at' => 'immutable_datetime'];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }
}
