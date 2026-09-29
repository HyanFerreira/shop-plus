<?php

namespace Tests\Feature\Security;

use App\Actions\Cart\AddCartItem;
use App\Actions\Checkout\PlaceOrder;
use App\Actions\Payment\ProcessPayment;
use App\Domain\Payment\FictitiousCardValidator;
use App\Models\Address;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\User;
use App\Support\SecurityAudit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_metadata_is_encrypted_and_log_is_immutable(): void
    {
        $user = User::factory()->create();
        $log = app(SecurityAudit::class)->record($user, 'security.test', $user, ['marker' => 'sensitive-test-marker']);

        $raw = DB::table('audit_logs')->where('id', $log->id)->value('metadata_encrypted');
        $this->assertStringNotContainsString('sensitive-test-marker', $raw);
        $this->assertSame('sensitive-test-marker', $log->metadata_encrypted['marker']);
        $this->assertArrayNotHasKey('metadata_encrypted', $log->toArray());

        try {
            $log->update(['event' => 'changed']);
            $this->fail('Update should fail.');
        } catch (\LogicException) {
            $this->assertTrue(true);
        }

        $this->expectException(\LogicException::class);
        $log->delete();
    }

    public function test_checkout_and_payment_create_audit_without_card_data(): void
    {
        $user = User::factory()->create();
        $address = Address::factory()->for($user)->create();
        $product = Product::factory()->create();
        InventoryItem::factory()->for($product)->create(['on_hand' => 3]);
        app(AddCartItem::class)->execute($user, $product, 1);
        $order = app(PlaceOrder::class)->execute($user, $address->id, 'pickup', (string) Str::uuid());
        app(ProcessPayment::class)->execute($user, $order, FictitiousCardValidator::APPROVED_VISA, '123', now()->month, now()->year + 1, (string) Str::uuid());

        $this->assertDatabaseHas('audit_logs', ['event' => 'order.created', 'actor_id' => $user->id]);
        $this->assertDatabaseHas('audit_logs', ['event' => 'payment.authorized', 'actor_id' => $user->id]);
        $raw = json_encode(DB::table('audit_logs')->get());
        $this->assertStringNotContainsString(FictitiousCardValidator::APPROVED_VISA, $raw);
    }
}
