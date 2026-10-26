<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeRefinementTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_has_the_original_headphones_as_a_static_hero_above_the_approved_category_section(): void
    {
        $response = $this->get('/')->assertOk();
        $document = new \DOMDocument;
        @$document->loadHTML('<?xml encoding="UTF-8">'.$response->getContent());
        $xpath = new \DOMXPath($document);
        $response->assertDontSee('homeCarousel', false)->assertDontSee('aria-roledescription="carrossel"', false);
        $this->assertSame(1, $xpath->query('//section[@class="home-hero"]')->length);
        $hero = $xpath->query('//section[@class="home-hero"]')->item(0);
        $this->assertSame(0, $xpath->query('.//button | .//*[@x-data] | .//*[@aria-roledescription="slide"]', $hero)->length);
        $this->assertSame(1, $xpath->query('.//img', $hero)->length);
        $this->assertStringEndsWith('/images/hero-headphones.png', $xpath->query('.//img', $hero)->item(0)->getAttribute('src'));
        $this->assertSame('#ofertas', $xpath->query('.//a', $hero)->item(0)->getAttribute('href'));
        $this->assertSame('2.5', $xpath->query('.//a//svg', $hero)->item(0)?->getAttribute('stroke-width'));
        $this->assertSame('um só lugar.', $xpath->query('.//h1/span[@class="home-hero__highlight"]', $hero)->item(0)?->textContent);
        $this->assertSame(1, $xpath->query('.//h1/br', $hero)->length);
        $this->assertSame(1, $xpath->query('.//span[@class="home-hero__tag"]/following-sibling::h1', $hero)->length);
        $this->assertSame(0, $xpath->query('.//ul | .//li', $hero)->length);
        $this->assertSame(0, $xpath->query('.//a/following-sibling::*', $hero)->length);
        $this->assertSame(1, $xpath->query('//section[@id="ofertas"]')->length);
        $response->assertSeeText('Tudo o que você precisa')->assertSeeText('em')->assertSeeText('um só lugar.')
            ->assertSeeText('Ofertas incríveis para você')
            ->assertSeeText('Produtos incríveis para o seu dia a dia')->assertSeeText('com entrega em todo o Brasil.')
            ->assertSeeText('Ver ofertas');
        $this->assertSame(1, $xpath->query('//section[@aria-labelledby="categories-title"]')->length);
        $this->assertSame('Categorias', $xpath->query('//*[@id="categories-title"]')->item(0)?->textContent);
        foreach (['categories-title', 'offers-title', 'bestsellers-title'] as $titleId) {
            $this->assertSame(1, $xpath->query('//section[@class="home-section"]/div[@class="home-section__heading"]/h2[@id="'.$titleId.'"]')->length);
        }
        $this->assertSame(1, $xpath->query('//main//h1')->length);
        $response->assertDontSeeText('Explore nossos departamentos');
        $this->assertSame(0, $xpath->query('//section[@aria-labelledby="categories-title"]//a[contains(.,"Ver mais") or contains(.,"Ver todas")]')->length);
        $classes = [];
        foreach ($xpath->query('//main/div/*') as $section) {
            $classes[] = explode(' ', $section->getAttribute('class'))[0];
        }
        $this->assertSame(['home-hero', 'home-section', 'home-section', 'home-benefits', 'home-section', 'home-responsive', 'home-newsletter'], $classes);
        $response->assertDontSeeText('Categorias em destaque');
    }

    public function test_shortcuts_use_real_active_category_slugs_and_the_existing_catalog_filter(): void
    {
        $category = Category::factory()->create(['name' => 'Informática', 'slug' => 'informatica']);
        $inactive = Category::factory()->create(['name' => 'Categoria retirada', 'status' => 'inactive']);
        $product = Product::factory()->for($category)->create(['name' => 'Notebook da categoria selecionada']);
        $foreign = Product::factory()->for(Category::factory()->create(['name' => 'Outra categoria']))->create(['name' => 'Produto de outra categoria']);
        $home = $this->get('/')->assertOk()->assertDontSeeText($inactive->name);
        $document = new \DOMDocument;
        @$document->loadHTML('<?xml encoding="UTF-8">'.$home->getContent());
        $xpath = new \DOMXPath($document);
        $links = $xpath->query('//nav[@aria-label="Atalhos de categorias"]/a');
        $this->assertSame(10, $links->length);
        $this->assertSame(route('catalog.index', ['category' => $category->slug]), $links->item(1)->getAttribute('href'));
        $this->assertSame(route('catalog.index'), $links->item(9)->getAttribute('href'));
        $this->get('/catalogo?category=informatica')->assertOk()->assertSeeText($product->name)->assertDontSeeText($foreign->name);
        $this->get($links->item(9)->getAttribute('href'))->assertOk()->assertSeeText($product->name)->assertSeeText($foreign->name);
    }

    public function test_category_images_match_existing_assets_and_labels_are_outside_circles(): void
    {
        $slugs = ['eletronicos', 'informatica', 'casa-decoracao', 'moda', 'esportes', 'beleza', 'automotivo', 'brinquedos', 'livros'];
        foreach ($slugs as $slug) {
            $category = Category::factory()->create(['slug' => $slug, 'name' => $slug]);
            Product::factory()->for($category)->create(['name' => 'Produto de '.$slug]);
        }
        $response = $this->get('/')->assertOk();
        $document = new \DOMDocument;
        @$document->loadHTML('<?xml encoding="UTF-8">'.$response->getContent());
        $xpath = new \DOMXPath($document);
        $links = $xpath->query('//nav[@aria-label="Atalhos de categorias"]/a');
        $this->assertSame(10, $links->length);
        $expected = collect($slugs)->push('mais');
        $labels = ['Eletrônicos', 'Informática', 'Casa e Decoração', 'Moda', 'Esportes', 'Beleza', 'Automotivo', 'Brinquedos', 'Livros', 'Mais'];
        foreach ($links as $index => $link) {
            $image = $xpath->query('./span[contains(@class,"home-shortcuts__circle")]/img', $link)->item(0);
            $this->assertNotNull($image);
            $this->assertStringEndsWith('/images/categories/'.$expected[$index].'.png', $image->getAttribute('src'));
            $this->assertFileExists(public_path('images/categories/'.$expected[$index].'.png'));
            $this->assertSame(1, $xpath->query('./span[not(contains(@class,"home-shortcuts__circle"))]', $link)->length);
            $label = $xpath->query('./span[not(contains(@class,"home-shortcuts__circle"))]', $link)->item(0);
            $this->assertSame($labels[$index], trim($label->textContent));
            $expectedUrl = $index < 9 ? route('catalog.index', ['category' => $expected[$index]]) : route('catalog.index');
            $this->assertSame($expectedUrl, $link->getAttribute('href'));
            if ($index < 9) {
                $this->get($expectedUrl)->assertOk()->assertSeeText('Produto de '.$expected[$index])
                    ->assertDontSeeText('Produto de '.$expected[($index + 1) % 9]);
            }
        }
    }

    public function test_best_sellers_keep_the_existing_order_and_only_expand_to_ten_distinct_eligible_products(): void
    {
        $category = Category::factory()->create();
        $products = collect(range(1, 12))->map(fn ($count) => Product::factory()->for($category)->create([
            'rating_average' => 4.8, 'rating_count' => $count,
        ]));
        Product::factory()->inactive()->create(['rating_average' => 5]);
        $response = $this->get('/')->assertOk();
        $response->assertViewHas('bestSellers', fn ($items) => $items->modelKeys() === $products->reverse()->take(10)->pluck('id')->all());
        $this->assertDatabaseCount('products', 13);
        $document = new \DOMDocument;
        @$document->loadHTML('<?xml encoding="UTF-8">'.$response->getContent());
        $xpath = new \DOMXPath($document);
        $this->assertSame(15, $xpath->query('//article[contains(@class,"home-product-card--quick-actions")]')->length);
        $this->assertSame(0, $xpath->query('//a//button | //a//form | //button//button | //form//form')->length);
    }

    public function test_short_best_seller_collection_is_not_padded_with_duplicates(): void
    {
        Product::factory()->count(3)->create();
        $this->get('/')->assertOk()->assertViewHas('bestSellers', fn ($items) => $items->count() === 3 && $items->unique('id')->count() === 3);
    }
}
