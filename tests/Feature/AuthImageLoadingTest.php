<?php

namespace Tests\Feature;

use DOMDocument;
use DOMXPath;
use Tests\TestCase;

class AuthImageLoadingTest extends TestCase
{
    public function test_both_auth_pages_preload_the_same_eager_image_in_the_head(): void
    {
        foreach (['/login', '/register'] as $route) {
            $document = new DOMDocument;
            @$document->loadHTML($this->get($route)->assertOk()->getContent());
            $xpath = new DOMXPath($document);
            $preloads = $xpath->query('//head/link[@rel="preload" and @as="image"]');
            $this->assertSame(1, $preloads->length);
            $image = $xpath->query('//aside[contains(@class,"auth-benefits")]//img')->item(0);
            $this->assertSame($image->getAttribute('src'), $preloads->item(0)->getAttribute('href'));
            $this->assertSame('(min-width: 1024px)', $preloads->item(0)->getAttribute('media'));
            $this->assertSame('high', $preloads->item(0)->getAttribute('fetchpriority'));
            $this->assertSame('eager', $image->getAttribute('loading'));
            $this->assertSame('sync', $image->getAttribute('decoding'));
            $this->assertSame('high', $image->getAttribute('fetchpriority'));
            $this->assertSame('1774', $image->getAttribute('width'));
            $this->assertSame('887', $image->getAttribute('height'));
        }
    }

    public function test_unrelated_auth_pages_do_not_preload_the_panel_image(): void
    {
        $this->get('/forgot-password')->assertOk()->assertDontSee('images/login.png');
    }
}
