<?php

namespace Tests\Feature\Catalog;

use App\Livewire\Admin\CatalogManager;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ProductImageManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_upload_a_valid_image_with_server_generated_path(): void
    {
        Storage::fake('public');
        $product = Product::factory()->create();

        Livewire::actingAs(User::factory()->admin()->create())
            ->test(CatalogManager::class)
            ->set('imageProductId', $product->id)
            ->set('productImage', UploadedFile::fake()->image('customer-name.jpg', 800, 600))
            ->set('imageAltText', 'Produto fictício visto de frente')
            ->set('imageSortOrder', 10)
            ->call('saveProductImage')
            ->assertHasNoErrors();

        $image = ProductImage::firstOrFail();

        $this->assertStringStartsWith('products/'.$product->id.'/', $image->path);
        $this->assertStringNotContainsString('customer-name', $image->path);
        $this->assertSame(10, $image->sort_order);
        Storage::disk('public')->assertExists($image->path);
    }

    public function test_non_image_and_oversized_uploads_are_rejected(): void
    {
        Storage::fake('public');
        $product = Product::factory()->create();
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)->test(CatalogManager::class)
            ->set('imageProductId', $product->id)
            ->set('productImage', UploadedFile::fake()->create('payload.jpg', 10, 'text/plain'))
            ->call('saveProductImage')
            ->assertHasErrors(['productImage']);

        Livewire::actingAs($admin)->test(CatalogManager::class)
            ->set('imageProductId', $product->id)
            ->set('productImage', UploadedFile::fake()->image('large.png')->size(2049))
            ->call('saveProductImage')
            ->assertHasErrors(['productImage']);

        $this->assertDatabaseCount('product_images', 0);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_upload_requires_an_existing_non_deleted_product(): void
    {
        Storage::fake('public');
        $product = Product::factory()->create();
        $product->delete();

        Livewire::actingAs(User::factory()->admin()->create())
            ->test(CatalogManager::class)
            ->set('imageProductId', $product->id)
            ->set('productImage', UploadedFile::fake()->image('valid.webp'))
            ->call('saveProductImage')
            ->assertHasErrors(['imageProductId']);

        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_admin_can_delete_the_image_record_and_file(): void
    {
        Storage::fake('public');
        $product = Product::factory()->create();
        Storage::disk('public')->put('products/'.$product->id.'/test.jpg', 'synthetic-image');
        $image = ProductImage::factory()->for($product)->create(['path' => 'products/'.$product->id.'/test.jpg']);

        Livewire::actingAs(User::factory()->admin()->create())
            ->test(CatalogManager::class)
            ->call('deleteProductImage', $image->id)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('product_images', ['id' => $image->id]);
        Storage::disk('public')->assertMissing($image->path);
    }

    public function test_image_alt_text_is_escaped_and_images_are_ordered(): void
    {
        $product = Product::factory()->create();
        $unsafe = '<script>alert("image")</script>';
        ProductImage::factory()->for($product)->create(['alt_text' => 'Second', 'sort_order' => 20]);
        ProductImage::factory()->for($product)->create(['alt_text' => $unsafe, 'sort_order' => 10]);

        Livewire::actingAs(User::factory()->admin()->create())
            ->test(CatalogManager::class)
            ->assertSeeInOrder([$unsafe, 'Second'])
            ->assertDontSee($unsafe, false);
    }
}
