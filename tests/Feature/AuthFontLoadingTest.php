<?php

namespace Tests\Feature;

use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class AuthFontLoadingTest extends TestCase
{
    use RefreshDatabase;

    public function test_auth_pages_use_local_figtree_and_preload_only_initial_weights(): void
    {
        foreach (['/login', '/register', '/forgot-password'] as $route) {
            $html = $this->get($route)->assertOk()->getContent();
            $this->assertStringNotContainsString('fonts.bunny.net', $html);
            $this->assertStringNotContainsString('fonts.googleapis.com', $html);
            $document = new DOMDocument;
            @$document->loadHTML($html);
            $xpath = new DOMXPath($document);
            $fonts = $xpath->query('//head/link[@rel="preload" and @as="font"]');
            $this->assertSame(3, $fonts->length);
            foreach ([400, 600, 700] as $index => $weight) {
                $font = $fonts->item($index);
                $this->assertStringEndsWith('/fonts/figtree/figtree-latin-'.$weight.'-normal.woff2', $font->getAttribute('href'));
                $this->assertSame('font/woff2', $font->getAttribute('type'));
                $this->assertSame('anonymous', $font->getAttribute('crossorigin'));
                $this->assertFileExists(public_path('fonts/figtree/figtree-latin-'.$weight.'-normal.woff2'));
            }
            foreach ([400, 500, 600, 700] as $weight) {
                $this->assertStringContainsString('font-weight: '.$weight.';', $html);
                $this->assertFileExists(public_path('fonts/figtree/figtree-latin-ext-'.$weight.'-normal.woff2'));
            }
            $this->assertStringContainsString('font-display: swap;', $html);
            $this->assertFileExists(public_path('fonts/figtree/OFL.txt'));
        }
    }

    public function test_error_weight_is_preloaded_when_server_errors_are_visible(): void
    {
        $errors = (new ViewErrorBag)->put('default', new MessageBag(['email' => 'Synthetic validation error.']));
        $html = $this->withSession(['errors' => $errors])->get('/login')->assertOk()->getContent();
        $document = new DOMDocument;
        @$document->loadHTML($html);
        $xpath = new DOMXPath($document);
        $this->assertSame(1, $xpath->query('//head/link[@as="font" and contains(@href,"latin-500-normal.woff2")]')->length);
    }

    public function test_storefront_and_other_auth_pages_keep_the_existing_font_delivery(): void
    {
        foreach (['/', '/reset-password/synthetic-token'] as $route) {
            $this->get($route)->assertOk()
                ->assertSee('https://fonts.bunny.net/css?family=figtree:', false)
                ->assertDontSee('/fonts/figtree/', false);
        }
    }
}
