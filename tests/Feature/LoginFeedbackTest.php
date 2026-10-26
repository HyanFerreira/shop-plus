<?php

namespace Tests\Feature;

use App\Models\User;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class LoginFeedbackTest extends TestCase
{
    use RefreshDatabase;

    private function xpath(string $html): DOMXPath
    {
        $document = new DOMDocument;
        @$document->loadHTML('<?xml encoding="UTF-8">'.$html);

        return new DOMXPath($document);
    }

    public function test_rejected_credentials_are_a_generic_alert_without_marking_individual_fields(): void
    {
        $user = User::factory()->create();
        foreach ([$user->email, 'unknown@example.test'] as $email) {
            $this->from('/login')->post('/login', ['email' => $email, 'password' => 'NeverReflectThis123!'])
                ->assertSessionHasErrors('email');
            $response = $this->get('/login')->assertOk()
                ->assertSee('Não foi possível entrar. Verifique suas credenciais e tente novamente.')
                ->assertDontSee('NeverReflectThis123!');
            $xpath = $this->xpath($response->getContent());
            $this->assertSame(1, $xpath->query('//*[@id="login-general-error" and @role="alert"]')->length);
            foreach (['email', 'password'] as $field) {
                $input = $xpath->query('//input[@id="'.$field.'"]')->item(0);
                $this->assertSame('false', $input->getAttribute('aria-invalid'));
                $this->assertFalse($input->hasAttribute('aria-describedby'));
            }
            $this->assertGuest();
        }
    }

    public function test_server_email_validation_stays_on_the_email_with_a_decorative_alert_icon(): void
    {
        $this->from('/login')->post('/login', ['email' => 'not-an-email', 'password' => 'synthetic-password'])
            ->assertSessionHasErrors('email');
        $xpath = $this->xpath($this->get('/login')->assertOk()->getContent());
        $input = $xpath->query('//input[@id="email"]')->item(0);
        $this->assertSame('true', $input->getAttribute('aria-invalid'));
        $this->assertSame('email-error', $input->getAttribute('aria-describedby'));
        $this->assertSame(1, $xpath->query('//*[@id="email-error" and not(@hidden)]//svg[@aria-hidden="true"]')->length);
        $this->assertSame(0, $xpath->query('//*[@id="login-general-error"]')->length);
    }

    public function test_missing_fields_from_server_have_individual_messages_and_required_attributes(): void
    {
        $this->from('/login')->post('/login', [])->assertSessionHasErrors(['email', 'password']);
        $xpath = $this->xpath($this->get('/login')->assertOk()->getContent());
        foreach (['email', 'password'] as $field) {
            $this->assertSame(1, $xpath->query('//input[@id="'.$field.'" and @required and @aria-invalid="true" and @aria-describedby="'.$field.'-error"]')->length);
            $this->assertSame(1, $xpath->query('//*[@id="'.$field.'-error" and not(@hidden)]//svg[@aria-hidden="true"]')->length);
        }
        $this->assertSame(0, $xpath->query('//*[@id="login-general-error"]')->length);
    }

    public function test_server_field_messages_are_escaped_and_password_is_not_repopulated(): void
    {
        $errors = (new ViewErrorBag)->put('default', new MessageBag(['password' => '<script>not-executable</script>']));
        $response = $this->withSession(['errors' => $errors])->get('/login')->assertOk();
        $response->assertSee('&lt;script&gt;not-executable&lt;/script&gt;', false)->assertDontSee('<script>not-executable</script>', false);
        $xpath = $this->xpath($response->getContent());
        $this->assertSame('', $xpath->query('//input[@id="password"]')->item(0)->getAttribute('value'));
        $this->assertSame(1, $xpath->query('//*[@id="password-error" and not(@hidden)]//svg')->length);
    }

    public function test_lockout_message_remains_general_and_does_not_mark_the_email(): void
    {
        $message = trans('auth.throttle', ['seconds' => 30, 'minutes' => 1]);
        $errors = (new ViewErrorBag)->put('default', new MessageBag(['email' => $message]));
        $response = $this->withSession(['errors' => $errors])->get('/login')->assertOk()->assertSee($message);
        $xpath = $this->xpath($response->getContent());
        $this->assertSame(1, $xpath->query('//*[@id="login-general-error"]')->length);
        $this->assertSame('false', $xpath->query('//input[@id="email"]')->item(0)->getAttribute('aria-invalid'));
    }

    public function test_login_rate_limit_still_rejects_the_sixth_attempt(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->from('/login')->post('/login', ['email' => 'limited@example.test', 'password' => 'invalid-password'])
                ->assertRedirect('/login');
        }
        $this->post('/login', ['email' => 'limited@example.test', 'password' => 'invalid-password'])->assertStatus(429);
        $this->assertGuest();
    }
}
