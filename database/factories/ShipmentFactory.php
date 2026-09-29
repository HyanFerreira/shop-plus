<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Enums\ShipmentStatus;
use App\Models\Order;
use App\Models\Shipment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Shipment> */
class ShipmentFactory extends Factory
{
    protected $model = Shipment::class;

    public function definition(): array
    {
        return [
            'order_id' => Order::factory()->state(['status' => OrderStatus::Paid]),
            'method' => 'economy',
            'price_cents' => 1490,
            'estimated_days' => 7,
            'status' => ShipmentStatus::AwaitingProcessing,
            'tracking_code' => 'TRK-'.Str::upper(Str::random(16)),
        ];
    }
}
