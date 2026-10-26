<?php

namespace Tests\Feature;

use App\Models\User;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class StorefrontHeaderTest extends TestCase
{
    use RefreshDatabase;

    private function header(string $path = '/'): DOMXPath
    {
        $html = $this->get($path)->assertOk()->getContent();
        $document = new DOMDocument;
        @$document->loadHTML('<?xml encoding="UTF-8">'.$html);

        return new DOMXPath($document);
    }

    public function test_guest_has_a_login_link_and_no_cart_in_the_header(): void
    {
        $xpath = $this->header();
        $this->assertSame(0, $xpath->query('//header//a[@aria-label="Carrinho"]')->length);
        $login = $xpath->query('//header//a[@href="'.route('login').'"]')->item(0);
        $this->assertSame('Entrar', trim($login->textContent));
        $this->assertStringContainsString('ds-button--primary', $login->getAttribute('class'));
        $this->assertSame(0, $xpath->query('//*[@id="storefront-account-menu"]')->length);
    }

    public function test_authenticated_user_keeps_cart_and_account_navigation(): void
    {
        $this->actingAs(User::factory()->create(['name' => 'Cliente Exemplo']));
        $xpath = $this->header();
        $this->assertSame(1, $xpath->query('//header//a[@href="'.route('cart.show').'" and @aria-label="Carrinho"]')->length);
        $control = $xpath->query('//header//button[@type="button" and @aria-controls="storefront-account-menu"]')->item(0);
        $this->assertNotNull($control);
        $this->assertSame('false', $control->getAttribute('aria-expanded'));
        $this->assertStringNotContainsString('Exemplo', $control->textContent.$control->getAttribute('aria-label').$control->getAttribute('title'));
        $this->assertSame('C', $xpath->query('//header//*[@class="storefront-user__avatar"]')->item(0)->textContent);
        $this->assertSame('Cliente', $xpath->query('//header//*[@class="storefront-user__name"]')->item(0)->textContent);
        $this->assertSame(0, $xpath->query('//header//a[@href="'.route('login').'"]')->length);
    }

    public function test_first_name_handles_unicode_spacing_long_names_and_html_safely(): void
    {
        foreach ([
            [" \tÉlodie\u{00a0}  Fictícia Um ", 'Élodie', 'É'],
            ['李 明', '李', '李'],
            [str_repeat('Á', 100).' Sobrenome', str_repeat('Á', 100), 'Á'],
            ['<script>alert(1)</script> Sobrenome', '<script>alert(1)</script>', '<'],
            ['   ', 'Conta', 'C'],
        ] as [$name, $first, $initial]) {
            $this->actingAs(User::factory()->create(['name' => $name]));
            $xpath = $this->header();
            $this->assertSame($first, $xpath->query('//header//*[@class="storefront-user__name"]')->item(0)?->textContent);
            $this->assertSame($initial, $xpath->query('//header//*[@class="storefront-user__avatar"]')->item(0)?->textContent);
            $this->assertSame(0, $xpath->query('//header//script')->length);
        }
    }

    public static function accountRoles(): array
    {
        return ['customer' => [false], 'administrator' => [true]];
    }

    #[DataProvider('accountRoles')]
    public function test_dropdown_has_exact_order_active_state_and_post_logout(bool $admin): void
    {
        $factory = User::factory();
        $this->actingAs(($admin ? $factory->admin() : $factory)->create());
        $xpath = $this->header('/dashboard');
        $menu = '//*[@id="storefront-account-menu"]';
        $links = fn ($scope) => array_map(fn ($link) => [$link->getAttribute('href'), $link->getAttribute('aria-current'), trim($link->textContent)], iterator_to_array($xpath->query($scope.'//nav/a')));
        $this->assertSame(0, $xpath->query('//aside')->length);
        $this->assertSame(['Visão geral', 'Perfil', 'Pedidos', 'Favoritos'], array_column($links($menu), 2));
        $this->assertSame([route('dashboard'), route('profile.show'), route('orders.index'), route('favorites.index')], array_column($links($menu), 0));
        $this->assertSame('page', $links($menu)[0][1]);
        $this->assertSame(0, $xpath->query($menu.'//a[@href="'.route('admin.dashboard').'"]')->length);
        $this->assertSame(1, $xpath->query($menu.'//form[@method="POST" and @action="'.route('logout').'"]//input[@name="_token"]')->length);
        $this->assertSame(1, $xpath->query($menu.'//form//button[@type="submit"]//svg')->length);
        $this->assertSame(0, $xpath->query('//header//a[@href="'.route('logout').'"]')->length);
        $this->post(route('logout'))->assertRedirect('/');
        $this->assertGuest();
    }

    public function test_search_preserves_get_query_and_accessible_submit_and_active_page(): void
    {
        $xpath = $this->header('/catalogo?q=produto+teste');
        $this->assertSame(1, $xpath->query('//header//form[@method="get" and @action="'.route('catalog.index').'" and @role="search"]')->length);
        $this->assertSame(1, $xpath->query('//header//input[@type="search" and @name="q" and @value="produto teste"]')->length);
        $this->assertSame(1, $xpath->query('//header//label[@for="storefront-search"]')->length);
        $this->assertSame(1, $xpath->query('//header//button[@type="submit" and @aria-label="Buscar produtos"]//svg')->length);
        $this->assertSame('Categorias', $xpath->query('//header//nav[@aria-label="Navegação principal"]/a[@aria-current="page"]')->item(0)->textContent);
        $this->assertSame('Início', $this->header()->query('//header//nav[@aria-label="Navegação principal"]/a[@aria-current="page"]')->item(0)->textContent);
    }

    public function test_global_logo_always_accents_plus_and_keeps_icon_only_option(): void
    {
        foreach (['<x-shop-logo />', '<x-shop-logo :accented="false" />'] as $template) {
            $html = Blade::render($template);
            $this->assertStringContainsString('Shop<span class="ds-shop-logo__accent">Plus</span>', $html);
            $this->assertStringContainsString('ds-shop-logo__icon', $html);
        }
        $this->assertStringNotContainsString('Plus', Blade::render('<x-shop-logo :label="false" />'));
    }
}
