<?php

namespace Tests\Feature\Inventory;

use App\Domain\PersonalData\BlindIndex;
use App\Livewire\Admin\SupplyManager;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class SupplierManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_supply_route_and_component_require_an_administrator(): void
    {
        $this->get('/admin/abastecimento')->assertRedirect('/login');
        $this->actingAs(User::factory()->create())->get('/admin/abastecimento')->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())
            ->get('/admin/abastecimento')->assertOk()->assertSeeLivewire(SupplyManager::class);

        Livewire::actingAs(User::factory()->create())->test(SupplyManager::class)->assertForbidden();
    }

    public function test_admin_can_create_and_update_an_encrypted_fictitious_supplier(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)->test(SupplyManager::class)
            ->set('supplierName', 'Fornecedor Acadêmico Fictício')
            ->set('supplierDocument', '12.345.678/0001-95')
            ->set('supplierEmail', 'fornecedor@example.test')
            ->set('supplierPhone', '(11) 3333-4444')
            ->set('supplierAddress', 'Rua Fictícia, 100')
            ->call('saveSupplier')->assertHasNoErrors();

        $supplier = Supplier::firstOrFail();
        $raw = DB::table('suppliers')->find($supplier->id);
        $this->assertSame((new BlindIndex)->for('12345678000195'), $supplier->document_hash);
        $this->assertNotSame($supplier->name_encrypted, $raw->name_encrypted);

        Livewire::actingAs($admin)->test(SupplyManager::class)
            ->call('editSupplier', $supplier->id)
            ->set('supplierName', 'Fornecedor Atualizado Fictício')
            ->set('supplierActive', false)
            ->call('saveSupplier')->assertHasNoErrors();

        $this->assertSame('Fornecedor Atualizado Fictício', $supplier->fresh()->name_encrypted);
        $this->assertFalse($supplier->fresh()->active);
    }

    public function test_invalid_or_duplicate_document_is_rejected_generically(): void
    {
        $admin = User::factory()->admin()->create();
        Supplier::factory()->create([
            'document_encrypted' => '12.345.678/0001-95',
            'document_hash' => (new BlindIndex)->for('12345678000195'),
        ]);

        Livewire::actingAs($admin)->test(SupplyManager::class)
            ->set('supplierName', 'Outro fornecedor fictício')
            ->set('supplierDocument', '12.345.678/0001-95')
            ->set('supplierEmail', 'outro@example.test')
            ->set('supplierPhone', '(11) 3333-5555')
            ->set('supplierAddress', 'Rua de Teste, 200')
            ->call('saveSupplier')->assertHasErrors(['supplierDocument']);

        Livewire::actingAs($admin)->test(SupplyManager::class)
            ->set('supplierDocument', '00000000000000')
            ->call('saveSupplier')->assertHasErrors(['supplierDocument']);
    }

    public function test_admin_can_link_a_product_with_integer_cost(): void
    {
        $supplier = Supplier::factory()->create();
        $product = Product::factory()->create();

        Livewire::actingAs(User::factory()->admin()->create())->test(SupplyManager::class)
            ->set('linkSupplierId', $supplier->id)
            ->set('linkProductId', $product->id)
            ->set('externalCode', ' ext-001 ')
            ->set('supplierCost', '123,45')
            ->call('saveSupplierProduct')->assertHasNoErrors();

        $link = $supplier->supplierProducts()->firstOrFail();
        $this->assertSame(12_345, $link->cost_cents);
        $this->assertSame('EXT-001', $link->external_code);
    }

    public function test_admin_can_soft_delete_and_restore_supplier(): void
    {
        $supplier = Supplier::factory()->create();
        $component = Livewire::actingAs(User::factory()->admin()->create())->test(SupplyManager::class);

        $component->call('deleteSupplier', $supplier->id)->assertHasNoErrors();
        $this->assertSoftDeleted($supplier);
        $component->call('restoreSupplier', $supplier->id)->assertHasNoErrors();
        $this->assertNotSoftDeleted($supplier->fresh());
    }
}
