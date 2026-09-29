<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryItem extends Model
{
    use HasFactory;

    protected $fillable = ['product_id', 'on_hand', 'reserved', 'minimum_level'];

    protected function casts(): array
    {
        return ['on_hand' => 'integer', 'reserved' => 'integer', 'minimum_level' => 'integer'];
    }

    public function available(): int
    {
        return $this->on_hand - $this->reserved;
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }
}
