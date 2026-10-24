<?php

namespace Tests\Feature;

use App\Models\Product;
use Database\Seeders\DemoProductImagesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HomePresentationTest extends TestCase
{
    use RefreshDatabase;

    public function test_known_demo_product_has_a_specific_local_image_without_database_changes(): void
    {
        $product = Product::factory()->create([
            'sku' => 'VET-NOTE-15',
            'name' => 'Notebook fictício com um nome longo que deve permanecer completo e legível',
            'price_cents' => 279900,
            'rating_average' => 4.8,
            'rating_count' => 521,
        ]);
        $before = $product->fresh()->getRawOriginal();

        $this->get('/')->assertOk()
            ->assertSee('images/demo-products/notebook.svg', false)
            ->assertSeeText($product->name)
            ->assertSeeText('2.799,00')
            ->assertSeeText('521');

        $this->assertSame($before, $product->fresh()->getRawOriginal());
        $this->assertSame(0, $product->images()->count());
    }

    public function test_registered_photo_has_priority_and_missing_files_have_an_honest_fallback(): void
    {
        Storage::fake('public');
        $product = Product::factory()->create(['sku' => 'NIM-PHONE-01']);
        Storage::disk('public')->put('products/custom.svg', '<svg xmlns="http://www.w3.org/2000/svg"/>');
        $product->images()->create(['path' => 'products/custom.svg', 'alt_text' => 'Foto cadastrada', 'sort_order' => 0]);
        $this->get('/')->assertOk()->assertSee('products/custom.svg', false)->assertSee('Foto cadastrada');

        $product->images()->create(['path' => 'products/missing-first.webp', 'alt_text' => 'Arquivo ausente', 'sort_order' => -1]);
        $this->get('/')->assertOk()->assertSee('products/custom.svg', false);

        Storage::disk('public')->delete('products/custom.svg');
        $this->get('/')->assertOk()->assertSee('images/demo-products/phone.svg', false);

        $unknown = Product::factory()->create(['sku' => 'UNKNOWN-DEMO']);
        $unknown->images()->create(['path' => 'products/missing.webp', 'alt_text' => 'Arquivo ausente', 'sort_order' => 0]);
        $this->get('/')->assertOk()->assertSeeText('Imagem indisponível');
    }

    public function test_image_seeder_is_idempotent_and_changes_only_image_associations(): void
    {
        Storage::fake('public');
        $product = Product::factory()->create(['sku' => 'NIM-PHONE-01']);
        $unknown = Product::factory()->create(['sku' => 'UNRELATED']);
        $product->images()->create(['path' => 'products/original.webp', 'alt_text' => 'Imagem original', 'sort_order' => 0]);
        $before = $product->fresh()->getRawOriginal();

        $this->seed(DemoProductImagesSeeder::class);
        $this->seed(DemoProductImagesSeeder::class);

        $this->assertSame($before, $product->fresh()->getRawOriginal());
        $this->assertSame(2, $product->images()->count());
        $this->assertSame('products/original.webp', $product->images()->first()->path);
        $this->assertSame(0, $unknown->images()->count());
        Storage::disk('public')->assertExists('products/demo/phone.svg');
    }

    public function test_all_demo_images_are_local_valid_and_distinct(): void
    {
        $images = config('demo-images.products', []);
        $this->assertCount(16, $images);
        $this->assertCount(16, array_unique(array_column($images, 'file')));
        foreach ($images as $image) {
            $file = public_path('images/demo-products/'.$image['file']);
            $this->assertFileExists($file);
            $xml = simplexml_load_file($file);
            $this->assertNotFalse($xml);
            $this->assertSame('0 0 640 480', (string) $xml['viewBox']);
            $this->assertNotEmpty($image['alt']);
        }
    }

    public function test_home_does_not_offer_unimplemented_favorites_apps_newsletter_or_installments(): void
    {
        $response = $this->get('/')->assertOk()
            ->assertSeeText('Inscrições indisponíveis')
            ->assertSeeText('ShopPlus em qualquer tela')
            ->assertDontSeeText('12x sem juros')
            ->assertDontSeeText('App Store')
            ->assertDontSeeText('Google Play')
            ->assertDontSeeText('QR Code');
        $dom = new \DOMDocument;
        @$dom->loadHTML('<?xml encoding="UTF-8">'.$response->getContent());
        $xpath = new \DOMXPath($dom);
        $this->assertSame(0, $xpath->query('//main//form')->length);
        $this->assertSame(0, $xpath->query('//main//button[contains(@aria-label, "Favorit")]')->length);
    }
}
