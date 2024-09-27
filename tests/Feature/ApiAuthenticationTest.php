<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequestsWithRedis;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ApiAuthenticationTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->createUser('johndoe', null, ['password' => Hash::make('mypassword')]);
    }

    #[Test]
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

    #[Test]
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

    #[Test]
    public function user_must_exist_to_get_access_token()
    {
        $this->withoutMiddleware(ThrottleRequestsWithRedis::class);

        $response = $this->json('POST', '/api/auth/login', [
            'username' => 'doesnotexist',
            'password' => 'mypassword',
            'device_name' => 'Firefox',
        ]);

        $response->assertUnauthorized();
        $response->assertExactJson(['message' => 'The provided credentials are incorrect.']);
    }

    #[Test]
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

    #[Test]
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
        $response->assertExactJson(['message' => 'Security key authentication is not currently supported from the extension or mobile apps, please use an API key to login instead.']);
    }

    #[Test]
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
        $response2->assertExactJson(['message' => 'The \'One Time Password\' typed was wrong.']);

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

    #[Test]
    public function user_can_logout_via_api()
    {
        $this->withoutMiddleware(ThrottleRequestsWithRedis::class);

        $this->user->defaultUsername->username = 'janedoe';
        $this->user->defaultUsername->save();

        $token = $this->user->createToken('New');

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->json('POST', '/api/auth/logout', []);

        $response->assertStatus(204);
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $this->user->id,
        ]);
    }

    #[Test]
    public function user_can_delete_account_via_api()
    {
        $this->withoutMiddleware(ThrottleRequestsWithRedis::class);

        $this->user->defaultUsername->username = 'janedoe';
        $this->user->defaultUsername->save();

        $token = $this->user->createToken('New');

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->json('POST', '/api/auth/delete-account', [
            'password' => 'mypassword',
        ]);

        $response->assertStatus(204);
        $this->assertDatabaseMissing('usernames', [
            'username' => 'janedoe',
        ]);
    }

    #[Test]
    public function user_must_enter_correct_password_to_delete_account()
    {
        $this->withoutMiddleware(ThrottleRequestsWithRedis::class);

        $this->user->defaultUsername->username = 'janedoe';
        $this->user->defaultUsername->save();

        $token = $this->user->createToken('New');

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->json('POST', '/api/auth/delete-account', [
            'password' => 'incorrect',
        ]);

        $response->assertJsonValidationErrorFor('password');
        $this->assertDatabaseHas('usernames', [
            'username' => 'janedoe',
        ]);
    }

    #[Test]
    public function user_must_have_valid_api_key_to_delete_account()
    {
        $this->withoutMiddleware(ThrottleRequestsWithRedis::class);

        $this->user->defaultUsername->username = 'janedoe';
        $this->user->defaultUsername->save();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer invalid-api-key',
        ])->json('POST', '/api/auth/delete-account', [
            'password' => 'mypassword',
        ]);

        $response->assertUnauthorized();
        $this->assertDatabaseHas('usernames', [
            'username' => 'janedoe',
        ]);
    }
}
