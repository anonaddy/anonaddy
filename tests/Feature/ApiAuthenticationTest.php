<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequestsWithRedis;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ApiAuthenticationTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'password' => Hash::make('mypassword'),
        ])->fresh();

        $this->user->usernames()->save($this->user->defaultUsername);
        $this->user->defaultUsername->username = 'johndoe';
        $this->user->defaultUsername->save();
    }

    /** @test */
    public function user_can_retreive_valid_access_token()
    {
        $this->withoutMiddleware(ThrottleRequestsWithRedis::class);

        $response = $this->json('POST', '/api/auth/login', [
            'username' => 'johndoe',
            'password' => 'mypassword',
            'device_name' => 'Firefox',
        ]);

        $response->assertSuccessful();
        $this->assertEquals($this->user->tokens[0]->token, hash('sha256', $response->json()['api_key']));
    }

    /** @test */
    public function user_password_must_be_correct_to_get_access_token()
    {
        $this->withoutMiddleware(ThrottleRequestsWithRedis::class);

        $response = $this->json('POST', '/api/auth/login', [
            'username' => 'johndoe',
            'password' => 'myincorrectpassword',
            'device_name' => 'Firefox',
        ]);

        $response->assertUnauthorized();
    }

    /** @test */
    public function user_must_exist_to_get_access_token()
    {
        $this->withoutMiddleware(ThrottleRequestsWithRedis::class);

        $response = $this->json('POST', '/api/auth/login', [
            'username' => 'doesnotexist',
            'password' => 'mypassword',
            'device_name' => 'Firefox',
        ]);

        $response->assertUnauthorized();
        $response->assertExactJson(['error' => 'The provided credentials are incorrect']);
    }

    /** @test */
    public function user_is_throttled_by_middleware_for_too_many_requests()
    {
        $this->json('POST', '/api/auth/login', [
            'username' => 'johndoe',
            'password' => 'incorrect1',
            'device_name' => 'Firefox',
        ]);

        $this->json('POST', '/api/auth/login', [
            'username' => 'johndoe',
            'password' => 'incorrect2',
            'device_name' => 'Firefox',
        ]);

        $this->json('POST', '/api/auth/login', [
            'username' => 'johndoe',
            'password' => 'incorrect3',
            'device_name' => 'Firefox',
        ]);

        $response = $this->json('POST', '/api/auth/login', [
            'username' => 'johndoe',
            'password' => 'incorrect4',
            'device_name' => 'Firefox',
        ]);

        $response->assertStatus(429);
    }

    /** @test */
    public function user_cannot_get_access_token_with_webauthn_enabled()
    {
        $this->withoutMiddleware(ThrottleRequestsWithRedis::class);

        $this->user->webauthnKeys()->create([
            'name' => 'key',
            'enabled' => true,
            'credentialId' => 'xyz',
            'type' => 'public-key',
            'transports' => [],
            'attestationType' => 'none',
            'trustPath' => '{"type":"Webauthn\\TrustPath\\EmptyTrustPath"}',
            'aaguid' => '00000000-0000-0000-0000-000000000000',
            'credentialPublicKey' => 'xyz',
            'counter' => 0,
        ]);

        $response = $this->json('POST', '/api/auth/login', [
            'username' => 'johndoe',
            'password' => 'mypassword',
            'device_name' => 'Firefox',
        ]);

        $response->assertForbidden();
        $response->assertExactJson(['error' => 'WebAuthn authentication is not currently supported from the extension or mobile apps, please use an API key to login instead']);
    }

    /** @test */
    public function user_must_provide_correct_otp_if_enabled()
    {
        $this->withoutMiddleware(ThrottleRequestsWithRedis::class);

        $secret = app('pragmarx.google2fa')->generateSecretKey();
        $this->user->update([
            'two_factor_secret' => $secret,
            'two_factor_enabled' => true,
        ]);

        $response = $this->json('POST', '/api/auth/login', [
            'username' => 'johndoe',
            'password' => 'mypassword',
            'device_name' => 'Firefox',
        ]);

        $response->assertStatus(422);

        $mfaKey = $response->json()['mfa_key'];
        $csrfToken = $response->json()['csrf_token'];
        $this->assertNotNull($mfaKey);
        $this->assertNotNull($csrfToken);

        $response2 = $this->withHeaders([
            'X-CSRF-TOKEN' => $csrfToken,
        ])->json('POST', '/api/auth/mfa', [
            'mfa_key' => $mfaKey,
            'otp' => '000000',
            'device_name' => 'Firefox',
        ]);

        $response2->assertUnauthorized();
        $response2->assertExactJson(['error' => 'The \'One Time Password\' typed was wrong']);

        $response3 = $this->withHeaders([
            'X-CSRF-TOKEN' => $csrfToken,
        ])->json('POST', '/api/auth/mfa', [
            'mfa_key' => $mfaKey,
            'otp' => app('pragmarx.google2fa')->getCurrentOtp($secret),
            'device_name' => 'Firefox',
        ]);

        $response3->assertSuccessful();
        $this->assertEquals($this->user->tokens[0]->token, hash('sha256', $response3->json()['api_key']));
    }
}
