<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Order> */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'public_number' => 'ORD-'.Str::upper(Str::random(16)),
            'checkout_key' => (string) Str::uuid(),
            'status' => OrderStatus::PendingPayment,
            'subtotal_cents' => 2500,
            'shipping_cents' => 0,
            'total_cents' => 2500,
            'address_snapshot_encrypted' => ['recipient' => 'Cliente FictÃ­cio', 'postal_code' => '01001-000', 'street' => 'Rua FictÃ­cia', 'number' => '1', 'complement' => null, 'district' => 'Centro', 'city' => 'Cidade FictÃ­cia', 'state' => 'SP'],
            'shipping_method' => 'pickup',
            'shipping_days' => 1,
            'placed_at' => now(),
        ];
    }
}
