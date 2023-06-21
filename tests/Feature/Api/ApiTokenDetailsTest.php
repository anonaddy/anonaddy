<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ApiTokenDetailsTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /** @test */
    public function user_can_get_account_details()
    {
        $user = User::factory()->create()->fresh();
        $token = $user->createToken('New');

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->json('GET', '/api/v1/api-token-details');

        $response->assertSuccessful();

        $this->assertEquals($token->accessToken->name, $response->json()['name']);
        $this->assertEquals($token->accessToken->created_at, $response->json()['created_at']);
        $this->assertEquals($token->accessToken->expires_at, $response->json()['expires_at']);
    }
}
