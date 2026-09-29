<?php

namespace App\Actions\Inventory;

use App\Domain\Catalog\Price;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\SupplierProduct;
use App\Models\User;

class SaveSupplierProduct
{
    public function execute(User $actor, int $supplierId, int $productId, string $code, string $cost): SupplierProduct
    {
        abort_unless($actor->isAdmin(), 403);
        $supplier = Supplier::query()->where('active', true)->findOrFail($supplierId);
        $product = Product::query()->findOrFail($productId);

        return $supplier->supplierProducts()->updateOrCreate(
            ['product_id' => $product->id],
            ['external_code' => strtoupper(trim($code)), 'cost_cents' => Price::fromInput($cost)->cents()],
        );
    }
}
