<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = ['order_id', 'amount_cents', 'status', 'token', 'brand', 'last_four', 'authorization_code', 'idempotency_key', 'processed_at'];

    protected $hidden = ['token', 'idempotency_key'];

    protected function casts(): array
    {
        return ['amount_cents' => 'integer', 'status' => PaymentStatus::class, 'processed_at' => 'immutable_datetime'];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
