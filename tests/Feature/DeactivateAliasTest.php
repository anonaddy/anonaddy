<?php

namespace Tests\Feature;

use App\Models\Alias;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequestsWithRedis;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DeactivateAliasTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ThrottleRequestsWithRedis::class);
    }

    #[Test]
    public function signed_get_deactivates_the_alias_and_redirects_to_the_edit_page(): void
    {
        $user = $this->createUser();
        $alias = Alias::factory()->create([
            'user_id' => $user->id,
            'active' => true,
        ]);

        $response = $this->actingAs($user)->get(URL::signedRoute('deactivate', ['alias' => $alias->id]));

        $response->assertRedirectToRoute('aliases.edit', ['id' => $alias->id]);
        $this->assertFalse($alias->fresh()->active);
        $this->assertNotSoftDeleted($alias);
    }

    #[Test]
    public function guest_is_redirected_to_login(): void
    {
        $user = $this->createUser();
        $alias = Alias::factory()->create([
            'user_id' => $user->id,
            'active' => true,
        ]);

        $response = $this->get(URL::signedRoute('deactivate', ['alias' => $alias->id]));

        $response->assertRedirectToRoute('login');
        $this->assertTrue($alias->fresh()->active);
    }

    #[Test]
    public function missing_signature_is_forbidden(): void
    {
        $user = $this->createUser();
        $alias = Alias::factory()->create([
            'user_id' => $user->id,
            'active' => true,
        ]);

        $response = $this->actingAs($user)->get(route('deactivate', ['alias' => $alias->id]));

        $response->assertForbidden();
        $this->assertTrue($alias->fresh()->active);
    }

    #[Test]
    public function other_user_receives_404(): void
    {
        $owner = $this->createUser('owner');
        $other = $this->createUser('other');
        $alias = Alias::factory()->create([
            'user_id' => $owner->id,
            'active' => true,
        ]);

        $response = $this->actingAs($other)->get(URL::signedRoute('deactivate', ['alias' => $alias->id]));

        $response->assertNotFound();
        $this->assertTrue($alias->fresh()->active);
    }
}
