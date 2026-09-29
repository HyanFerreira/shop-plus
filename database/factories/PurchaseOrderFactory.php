<?php

namespace Database\Factories;

use App\Enums\PurchaseOrderStatus;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PurchaseOrderFactory extends Factory
{
    protected $model = PurchaseOrder::class;

    public function definition(): array
    {
        return ['supplier_id' => Supplier::factory(), 'public_number' => 'PO-'.Str::upper(Str::random(12)), 'status' => PurchaseOrderStatus::Draft, 'total_cents' => 0];
    }
}
