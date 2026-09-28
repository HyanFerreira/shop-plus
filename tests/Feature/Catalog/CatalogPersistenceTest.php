<?php

namespace Tests\Feature\Catalog;

use App\Enums\CatalogStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogPersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_and_product_statuses_are_typed_and_active_by_default(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->for($category)->create();

        $this->assertSame(CatalogStatus::Active, $category->status);
        $this->assertSame(CatalogStatus::Active, $product->status);
        $this->assertTrue(Category::active()->whereKey($category)->exists());
        $this->assertTrue(Product::active()->whereKey($product)->exists());
    }

    public function test_catalogue_relationships_and_image_order_are_defined(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->for($category)->create();
        $later = ProductImage::factory()->for($product)->create(['sort_order' => 20]);
        $earlier = ProductImage::factory()->for($product)->create(['sort_order' => 10]);

        $this->assertTrue($category->products->contains($product));
        $this->assertSame([$earlier->id, $later->id], $product->images->pluck('id')->all());
        $this->assertSame($product->id, $earlier->product->id);
    }

    public function test_price_and_measurements_are_persisted_as_integers(): void
    {
        $product = Product::factory()->create([
            'price_cents' => 19_990,
            'weight_grams' => 750,
            'width_mm' => 120,
            'height_mm' => 80,
            'length_mm' => 250,
        ]);

        foreach (['price_cents', 'weight_grams', 'width_mm', 'height_mm', 'length_mm'] as $attribute) {
            $this->assertIsInt($product->{$attribute});
        }

        $this->assertSame(19_990, $product->price_cents);
    }

    public function test_category_slugs_are_unique(): void
    {
        $category = Category::factory()->create();

        $this->expectException(QueryException::class);
        Category::factory()->create(['slug' => $category->slug]);
    }

    public function test_product_slugs_are_unique(): void
    {
        $product = Product::factory()->create();

        $this->expectException(QueryException::class);
        Product::factory()->create(['slug' => $product->slug]);
    }

    public function test_product_skus_are_unique(): void
    {
        $product = Product::factory()->create();

        $this->expectException(QueryException::class);
        Product::factory()->create(['sku' => $product->sku]);
    }

    public function test_categories_and_products_use_soft_deletion(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->for($category)->create();

        $product->delete();
        $category->delete();

        $this->assertSoftDeleted($product);
        $this->assertSoftDeleted($category);
        $this->assertNull(Product::find($product->id));
        $this->assertNull(Category::find($category->id));
        $this->assertNotNull(Product::withTrashed()->find($product->id));
        $this->assertNotNull(Category::withTrashed()->find($category->id));
    }
}
