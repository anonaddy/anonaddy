<?php

namespace Tests\Feature\Api;

use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ApiTokensTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = factory(User::class)->create();
        $this->actingAs($this->user);
        $this->user->recipients()->save($this->user->defaultRecipient);
    }

    /** @test */
    public function user_can_rotate_api_token()
    {
        $this->assertNull($this->user->api_token);

        $response = $this->json('POST', '/settings/api-token', []);

        $response->assertStatus(200);

        $this->assertNotNull($response->getData()->token);
        $this->assertNotNull($this->user->refresh()->api_token);
    }

    /** @test */
    public function user_can_revoke_api_token()
    {
        $token = Str::random(60);

        $this->user->forceFill([
            'api_token' => hash('sha256', $token),
        ])->save();

        $this->assertNotNull($this->user->refresh()->api_token);

        $response = $this->json('DELETE', '/settings/api-token');

        $response->assertStatus(204);

        $this->assertNull($this->user->refresh()->api_token);
    }
}
