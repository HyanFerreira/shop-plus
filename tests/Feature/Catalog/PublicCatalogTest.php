<?php

namespace Tests\Feature\Catalog;

use App\Livewire\Catalog\Browser;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PublicCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalogue_is_publicly_accessible(): void
    {
        $this->get('/catalogo')
            ->assertOk()
            ->assertSeeText('Catálogo')
            ->assertSeeLivewire(Browser::class);
    }

    public function test_browser_shows_only_active_products_from_active_categories(): void
    {
        $activeCategory = Category::factory()->create();
        $inactiveCategory = Category::factory()->inactive()->create();
        $visible = Product::factory()->for($activeCategory)->create(['name' => 'Produto Visível']);
        Product::factory()->inactive()->for($activeCategory)->create(['name' => 'Produto Inativo']);
        Product::factory()->for($inactiveCategory)->create(['name' => 'Categoria Inativa']);
        $deleted = Product::factory()->for($activeCategory)->create(['name' => 'Produto Removido']);
        $deleted->delete();

        Livewire::test(Browser::class)
            ->assertSee($visible->name)
            ->assertDontSee('Produto Inativo')
            ->assertDontSee('Categoria Inativa')
            ->assertDontSee('Produto Removido');
    }

    public function test_search_category_filter_and_safe_sorting_work(): void
    {
        $books = Category::factory()->create(['name' => 'Livros', 'slug' => 'livros']);
        $other = Category::factory()->create(['name' => 'Outros', 'slug' => 'outros']);
        Product::factory()->for($books)->create(['name' => 'Laravel Seguro', 'slug' => 'laravel-seguro', 'price_cents' => 5_000]);
        Product::factory()->for($books)->create(['name' => 'PHP Moderno', 'slug' => 'php-moderno', 'price_cents' => 10_000]);
        Product::factory()->for($other)->create(['name' => 'Laravel Camiseta', 'slug' => 'laravel-camiseta', 'price_cents' => 2_000]);

        Livewire::test(Browser::class)
            ->set('search', 'Laravel')
            ->set('category', 'livros')
            ->assertSee('Laravel Seguro')
            ->assertDontSee('PHP Moderno')
            ->assertDontSee('Laravel Camiseta')
            ->set('search', '')
            ->set('sort', 'price_desc')
            ->assertSeeInOrder(['PHP Moderno', 'Laravel Seguro'])
            ->set('sort', 'price desc; drop table products')
            ->assertOk();

        $this->assertDatabaseCount('products', 3);
    }

    public function test_catalogue_is_paginated(): void
    {
        $category = Category::factory()->create();
        Product::factory()->count(13)->for($category)->create();

        Livewire::test(Browser::class)
            ->assertViewHas('products', fn ($products) => $products->count() === 12 && $products->lastPage() === 2);
    }

    public function test_product_detail_fails_closed_for_unavailable_products(): void
    {
        $category = Category::factory()->create();
        $active = Product::factory()->for($category)->create(['slug' => 'produto-ativo']);
        $inactive = Product::factory()->inactive()->for($category)->create(['slug' => 'produto-inativo']);
        $deleted = Product::factory()->for($category)->create(['slug' => 'produto-removido']);
        $deleted->delete();
        $hiddenCategory = Category::factory()->inactive()->create();
        $hidden = Product::factory()->for($hiddenCategory)->create(['slug' => 'categoria-inativa']);

        $this->get('/catalogo/'.$active->slug)->assertOk();
        $this->get('/catalogo/'.$inactive->slug)->assertNotFound();
        $this->get('/catalogo/'.$deleted->slug)->assertNotFound();
        $this->get('/catalogo/'.$hidden->slug)->assertNotFound();
    }

    public function test_product_detail_formats_price_escapes_content_and_has_image_fallback(): void
    {
        $unsafe = '<script>alert("detail")</script>';
        $product = Product::factory()->create([
            'name' => 'Produto de Detalhe',
            'slug' => 'produto-de-detalhe',
            'description' => $unsafe,
            'price_cents' => 123_456,
        ]);

        $this->get('/catalogo/'.$product->slug)
            ->assertOk()
            ->assertSeeText('R$ 1.234,56')
            ->assertSee($unsafe)
            ->assertDontSee($unsafe, false)
            ->assertSeeText('Sem imagem');

        ProductImage::factory()->for($product)->create(['alt_text' => 'Imagem sintética']);

        $this->get('/catalogo/'.$product->slug)
            ->assertOk()
            ->assertSee('Imagem sintética')
            ->assertDontSeeText('Sem imagem');
    }
}
