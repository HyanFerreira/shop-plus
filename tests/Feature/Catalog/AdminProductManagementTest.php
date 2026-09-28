<?php

namespace Tests\Feature\Catalog;

use App\Livewire\Admin\CatalogManager;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

class AdminProductManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_and_update_a_product_using_integer_cents(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::factory()->create();
        $component = Livewire::actingAs($admin)->test(CatalogManager::class);

        $this->fillProduct($component, $category)
            ->call('saveProduct')
            ->assertHasNoErrors();

        $product = Product::firstOrFail();
        $this->assertSame('produto-academico', $product->slug);
        $this->assertSame('TEST-001', $product->sku);
        $this->assertSame(19_990, $product->price_cents);

        Livewire::actingAs($admin)
            ->test(CatalogManager::class)
            ->call('editProduct', $product->id)
            ->assertSet('productPrice', '199,90')
            ->set('productName', 'Produto Acadêmico Atualizado')
            ->set('productPrice', '250.05')
            ->set('productStatus', 'inactive')
            ->call('saveProduct')
            ->assertHasNoErrors();

        $this->assertSame(25_005, $product->fresh()->price_cents);
        $this->assertSame('inactive', $product->fresh()->status->value);
    }

    public function test_product_requires_existing_category_and_valid_money_and_measurements(): void
    {
        $component = Livewire::actingAs(User::factory()->admin()->create())->test(CatalogManager::class);
        $category = Category::factory()->create();

        $this->fillProduct($component, $category, [
            'productCategoryId' => 999999,
            'productPrice' => '19.999',
            'productWeight' => 0,
            'productWidth' => -1,
        ])->call('saveProduct')->assertHasErrors([
            'productCategoryId', 'productPrice', 'productWeight', 'productWidth',
        ]);

        $this->assertDatabaseCount('products', 0);
    }

    public function test_product_slug_and_sku_collisions_are_validation_errors(): void
    {
        $existing = Product::factory()->create(['slug' => 'produto-academico', 'sku' => 'TEST-001']);
        $component = Livewire::actingAs(User::factory()->admin()->create())->test(CatalogManager::class);

        $this->fillProduct($component, $existing->category)
            ->call('saveProduct')
            ->assertHasErrors(['productName', 'productSku']);

        $this->assertDatabaseCount('products', 1);
    }

    public function test_admin_can_soft_delete_and_restore_a_product(): void
    {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create();

        Livewire::actingAs($admin)->test(CatalogManager::class)
            ->call('deleteProduct', $product->id)
            ->assertHasNoErrors();

        $this->assertSoftDeleted($product);

        Livewire::actingAs($admin)->test(CatalogManager::class)
            ->call('restoreProduct', $product->id)
            ->assertHasNoErrors();

        $this->assertNotSoftDeleted($product->fresh());
    }

    public function test_persisted_product_description_is_escaped(): void
    {
        $unsafe = '<script>alert("product")</script>';
        $category = Category::factory()->create();
        $component = Livewire::actingAs(User::factory()->admin()->create())->test(CatalogManager::class);

        $this->fillProduct($component, $category, ['productDescription' => $unsafe])
            ->call('saveProduct')
            ->assertHasNoErrors()
            ->assertSee($unsafe)
            ->assertDontSee($unsafe, false);
    }

    /** @param array<string, string|int> $overrides */
    private function fillProduct(Testable $component, Category $category, array $overrides = []): Testable
    {
        $values = array_merge([
            'productCategoryId' => $category->id,
            'productName' => 'Produto Acadêmico',
            'productSku' => 'test-001',
            'productDescription' => 'Descrição exclusivamente fictícia.',
            'productPrice' => '199,90',
            'productWeight' => 750,
            'productWidth' => 120,
            'productHeight' => 80,
            'productLength' => 250,
            'productStatus' => 'active',
        ], $overrides);

        foreach ($values as $property => $value) {
            $component->set($property, $value);
        }

        return $component;
    }
}
