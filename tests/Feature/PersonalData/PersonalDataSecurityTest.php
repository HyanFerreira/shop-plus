<?php

namespace Tests\Feature\PersonalData;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PersonalDataSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_personal_data_page_is_private_and_not_cacheable(): void
    {
        $response = $this->actingAs(User::factory()->create())
            ->get('/meus-dados')
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY');

        $cacheControl = $response->headers->get('Cache-Control', '');

        $this->assertStringContainsString('no-store', $cacheControl);
        $this->assertStringContainsString('private', $cacheControl);
    }
}
