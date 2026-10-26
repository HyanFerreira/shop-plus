<?php

namespace Tests\Feature;

use App\Models\User;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class StorefrontStructureTest extends TestCase
{
    use RefreshDatabase;

    public static function storefrontPages(): array
    {
        return [
            'home visitor' => ['/', false],
            'catalog visitor' => ['/catalogo', false],
            'home authenticated' => ['/', true],
            'catalog authenticated' => ['/catalogo', true],
        ];
    }

    #[DataProvider('storefrontPages')]
    public function test_shared_structure_keeps_navigation_search_and_account_accessible(string $path, bool $authenticated): void
    {
        if ($authenticated) {
            $this->actingAs(User::factory()->create(['name' => 'Alexandremuitolongoparatestar Silva']));
        }

        $response = $this->get($path)->assertOk();
        $dom = new DOMDocument;
        @$dom->loadHTML('<?xml encoding="UTF-8">'.$response->getContent());
        $xpath = new DOMXPath($dom);

        $this->assertSame(1, $xpath->query('//header')->length);
        $this->assertSame(1, $xpath->query('//footer')->length);
        $this->assertSame(1, $xpath->query('//main')->length);
        $this->assertSame(1, $xpath->query('//header//nav[@aria-label="Navegação principal"]')->length);
        $this->assertSame(4, $xpath->query('//header//nav[@aria-label="Navegação principal"]/a')->length);
        $this->assertSame(1, $xpath->query('//header//form[@role="search"]')->length);
        $this->assertSame(1, $xpath->query('//header//input[@name="q"]')->length);
        $this->assertSame(1, $xpath->query('//header//button[@type="submit"][@aria-label="Buscar produtos"]')->length);
        $this->assertSame($authenticated ? 1 : 0, $xpath->query('//header//a[@aria-label="Carrinho"]')->length);

        $accountUrl = route($authenticated ? 'dashboard' : 'login');
        $this->assertSame(1, $xpath->query('//header//a[@href="'.$accountUrl.'"]')->length);
        $this->assertNotNull($dom->getElementById('storefront-content'));
        $this->assertSame('-1', $dom->getElementById('storefront-content')->getAttribute('tabindex'));
        $this->assertSame(1, $xpath->query('//header/a[@href="#storefront-content"]')->length);
    }

    public static function searchPages(): array
    {
        return [['/'], ['/catalogo']];
    }

    #[DataProvider('searchPages')]
    public function test_header_search_preserves_the_existing_catalog_query_contract(string $path): void
    {
        $query = 'Mouse & "teclado"';
        $response = $this->get($path.'?'.http_build_query(['q' => $query]))->assertOk();
        $dom = new DOMDocument;
        @$dom->loadHTML('<?xml encoding="UTF-8">'.$response->getContent());
        $xpath = new DOMXPath($dom);
        $form = $xpath->query('//header//form')->item(0);
        $input = $xpath->query('//header//input[@name="q"]')->item(0);

        $this->assertSame(route('catalog.index'), $form->getAttribute('action'));
        $this->assertSame('get', strtolower($form->getAttribute('method')));
        $this->assertNotNull($input);
        $this->assertSame($query, $input->getAttribute('value'));
    }
}
