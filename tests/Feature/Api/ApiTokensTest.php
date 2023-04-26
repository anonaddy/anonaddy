<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiTokensTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create()->fresh();
        Sanctum::actingAs($this->user, [], 'web');
        $this->user->recipients()->save($this->user->defaultRecipient);
    }

    /** @test */
    public function user_can_create_api_token()
    {
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

    /** @test */
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
