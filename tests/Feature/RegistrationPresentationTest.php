<?php

namespace Tests\Feature;

use App\Models\User;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class RegistrationPresentationTest extends TestCase
{
    use RefreshDatabase;

    private function xpath(string $html): DOMXPath
    {
        $document = new DOMDocument;
        @$document->loadHTML('<?xml encoding="UTF-8">'.$html);

        return new DOMXPath($document);
    }

    public function test_registration_and_login_render_the_same_brand_panel_and_asset(): void
    {
        $panels = [];
        foreach (['/login', '/register'] as $route) {
            $xpath = $this->xpath($this->get($route)->assertOk()->getContent());
            $panel = $xpath->query('//aside[contains(@class,"auth-benefits")]')->item(0);
            $panels[] = $panel->ownerDocument->saveHTML($panel);
            $this->assertSame(3, $xpath->query('//aside[contains(@class,"auth-benefits")]//li')->length);
            $this->assertSame(1, $xpath->query('//aside[contains(@class,"auth-benefits")]//img[contains(@src,"images/login.png")]')->length);
            $this->assertSame(1, $xpath->query('//main[contains(@class,"auth-page--branded")]')->length);
        }
        $this->assertSame($panels[0], $panels[1]);
    }

    public function test_registration_has_accessible_icon_fields_limits_and_error_targets(): void
    {
        $xpath = $this->xpath($this->get('/register')->assertOk()->getContent());
        foreach (['name' => 255, 'email' => 255, 'password' => 128, 'password_confirmation' => 128] as $name => $limit) {
            $this->assertSame(1, $xpath->query('//input[@name="'.$name.'" and @required and @maxlength="'.$limit.'" and @aria-invalid="false"]')->length);
            $this->assertSame(1, $xpath->query('//*[@id="'.$name.'-error" and @hidden]//svg[@aria-hidden="true"]')->length);
            $this->assertSame(1, $xpath->query('//input[@name="'.$name.'"]/preceding-sibling::svg')->length);
        }
        $this->assertSame(2, $xpath->query('//button[@type="button" and @aria-controls and @aria-label]')->length);
        $this->assertSame('conta', $xpath->query('//h1/span')->item(0)->textContent);
        $this->assertSame(1, $xpath->query('//form[@method="POST"]//input[@name="_token"]')->length);
    }

    public function test_server_errors_are_escaped_and_associated_without_reflecting_passwords(): void
    {
        $messages = array_fill_keys(['name', 'email', 'password', 'password_confirmation'], '<script>synthetic-error</script>');
        $errors = (new ViewErrorBag)->put('default', new MessageBag($messages));
        $response = $this->withSession(['errors' => $errors, '_old_input' => ['name' => 'Synthetic User', 'password' => 'NeverReflect123!', 'password_confirmation' => 'NeverReflect123!']])
            ->get('/register')->assertOk()->assertDontSee('NeverReflect123!')->assertDontSee('<script>synthetic-error</script>', false);
        $xpath = $this->xpath($response->getContent());
        foreach (array_keys($messages) as $name) {
            $this->assertSame(1, $xpath->query('//input[@name="'.$name.'" and @aria-invalid="true" and @aria-describedby="'.$name.'-error"]')->length);
            $this->assertSame(1, $xpath->query('//*[@id="'.$name.'-error" and not(@hidden)]//svg')->length);
        }
    }

    public function test_authoritative_registration_validation_still_rejects_duplicate_email_and_invalid_fields(): void
    {
        User::factory()->create(['email' => 'duplicate@example.test']);
        $this->from('/register')->post('/register', [
            'name' => str_repeat('n', 256), 'email' => 'duplicate@example.test',
            'password' => 'short', 'password_confirmation' => 'different',
        ])->assertSessionHasErrors(['name', 'email', 'password']);
        $this->get('/register')->assertOk()->assertSee('email-error')->assertSee('aria-invalid="true"', false);
        $this->assertGuest();
        $this->assertDatabaseCount('users', 1);
    }

    public function test_confirmation_errors_from_fortify_are_shown_at_confirmation(): void
    {
        $this->from('/register')->post('/register', [
            'name' => 'Synthetic User', 'email' => 'confirmation@example.test',
            'password' => 'Synthetic123!', 'password_confirmation' => 'Different123!',
        ])->assertSessionHasErrors('password');
        $xpath = $this->xpath($this->get('/register')->assertOk()->getContent());
        $this->assertSame(1, $xpath->query('//input[@id="password_confirmation" and @aria-invalid="true" and @aria-describedby="password_confirmation-error"]')->length);
        $this->assertSame(1, $xpath->query('//input[@id="password" and @aria-invalid="false"]')->length);
        $this->assertDatabaseCount('users', 0);
    }

    public function test_registration_backend_still_enforces_required_format_policy_and_maximums(): void
    {
        $this->from('/register')->post('/register', [])->assertSessionHasErrors(['name', 'email', 'password']);
        foreach ([['invalid', 'Synthetic123!', ['email']], ['short@example.test', 'short', ['password']], [str_repeat('e', 256).'@example.test', str_repeat('p', 129), ['email', 'password']]] as [$email, $password, $expectedErrors]) {
            $this->from('/register')->post('/register', [
                'name' => 'Synthetic User', 'email' => $email,
                'password' => $password, 'password_confirmation' => $password,
            ])->assertSessionHasErrors($expectedErrors);
        }
        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
    }
}
