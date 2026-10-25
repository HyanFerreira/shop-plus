<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiAndInfrastructureTest extends TestCase
{
    use RefreshDatabase;

    public function test_disabled_account_cannot_use_a_previously_issued_api_token(): void
    {
        $user = User::factory()->disabled()->create();
        $token = $user->createToken('synthetic-test')->plainTextToken;
        $this->withToken($token)->getJson('/api/user')->assertForbidden();
    }

    public function test_active_api_user_is_private_and_secrets_are_not_serialized(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('synthetic-test')->plainTextToken;
        $response = $this->withToken($token)->getJson('/api/user')->assertOk()
            ->assertJsonPath('id', $user->id)->assertJsonMissingPath('password')->assertJsonMissingPath('remember_token')
            ->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }

    public function test_health_endpoint_has_security_headers(): void
    {
        $this->get('/up')->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
    }
}
