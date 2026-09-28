<?php

namespace Tests\Feature;

use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    public function test_successful_web_responses_include_baseline_security_headers(): void
    {
        $this->assertSecurityHeaders($this->get('/')->assertOk());
    }

    public function test_authentication_redirects_include_baseline_security_headers(): void
    {
        $this->assertSecurityHeaders(
            $this->get('/dashboard')->assertRedirect('/login')
        );
    }

    private function assertSecurityHeaders(TestResponse $response): void
    {
        $response
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
    }
}
