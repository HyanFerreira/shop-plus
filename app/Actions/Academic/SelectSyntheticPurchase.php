<?php

namespace App\Actions\Academic;

use App\Enums\UserStatus;
use App\Models\Product;
use App\Models\User;

class SelectSyntheticPurchase
{
    public function customer(): User
    {
        return User::where('is_automated_demo', true)->where('status', UserStatus::Active)->inRandomOrder()->firstOrFail();
    }

    public function product(): ?Product
    {
        return Product::visibleInStorefront()->where('is_automated_demo', true)
            ->whereHas('inventoryItem', fn ($stock) => $stock->whereColumn('on_hand', '>', 'reserved'))
            ->inRandomOrder()->first();
    }
}
