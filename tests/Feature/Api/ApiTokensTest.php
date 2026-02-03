<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ApiTokensTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->createUser('johndoe', null, ['password' => Hash::make('mypassword')]);
        Sanctum::actingAs($this->user, [], 'web');
    }

    #[Test]
    public function user_can_create_api_token()
    {
        $response = $this->post('/settings/personal-access-tokens', [
            'name' => 'New',
            'password' => 'mypassword',
        ]);

        $response->assertStatus(200);

        $this->assertNotNull($response->getData()->accessToken);
        $this->assertDatabaseHas('personal_access_tokens', [
            'name' => 'New',
            'tokenable_id' => $this->user->id,
        ]);
    }

    #[Test]
    public function user_cannot_create_api_token_with_incorrect_password()
    {
        $response = $this->post('/settings/personal-access-tokens', [
            'name' => 'New',
            'password' => 'wrongpassword',
        ]);

        $response
            ->assertStatus(302)
            ->assertSessionHasErrors('password');

        $this->assertDatabaseMissing('personal_access_tokens', [
            'name' => 'New',
            'tokenable_id' => $this->user->id,
        ]);
    }

    #[Test]
    public function user_cannot_create_api_token_without_password_when_user_is_internal()
    {
        $response = $this->post('/settings/personal-access-tokens', [
            'name' => 'New',
        ]);

        $response
            ->assertStatus(302)
            ->assertSessionHasErrors('password');

        $this->assertDatabaseMissing('personal_access_tokens', [
            'name' => 'New',
            'tokenable_id' => $this->user->id,
        ]);
    }

    #[Test]
    public function user_can_create_api_token_without_password_when_user_is_extenal()
    {
        $this->user->defaultUsername->external_id = 'test';
        $this->user->defaultUsername->save();

        $response = $this->post('/settings/personal-access-tokens', [
            'name' => 'New',
        ]);

        $response->assertStatus(200);

        $this->assertNotNull($response->getData()->accessToken);
        $this->assertDatabaseHas('personal_access_tokens', [
            'name' => 'New',
            'tokenable_id' => $this->user->id,
        ]);
    }

    #[Test]
    public function user_can_revoke_api_token()
    {
        $token = $this->user->createToken('New');

        $response = $this->delete("/settings/personal-access-tokens/{$token->accessToken->id}");

        $response->assertStatus(204);

        $this->assertEmpty($this->user->tokens);
        $this->assertDatabaseMissing('personal_access_tokens', [
            'name' => 'New',
            'tokenable_id' => $this->user->id,
            'id' => $token->accessToken->id,
        ]);
    }
}
