<?php

namespace Tests\Feature;

use App\Models\User;
use DOMDocument;
use DOMXPath;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class RecoveryPresentationTest extends TestCase
{
    use RefreshDatabase;

    private function xpath(string $html): DOMXPath
    {
        $document = new DOMDocument;
        @$document->loadHTML('<?xml encoding="UTF-8">'.$html);

        return new DOMXPath($document);
    }

    public function test_recovery_uses_one_card_with_brand_header_and_the_exact_existing_png(): void
    {
        $html = $this->get('/forgot-password')->assertOk()->getContent();
        $xpath = $this->xpath($html);
        $this->assertSame(1, $xpath->query('//main[contains(@class,"auth-page--recovery")]')->length);
        $this->assertSame(0, $xpath->query('//section[contains(@class,"auth-card--split")]')->length);
        $this->assertSame(1, $xpath->query('//header[contains(@class,"auth-recovery-header")]//a[contains(@class,"ds-shop-logo")]')->length);
        $this->assertSame(1, $xpath->query('//img[contains(@src,"images/forgout-password.png") and @width="1536" and @height="1024" and @loading="eager"]')->length);
        $this->assertSame('senha', $xpath->query('//h1/span')->item(0)->textContent);
        $this->assertSame(1, $xpath->query('//form[@action="'.route('password.email').'" and @method="POST" and @data-auth-validation="recovery"]')->length);
        $this->assertSame(1, $xpath->query('//form//input[@name="_token"]')->length);
        $this->assertSame(1, $xpath->query('//input[@name="email" and @required and @maxlength="255" and @data-auth-field="email"]/preceding-sibling::svg')->length);
        $this->assertSame(1, $xpath->query('//*[@id="email-error" and @hidden]//svg')->length);
        $this->assertStringNotContainsString('fonts.bunny.net', $html);
        $this->assertSame(1, $xpath->query('//a[@href="'.route('login').'" and contains(@class,"auth-link")]//svg')->length);
    }

    public function test_field_errors_are_accessible_escaped_and_do_not_duplicate_the_general_alert(): void
    {
        $this->from('/forgot-password')->post('/forgot-password', ['email' => 'invalid'])->assertSessionHasErrors('email');
        $xpath = $this->xpath($this->get('/forgot-password')->assertOk()->getContent());
        $this->assertSame(1, $xpath->query('//input[@id="email" and @aria-invalid="true" and @aria-describedby="email-error"]')->length);
        $this->assertSame(1, $xpath->query('//*[@id="email-error" and not(@hidden)]//svg')->length);
        $this->assertSame(0, $xpath->query('//*[@id="recovery-general-error"]')->length);
        $errors = (new ViewErrorBag)->put('default', new MessageBag(['email' => '<script>synthetic</script>']));
        $this->withSession(['errors' => $errors])->get('/forgot-password')->assertOk()
            ->assertSee('&lt;script&gt;synthetic&lt;/script&gt;', false)->assertDontSee('<script>synthetic</script>', false);
    }

    public function test_real_status_is_neutral_for_known_and_unknown_accounts(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'recovery@example.test']);
        foreach ([$user->email, 'unknown@example.test'] as $email) {
            $this->from('/forgot-password')->post('/forgot-password', ['email' => $email])
                ->assertRedirect('/forgot-password')->assertSessionHas('status', trans('passwords.sent'));
            $response = $this->get('/forgot-password')->assertOk()->assertSee(trans('passwords.sent'));
            $xpath = $this->xpath($response->getContent());
            $this->assertSame(1, $xpath->query('//*[@id="recovery-status" and @role="alert"]//svg')->length);
        }
        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_general_server_feedback_keeps_its_message_and_is_above_the_form(): void
    {
        $errors = (new ViewErrorBag)->put('default', new MessageBag(['recovery' => 'Muitas tentativas. Aguarde antes de tentar novamente.']));
        $xpath = $this->xpath($this->withSession(['errors' => $errors])->get('/forgot-password')->assertOk()->getContent());
        $this->assertSame(1, $xpath->query('//*[@id="recovery-general-error" and @role="alert"]/following-sibling::form')->length);
    }

    public function test_required_validation_and_existing_rate_limit_remain_authoritative(): void
    {
        $this->from('/forgot-password')->post('/forgot-password', [])->assertSessionHasErrors('email');
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->from('/forgot-password')->post('/forgot-password', ['email' => 'limited-recovery@example.test'])->assertRedirect('/forgot-password');
        }
        $this->post('/forgot-password', ['email' => 'limited-recovery@example.test'])->assertStatus(429);
        $this->assertGuest();
    }
}
