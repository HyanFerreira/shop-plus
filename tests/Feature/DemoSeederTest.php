<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_seed_is_idempotent_and_contains_only_marked_fictitious_data(): void
    {
        $this->seed();
        $counts = [
            'users' => DB::table('users')->count(),
            'products' => DB::table('products')->count(),
            'orders' => DB::table('orders')->count(),
            'suppliers' => DB::table('suppliers')->count(),
        ];
        $this->seed();

        $this->assertSame($counts['users'], DB::table('users')->count());
        $this->assertSame($counts['products'], DB::table('products')->count());
        $this->assertSame($counts['orders'], DB::table('orders')->count());
        $this->assertSame($counts['suppliers'], DB::table('suppliers')->count());
        $this->assertDatabaseHas('users', ['email' => 'admin@comercio.example.test']);
        $this->assertDatabaseHas('users', ['email' => 'cliente@comercio.example.test']);
        $this->assertTrue(Order::where('status', OrderStatus::PendingPayment)->exists());
        $this->assertTrue(Order::where('status', OrderStatus::Paid)->exists());

        $rawProfile = json_encode(DB::table('customer_profiles')->first());
        $this->assertStringNotContainsString('529.982.247-25', $rawProfile);
        $this->assertStringNotContainsString('98765-4321', json_encode(DB::table('phones')->get()));
    }
}
