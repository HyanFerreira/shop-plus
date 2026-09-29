<?php

namespace Tests\Feature\Inventory;

use App\Actions\Inventory\AdjustInventory;
use App\Enums\StockMovementType;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use LogicException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class InventoryAdjustmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_make_justified_idempotent_adjustment(): void
    {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create();
        $action = app(AdjustInventory::class);

        $action->execute($admin, $product, 5, 'Contagem inicial fictícia', '11111111-1111-4111-8111-111111111111');
        $action->execute($admin, $product, 5, 'Contagem inicial fictícia', '11111111-1111-4111-8111-111111111111');

        $inventory = $product->inventoryItem;
        $this->assertSame(5, $inventory->on_hand);
        $this->assertDatabaseCount('stock_movements', 1);
        $movement = StockMovement::firstOrFail();
        $this->assertSame(StockMovementType::Adjustment, $movement->type);
        $this->assertSame($admin->id, $movement->actor_id);
    }

    public function test_adjustment_cannot_make_stock_negative_or_drop_below_reserved(): void
    {
        $admin = User::factory()->admin()->create();
        $inventory = InventoryItem::factory()->create(['on_hand' => 5, 'reserved' => 3]);

        $this->expectException(ValidationException::class);
        app(AdjustInventory::class)->execute($admin, $inventory->product, -3, 'Ajuste inválido de teste', '22222222-2222-4222-8222-222222222222');
    }

    public function test_customer_cannot_adjust_inventory(): void
    {
        $this->expectException(HttpException::class);
        app(AdjustInventory::class)->execute(User::factory()->create(), Product::factory()->create(), 1, 'Tentativa', '33333333-3333-4333-8333-333333333333');
    }

    public function test_stock_movements_cannot_be_updated_or_deleted(): void
    {
        $movement = StockMovement::factory()->create();

        try {
            $movement->update(['reason' => 'alterado']);
            $this->fail('Movement update should be blocked.');
        } catch (LogicException) {
            $this->assertSame('Ajuste fictício de teste', $movement->fresh()->reason);
        }

        $this->expectException(LogicException::class);
        $movement->delete();
    }
}
