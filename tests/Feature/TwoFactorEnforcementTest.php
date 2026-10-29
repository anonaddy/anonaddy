<?php

namespace Tests\Feature;

use App\Facades\Webauthn;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\ThrottleRequestsWithRedis;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use PragmaRX\Google2FALaravel\Support\Constants;
use Tests\TestCase;

class TwoFactorEnforcementTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected string $secret;

    protected function setUp(): void
    {
        parent::setUp();

        $this->secret = app('pragmarx.google2fa')->generateSecretKey();

        $this->user = $this->createUser('johndoe', null, [
            'password' => Hash::make('mypassword'),
            'two_factor_secret' => $this->secret,
            'two_factor_enabled' => true,
        ]);
    }

    #[Test]
    public function session_that_has_not_passed_two_factor_cannot_use_the_api(): void
    {
        $this->actingAs($this->user)
            ->withHeader('Referer', config('app.url'))
            ->getJson('/api/v1/aliases')
            ->assertForbidden()
            ->assertExactJson(['message' => 'Two-factor authentication required.']);
    }

    #[Test]
    public function session_that_has_not_passed_two_factor_cannot_delete_the_account_via_the_api(): void
    {
        $this->actingAs($this->user)
            ->withHeader('Referer', config('app.url'))
            ->postJson('/api/auth/delete-account', ['password' => 'mypassword'])
            ->assertForbidden();

        $this->assertModelExists($this->user);
    }

    #[Test]
    public function inertia_visit_sends_an_unverified_authenticator_session_to_the_authenticator_page(): void
    {
        $this->actingAs($this->user)
            ->withHeaders($this->inertiaHeaders())
            ->get(route('settings.security'))
            ->assertStatus(409)
            ->assertHeader('X-Inertia-Location', route('login.2fa.index'));
    }

    #[Test]
    public function inertia_visit_sends_an_unverified_security_key_session_to_the_security_key_page(): void
    {
        $this->user->update([
            'two_factor_enabled' => false,
            'webauthn_enabled' => true,
        ]);

        $this->actingAs($this->user)
            ->withHeaders($this->inertiaHeaders())
            ->get(route('settings.security'))
            ->assertStatus(409)
            ->assertHeader('X-Inertia-Location', route('webauthn.login'));
    }

    #[Test]
    public function browser_visit_still_redirects_a_security_key_user_to_the_security_key_page(): void
    {
        $this->user->update([
            'two_factor_enabled' => false,
            'webauthn_enabled' => true,
        ]);

        $this->actingAs($this->user)
            ->get(route('settings.security'))
            ->assertRedirect(route('webauthn.login'));
    }

    #[Test]
    public function inertia_visit_continues_when_the_passed_factor_is_still_enabled(): void
    {
        $this->user->update(['webauthn_enabled' => true]);

        $this->actingAs($this->user)
            ->withSession([$this->totpSessionKey() => true])
            ->withHeaders($this->inertiaHeaders())
            ->get(route('settings.security'))
            ->assertOk();
    }

    #[Test]
    public function security_page_reports_which_factor_this_session_passed(): void
    {
        $this->actingAs($this->user)
            ->withSession([$this->totpSessionKey() => true])
            ->get(route('settings.security'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('sessionPassedTotp', true)
                ->where('sessionPassedWebauthn', false)
            );
    }

    #[Test]
    public function session_that_has_passed_two_factor_can_use_the_api(): void
    {
        $this->actingAs($this->user)
            ->withSession([$this->totpSessionKey() => true])
            ->withHeader('Referer', config('app.url'))
            ->getJson('/api/v1/aliases')
            ->assertOk();
    }

    #[Test]
    public function api_key_can_use_the_api_without_a_two_factor_session(): void
    {
        Sanctum::actingAs($this->user, []);

        $this->getJson('/api/v1/aliases')->assertOk();
    }

    #[Test]
    public function one_time_password_is_ignored_outside_the_two_factor_login_route(): void
    {
        $this->actingAs($this->user)
            ->get('/?one_time_password='.app('pragmarx.google2fa')->getCurrentOtp($this->secret))
            ->assertSessionMissing($this->totpSessionKey());
    }

    #[Test]
    public function two_factor_login_route_is_throttled_before_checking_the_one_time_password(): void
    {
        $this->actingAs($this->user);

        foreach (range(1, 3) as $attempt) {
            $this->post(route('login.2fa'), ['one_time_password' => '000000']);
        }

        $this->post(route('login.2fa'), [
            'one_time_password' => app('pragmarx.google2fa')->getCurrentOtp($this->secret),
        ])->assertTooManyRequests();

        $this->assertNull(session($this->totpSessionKey()));
    }

    #[Test]
    public function session_that_has_not_passed_two_factor_cannot_register_a_webauthn_key(): void
    {
        $this->actingAs($this->user)
            ->post('/webauthn/keys/options')
            ->assertViewIs(config('google2fa.view'));

        $this->assertFalse(Webauthn::canRegister($this->user));
    }

    #[Test]
    public function session_that_has_passed_two_factor_can_register_a_webauthn_key(): void
    {
        $this->actingAs($this->user)
            ->withSession([$this->totpSessionKey() => true])
            ->get(route('settings.security'));

        $this->assertTrue(Webauthn::canRegister($this->user));
    }

    #[Test]
    public function session_that_has_not_passed_two_factor_cannot_enable_a_webauthn_key(): void
    {
        $key = $this->user->webauthnKeys()->create([
            'name' => 'YubiKey',
            'enabled' => false,
            'credentialId' => 'xyz'.str()->random(8),
            'type' => 'public-key',
            'transports' => [],
            'attestationType' => 'none',
            'trustPath' => '{"type":"Webauthn\\TrustPath\\EmptyTrustPath"}',
            'aaguid' => '00000000-0000-0000-0000-000000000000',
            'credentialPublicKey' => 'xyz',
            'counter' => 0,
        ]);

        $this->actingAs($this->user)
            ->post(route('webauthn.enabled_key.store'), ['id' => $key->id]);

        $this->assertFalse($key->refresh()->enabled);
        $this->assertFalse($this->user->refresh()->webauthn_enabled);
    }

    #[Test]
    public function webauthn_only_user_that_has_not_passed_two_factor_cannot_register_another_key(): void
    {
        $user = $this->createUser(null, null, ['webauthn_enabled' => true]);

        $this->actingAs($user)->get(route('settings.security'));

        $this->assertFalse(Webauthn::canRegister($user));
    }

    #[Test]
    public function api_mfa_attempts_are_limited_per_user_across_ip_addresses(): void
    {
        $this->withoutMiddleware(ThrottleRequestsWithRedis::class);

        $mfaKey = $this->json('POST', '/api/auth/login', [
            'username' => 'johndoe',
            'password' => 'mypassword',
            'device_name' => 'Firefox',
        ])->json('mfa_key');

        foreach (range(1, 3) as $attempt) {
            $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.'.$attempt])
                ->json('POST', '/api/auth/mfa', [
                    'mfa_key' => $mfaKey,
                    'otp' => '000000',
                    'device_name' => 'Firefox',
                ])->assertUnauthorized();
        }

        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.4'])
            ->json('POST', '/api/auth/mfa', [
                'mfa_key' => $mfaKey,
                'otp' => app('pragmarx.google2fa')->getCurrentOtp($this->secret),
                'device_name' => 'Firefox',
            ])->assertTooManyRequests();

        $this->assertSame(0, $this->user->tokens()->count());
    }

    protected function totpSessionKey(): string
    {
        return config('google2fa.session_var').'.'.Constants::SESSION_AUTH_PASSED;
    }

    /**
     * @return array<string, string>
     */
    protected function inertiaHeaders(): array
    {
        return [
            'X-Inertia' => 'true',
            'X-Inertia-Version' => (string) app(HandleInertiaRequests::class)->version(Request::create('/')),
        ];
    }
}
