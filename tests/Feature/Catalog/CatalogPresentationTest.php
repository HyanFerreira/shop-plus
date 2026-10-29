<?php

namespace Tests\Feature\Catalog;

use App\Livewire\Catalog\Browser;
use App\Models\Category;
use App\Models\InventoryItem;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CatalogPresentationTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_reuses_home_card_with_explicit_brand_and_demo_image(): void
    {
        $product = Product::factory()->create(['sku' => 'NIM-PHONE-01', 'brand' => 'Marca <fictícia>', 'name' => 'Produto com nome longo que permanece completo no catálogo']);
        $response = $this->get('/catalogo')->assertOk()
            ->assertSee('home-product-card', false)
            ->assertSee('home-product-card__brand', false)
            ->assertSee('images/demo-products/phone.svg', false)
            ->assertSee($product->name)
            ->assertSee($product->brand)
            ->assertDontSee($product->brand, false);
        $dom = $this->dom($response->getContent());
        $this->assertSame('catalog-product-'.$product->id, $dom->getElementsByTagName('article')->item(0)->getAttribute('wire:key'));
        $this->get('/')->assertOk()->assertDontSee('home-product-card__brand', false);
    }

    public function test_every_active_filter_is_identified_and_clear_keeps_existing_search_semantics(): void
    {
        $category = Category::factory()->create(['name' => 'Eletrônicos', 'slug' => 'eletronicos']);
        $product = Product::factory()->for($category)->create(['name' => 'Telefone Alvo', 'brand' => 'Marca Alvo', 'price_cents' => 50000, 'rating_average' => 4.8, 'free_shipping' => true, 'express_shipping' => true]);
        InventoryItem::factory()->for($product)->create(['on_hand' => 5, 'reserved' => 0]);
        $component = Livewire::test(Browser::class)
            ->set('search', 'Telefone')->set('selectedCategories', ['eletronicos'])
            ->set('brands', ['Marca Alvo'])->set('minPrice', '400')->set('maxPrice', '600')
            ->set('minimumRating', 4)->set('inStock', true)->set('freeShipping', true)->set('expressShipping', true);
        $chips = $this->dom($component->html())->getElementById('catalog-active-filters');
        $this->assertNotNull($chips);
        foreach (['Telefone', 'Eletrônicos', 'Marca Alvo', '400', '600', '4+', 'Em estoque', 'Frete grátis', 'Entrega rápida'] as $label) {
            $this->assertStringContainsString($label, $chips->textContent);
        }
        $component->call('clearFilters')->assertSet('search', 'Telefone')->assertSet('selectedCategories', []);
        $chips = $this->dom($component->html())->getElementById('catalog-active-filters');
        $this->assertStringContainsString('Telefone', $chips->textContent);
        $this->assertStringNotContainsString('Marca Alvo', $chips->textContent);
        $component->set('search', '');
        $this->assertNull($this->dom($component->html())->getElementById('catalog-active-filters'));
    }

    public function test_filter_disclosure_and_existing_bindings_are_present_with_empty_search_state(): void
    {
        Product::factory()->create();
        $response = $this->get('/catalogo?q=nenhumresultado')->assertOk()
            ->assertSeeText('Nenhum produto encontrado')
            ->assertSeeText('Limpar busca');
        $dom = $this->dom($response->getContent());
        $this->assertSame('details', $dom->getElementById('catalog-filters')->tagName);
        $this->assertNotNull($dom->getElementById('catalog-filter-summary'));
        $this->assertNotNull($dom->getElementById('catalogMinPrice'));
        $this->assertNotNull($dom->getElementById('catalogMaxPrice'));
        foreach (['search', 'selectedCategories', 'minPrice', 'maxPrice', 'brands', 'minimumRating', 'inStock', 'freeShipping', 'expressShipping'] as $binding) {
            $this->assertMatchesRegularExpression('/wire:model[^=]*="'.preg_quote($binding, '/').'"/', $response->getContent());
        }
    }

    public function test_pagination_and_ordering_keep_the_existing_results_and_actions(): void
    {
        Product::factory()->count(13)->create();
        $component = Livewire::test(Browser::class)
            ->assertSee('Página 2')
            ->assertSee('Próxima página')
            ->call('nextPage')
            ->assertViewHas('products', fn ($products) => $products->currentPage() === 2 && $products->count() === 1)
            ->set('sort', 'price_asc')
            ->assertViewHas('products', fn ($products) => $products->currentPage() === 1)
            ->set('perPage', 24)
            ->assertViewHas('products', fn ($products) => $products->count() === 13 && $products->lastPage() === 1);
        $this->assertDatabaseCount('products', 13);
    }

    public function test_sort_menu_exposes_the_existing_choices_and_selected_state(): void
    {
        $component = Livewire::test(Browser::class);
        foreach (['relevance' => 'Mais relevantes', 'rating' => 'Melhor avaliados', 'name' => 'Nome', 'price_asc' => 'Menor preço', 'price_desc' => 'Maior preço'] as $value => $label) {
            $component->set('sort', $value)->assertSet('sort', $value);
            $dom = $this->dom($component->html());
            $trigger = $dom->getElementById('catalog-sort-trigger');
            $this->assertSame('menu', $trigger->getAttribute('aria-haspopup'));
            $this->assertSame('false', $trigger->getAttribute('aria-expanded'));
            $this->assertStringContainsString($label, $trigger->textContent);
            $menu = $dom->getElementById('catalog-sort-options');
            $this->assertSame('menu', $menu->getAttribute('role'));
            $options = (new \DOMXPath($dom))->query('.//button[@role="menuitemradio"]', $menu);
            $this->assertCount(5, $options);
            foreach ($options as $option) {
                $this->assertSame(trim($option->textContent) === $label ? 'true' : 'false', $option->getAttribute('aria-checked'));
                $this->assertSame('-1', $option->getAttribute('tabindex'));
                $this->assertStringContainsString("\$set('sort', '", $option->getAttribute('wire:click'));
            }
        }
    }

    public function test_per_page_select_and_filter_changes_reset_pagination(): void
    {
        Product::factory()->count(25)->create(['brand' => 'Marca inicial']);
        Product::factory()->create(['brand' => 'Marca selecionada']);
        $component = Livewire::test(Browser::class)->call('nextPage');
        $component->set('perPage', 24)
            ->assertViewHas('products', fn ($products) => $products->currentPage() === 1 && $products->count() === 24);
        $dom = $this->dom($component->html());
        $trigger = $dom->getElementById('catalog-per-page-trigger');
        $this->assertNotNull($trigger);
        $this->assertSame('menu', $trigger->getAttribute('aria-haspopup'));
        $this->assertStringContainsString('24 por página', $trigger->textContent);
        $menu = $dom->getElementById('catalog-per-page-options');
        $options = $menu->getElementsByTagName('button');
        $this->assertSame(['12 por página', '24 por página', '48 por página'], array_map(fn ($option) => trim($option->textContent), iterator_to_array($options)));
        foreach ($options as $index => $option) {
            $this->assertSame('menuitemradio', $option->getAttribute('role'));
            $this->assertSame($index === 1 ? 'true' : 'false', $option->getAttribute('aria-checked'));
            $this->assertSame("\$set('perPage', ".[12, 24, 48][$index].')', $option->getAttribute('wire:click'));
        }
        $component->call('nextPage')->set('brands', ['Marca selecionada'])
            ->assertViewHas('products', fn ($products) => $products->currentPage() === 1 && $products->total() === 1);
    }

    public function test_per_page_dropdown_announces_the_effective_fallback_for_an_unsupported_url_value(): void
    {
        Product::factory()->count(13)->create();
        $component = Livewire::test(Browser::class)->set('perPage', 20)
            ->assertViewHas('products', fn ($products) => $products->perPage() === 12);
        $dom = $this->dom($component->html());
        $this->assertStringContainsString('12 por página', $dom->getElementById('catalog-per-page-trigger')->textContent);
        $selected = (new \DOMXPath($dom))->query('//*[@id="catalog-per-page-options"]/button[@aria-checked="true"]');
        $this->assertCount(1, $selected);
        $this->assertSame('12 por página', trim($selected->item(0)->textContent));
    }

    private function dom(string $html): \DOMDocument
    {
        $dom = new \DOMDocument;
        @$dom->loadHTML('<?xml encoding="UTF-8">'.$html);

        return $dom;
    }
}
