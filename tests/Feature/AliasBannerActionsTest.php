<?php

namespace Tests\Feature;

use App\Models\Alias;
use App\Models\BlockedSender;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequestsWithRedis;
use Illuminate\Support\Facades\URL;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AliasBannerActionsTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ThrottleRequestsWithRedis::class);

        config(['anonaddy.all_domains' => ['anonaddy.local']]);
    }

    #[Test]
    public function guest_is_redirected_to_login(): void
    {
        $user = $this->createUser();
        $alias = Alias::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->get($this->signedActionsUrl($alias, 'sender@example.com'));

        $response->assertRedirect(route('login'));
        $this->assertNotSoftDeleted($alias);
    }

    #[Test]
    public function guest_cannot_deactivate_or_block_via_post(): void
    {
        $user = $this->createUser();
        $alias = Alias::factory()->create([
            'user_id' => $user->id,
        ]);

        $this->post(route('aliases.banner_actions.deactivate', $alias->id))
            ->assertRedirect(route('login'));
        $this->post(route('aliases.banner_actions.block_email', $alias->id), [
            'email' => 'sender@example.com',
        ])->assertRedirect(route('login'));

        $this->assertNotSoftDeleted($alias);
        $this->assertTrue($alias->fresh()->active);
        $this->assertDatabaseCount('blocked_senders', 0);
    }

    #[Test]
    public function missing_signature_is_forbidden(): void
    {
        $user = $this->createUser();
        $alias = Alias::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('aliases.banner_actions.show', [
            'alias' => $alias->id,
            'email' => 'sender@example.com',
        ]));

        $response->assertForbidden();
    }

    #[Test]
    public function other_user_receives_404(): void
    {
        $owner = $this->createUser('owner');
        $other = $this->createUser('other');
        $alias = Alias::factory()->create([
            'user_id' => $owner->id,
        ]);

        $response = $this->actingAs($other)->get($this->signedActionsUrl($alias, 'sender@example.com'));

        $response->assertNotFound();
    }

    #[Test]
    public function owner_sees_the_actions_page_and_get_does_not_change_data(): void
    {
        $user = $this->createUser();
        $alias = Alias::factory()->create([
            'user_id' => $user->id,
            'description' => 'Shop account',
        ]);

        $response = $this->actingAs($user)->get($this->signedActionsUrl($alias, 'News@Example.com'));

        $response->assertSuccessful();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Aliases/Actions')
            ->where('alias.id', $alias->id)
            ->where('alias.email', $alias->email)
            ->where('senderEmail', 'news@example.com')
            ->where('senderDomain', 'example.com')
            ->where('action', null)
            ->where('canBlockDomain', true)
        );
        $this->assertNotSoftDeleted($alias);
        $this->assertDatabaseCount('blocked_senders', 0);
    }

    #[Test]
    public function owner_sees_the_block_email_confirm_when_action_is_set(): void
    {
        $user = $this->createUser();
        $alias = Alias::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->get($this->signedActionsUrl($alias, 'sender@example.com', 'block_email'));

        $response->assertSuccessful();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Aliases/Actions')
            ->where('action', 'block_email')
            ->where('senderEmail', 'sender@example.com')
            ->where('canBlockDomain', true)
        );
        $this->assertDatabaseCount('blocked_senders', 0);
    }

    #[Test]
    public function owner_sees_the_block_domain_confirm_when_action_is_set(): void
    {
        $user = $this->createUser();
        $alias = Alias::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->get($this->signedActionsUrl($alias, 'sender@example.com', 'block_domain'));

        $response->assertSuccessful();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Aliases/Actions')
            ->where('action', 'block_domain')
            ->where('senderDomain', 'example.com')
            ->where('canBlockDomain', true)
        );
        $this->assertDatabaseCount('blocked_senders', 0);
    }

    #[Test]
    public function unknown_action_is_treated_as_the_hub(): void
    {
        $user = $this->createUser();
        $alias = Alias::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->get($this->signedActionsUrl($alias, 'sender@example.com', 'delete'));

        $response->assertSuccessful();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Aliases/Actions')
            ->where('action', null)
        );
    }

    #[Test]
    public function owner_can_deactivate_the_alias(): void
    {
        $user = $this->createUser();
        $alias = Alias::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->post(route('aliases.banner_actions.deactivate', $alias->id));

        $response->assertRedirectToRoute('aliases.edit', ['id' => $alias->id]);
        $this->assertFalse($alias->fresh()->active);
        $this->assertNotSoftDeleted($alias);
    }

    #[Test]
    public function other_user_cannot_deactivate_the_alias(): void
    {
        $owner = $this->createUser('owner');
        $other = $this->createUser('other');
        $alias = Alias::factory()->create([
            'user_id' => $owner->id,
        ]);

        $response = $this->actingAs($other)->post(route('aliases.banner_actions.deactivate', $alias->id));

        $response->assertNotFound();
        $this->assertTrue($alias->fresh()->active);
    }

    #[Test]
    public function owner_can_block_the_sender_email(): void
    {
        $user = $this->createUser();
        $alias = Alias::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->post(route('aliases.banner_actions.block_email', $alias->id), [
            'email' => 'Sender@Example.com',
        ]);

        $response->assertRedirect(route('blocklist.index'));
        $this->assertDatabaseHas('blocked_senders', [
            'user_id' => $user->id,
            'type' => 'email',
            'value' => 'sender@example.com',
        ]);
    }

    #[Test]
    public function owner_can_block_the_sender_domain(): void
    {
        $user = $this->createUser();
        $alias = Alias::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->post(route('aliases.banner_actions.block_domain', $alias->id), [
            'domain' => 'Example.com',
        ]);

        $response->assertRedirect(route('blocklist.index'));
        $this->assertDatabaseHas('blocked_senders', [
            'user_id' => $user->id,
            'type' => 'domain',
            'value' => 'example.com',
        ]);
    }

    #[Test]
    public function protected_alias_domains_are_not_blocked(): void
    {
        config(['anonaddy.all_domains' => ['addy.test']]);

        $user = $this->createUser();
        $alias = Alias::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->from('/aliases')->post(route('aliases.banner_actions.block_domain', $alias->id), [
            'domain' => 'addy.test',
        ]);

        $response->assertRedirect('/aliases');
        $response->assertSessionHasErrors('domain');
        $this->assertDatabaseCount('blocked_senders', 0);
    }

    #[Test]
    public function duplicate_blocklist_entry_returns_a_validation_error(): void
    {
        $user = $this->createUser();
        $alias = Alias::factory()->create([
            'user_id' => $user->id,
        ]);
        BlockedSender::factory()->create([
            'user_id' => $user->id,
            'type' => 'email',
            'value' => 'sender@example.com',
        ]);

        $response = $this->actingAs($user)->from('/aliases')->post(route('aliases.banner_actions.block_email', $alias->id), [
            'email' => 'sender@example.com',
        ]);

        $response->assertRedirect('/aliases');
        $response->assertSessionHasErrors('email');
        $this->assertDatabaseCount('blocked_senders', 1);
    }

    private function signedActionsUrl(Alias $alias, string $email, ?string $action = null): string
    {
        $parameters = [
            'alias' => $alias->id,
            'email' => $email,
        ];

        if ($action !== null) {
            $parameters['action'] = $action;
        }

        return URL::signedRoute('aliases.banner_actions.show', $parameters);
    }
}
