<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;
use Tests\TestCase;

class ApiTokensTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        Passport::actingAs($this->user, [], 'web');
        $this->user->recipients()->save($this->user->defaultRecipient);

        $this->user->usernames()->save($this->user->defaultUsername);
        $this->user->defaultUsername->username = 'johndoe';
        $this->user->defaultUsername->save();

        $clientRepository = new ClientRepository();
        $client = $clientRepository->createPersonalAccessClient(
            null,
            'Test Personal Access Client',
            config('app.url')
        );
        DB::table('oauth_personal_access_clients')->insert([
            'client_id' => $client->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /** @test */
    public function user_can_generate_api_token()
    {
        $response = $this->post('/oauth/personal-access-tokens', [
            'name' => 'New'
        ]);

        $response->assertStatus(200);

        $this->assertNotNull($response->getData()->accessToken);
        $this->assertDatabaseHas('oauth_access_tokens', [
            'name' => 'New',
            'user_id' => $this->user->id
        ]);
    }

    /** @test */
    public function user_can_revoke_api_token()
    {
        DB::table('oauth_access_tokens')->insert([
            'id' => '1830c31e8e17dc4e871aa21ebe82e6cbfdd0d5781bec42631dd381119f355a911075f7e1a3dc2240',
            'name' => 'New',
            'user_id' => $this->user->id,
            'revoked' => false,
            'client_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->delete('/oauth/personal-access-tokens/1830c31e8e17dc4e871aa21ebe82e6cbfdd0d5781bec42631dd381119f355a911075f7e1a3dc2240');

        $response->assertStatus(204);

        $this->assertDatabaseMissing('oauth_access_tokens', [
            'name' => 'New',
            'user_id' => $this->user->id,
            'id' => '1830c31e8e17dc4e871aa21ebe82e6cbfdd0d5781bec42631dd381119f355a911075f7e1a3dc2240',
            'revoke' => true
        ]);
    }
}
