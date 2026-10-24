<?php

namespace Tests\Feature\Catalog;

use App\Livewire\ProductAddToCart;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ProductDetailPresentationTest extends TestCase
{
    use RefreshDatabase;

    public function test_gallery_prioritizes_existing_registered_images_and_deduplicates_paths(): void
    {
        Storage::fake('public');
        $product = Product::factory()->create(['sku' => 'NIM-PHONE-01']);
        ProductImage::factory()->for($product)->create(['path' => 'missing.jpg', 'sort_order' => 0]);
        foreach (['front.svg', 'front.svg', 'back.svg'] as $index => $path) {
            Storage::disk('public')->put($path, '<svg xmlns="http://www.w3.org/2000/svg"/>');
            ProductImage::factory()->for($product)->create(['path' => $path, 'sort_order' => $index + 1]);
        }
        $response = $this->get(route('catalog.show', $product->slug))->assertOk();
        $dom = $this->dom($response->getContent());
        $xpath = new \DOMXPath($dom);
        $this->assertSame(2, $xpath->query('//*[@id="product-gallery-stage"]/img')->length);
        $this->assertSame(2, $xpath->query('//*[@id="product-thumbnails"]/button')->length);
        $response->assertDontSee('missing.jpg')->assertDontSee('images/demo-products/phone.svg', false);
        $this->assertStringContainsString('front.svg', $xpath->query('//*[@id="product-gallery-stage"]/img')->item(0)->getAttribute('src'));
    }

    public function test_demo_image_is_single_without_fake_thumbnails_and_unknown_sku_has_fallback(): void
    {
        Storage::fake('public');
        $product = Product::factory()->create(['sku' => 'NIM-PHONE-01', 'rating_count' => 0]);
        ProductImage::factory()->for($product)->create(['path' => 'missing.jpg']);
        $this->get(route('catalog.show', $product->slug))->assertOk()
            ->assertSee('images/demo-products/phone.svg', false)->assertDontSee('id="product-thumbnails"', false)
            ->assertSeeText('Sem avaliações')->assertDontSeeText('12x')->assertDontSeeText('Oferta fictícia')
            ->assertDontSee('aria-label="Favoritar"', false)->assertDontSeeText('Comprar agora');
        $product->update(['sku' => 'UNKNOWN-IMAGE']);
        $this->get(route('catalog.show', $product->slug))->assertOk()->assertSeeText('Imagem indisponível')
            ->assertDontSee('id="product-thumbnails"', false);
    }

    public function test_real_content_and_related_products_reuse_the_shared_card(): void
    {
        $product = Product::factory()->create(['description' => '<script>Longa descrição</script>', 'weight_grams' => 240, 'rating_average' => 4.2, 'rating_count' => 17]);
        $related = Product::factory()->create(['category_id' => $product->category_id, 'sku' => 'LUM-FONE-01']);
        $this->get(route('catalog.show', $product->slug))->assertOk()
            ->assertSee($product->description)->assertDontSee($product->description, false)
            ->assertSeeText('240 g')->assertSeeText('17 avaliações')->assertSeeText('4,2')
            ->assertSee('home-product-card', false)->assertSee('images/demo-products/earbuds.svg', false)
            ->assertSee(route('catalog.show', $related->slug), false)
            ->assertDontSee('role="tab"', false)->assertDontSeeText('acima de R$ 199');
    }

    public function test_purchase_controls_present_stock_limits_disabled_and_existing_actions(): void
    {
        $product = Product::factory()->create();
        InventoryItem::factory()->for($product)->create(['on_hand' => 5, 'reserved' => 3]);
        $component = Livewire::test(ProductAddToCart::class, ['product' => $product])
            ->assertSee('Diminuir quantidade')->assertSee('Aumentar quantidade')->assertSee('wire:submit="add"', false)
            ->call('increment')->assertSet('quantity', 2);
        $dom = $this->dom($component->html());
        $this->assertSame('2', $dom->getElementById('product-quantity')->getAttribute('max'));
        $this->assertTrue($dom->getElementById('product-quantity-increment')->hasAttribute('disabled'));
        $product->inventoryItem->update(['on_hand' => 3]);
        $component = Livewire::test(ProductAddToCart::class, ['product' => $product->fresh('inventoryItem')]);
        $this->assertTrue($this->dom($component->html())->getElementById('product-add-submit')->hasAttribute('disabled'));
    }

    public function test_existing_add_contract_redirects_guests_and_handles_stock_errors_and_success(): void
    {
        $product = Product::factory()->create();
        InventoryItem::factory()->for($product)->create(['on_hand' => 3, 'reserved' => 1]);
        Livewire::test(ProductAddToCart::class, ['product' => $product])->call('add')->assertRedirect(route('login'));
        $user = User::factory()->create();
        Livewire::actingAs($user)->test(ProductAddToCart::class, ['product' => $product])
            ->set('quantity', 3)->call('add')->assertHasErrors('quantity')->assertSeeText('Quantidade indisponível em estoque.');
        Livewire::actingAs($user)->test(ProductAddToCart::class, ['product' => $product])
            ->set('quantity', 2)->call('add')->assertHasNoErrors()->assertRedirect(route('cart.show'));
        $this->assertDatabaseHas('cart_items', ['product_id' => $product->id, 'quantity' => 2]);
        $this->assertSame('Produto adicionado ao carrinho.', session('cart-status'));
        Livewire::actingAs($user)->test(ProductAddToCart::class, ['product' => $product])
            ->call('add')->assertHasErrors('quantity');
    }

    private function dom(string $html): \DOMDocument
    {
        $dom = new \DOMDocument;
        @$dom->loadHTML('<?xml encoding="UTF-8">'.$html);
        return $dom;
    }
}
