<?php

namespace Tests\Feature;

use App\Models\BlockedSender;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BlocklistPageTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->createUser();
        $this->actingAs($this->user);
    }

    #[Test]
    public function authenticated_user_can_access_blocklist_index_and_sees_their_entries(): void
    {
        BlockedSender::factory()->count(2)->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->get(route('blocklist.index'));

        $response->assertSuccessful();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Blocklist/Index')
            ->has('initialRows.data', 2, fn (Assert $page) => $page
                ->where('user_id', $this->user->id)
                ->etc()
            )
            ->has('search')
            ->where('initialPageSize', 50)
        );
    }

    #[Test]
    public function api_index_returns_all_expected_fields(): void
    {
        BlockedSender::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'email',
            'value' => 'spam@example.com',
        ]);

        $response = $this->getJson('/api/v1/blocklist');

        $response->assertSuccessful();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonStructure([
            'data' => [
                [
                    'id',
                    'user_id',
                    'type',
                    'value',
                    'blocked',
                    'last_blocked',
                    'created_at',
                    'updated_at',
                ],
            ],
        ]);
    }

    #[Test]
    public function api_index_is_paginated_with_default_page_size_of_one_hundred(): void
    {
        BlockedSender::factory()->count(105)->create([
            'user_id' => $this->user->id,
            'type' => 'email',
        ]);

        $response = $this->getJson('/api/v1/blocklist');

        $response->assertSuccessful();
        $response->assertJsonCount(100, 'data');
        $response->assertJsonPath('meta.current_page', 1);
        $response->assertJsonPath('meta.per_page', 100);
        $response->assertJsonPath('meta.total', 105);
    }

    #[Test]
    public function api_index_can_filter_using_filter_search_query_param(): void
    {
        BlockedSender::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'domain',
            'value' => 'spamdomain.test',
        ]);

        BlockedSender::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'domain',
            'value' => 'hamdomain.test',
        ]);

        $response = $this->getJson('/api/v1/blocklist?filter[search]=spamdomain');

        $response->assertSuccessful();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.value', 'spamdomain.test');
    }

    #[Test]
    public function api_index_can_filter_by_type(): void
    {
        BlockedSender::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'email',
            'value' => 'one@example.com',
        ]);

        BlockedSender::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'domain',
            'value' => 'domain.test',
        ]);

        $response = $this->getJson('/api/v1/blocklist?filter[type]=domain');

        $response->assertSuccessful();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.type', 'domain');
        $response->assertJsonPath('data.0.value', 'domain.test');
    }

    #[Test]
    public function api_index_can_sort_by_blocked_ascending(): void
    {
        BlockedSender::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'email',
            'value' => 'high@example.com',
            'blocked' => 50,
        ]);

        BlockedSender::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'email',
            'value' => 'low@example.com',
            'blocked' => 5,
        ]);

        $response = $this->getJson('/api/v1/blocklist?sort=blocked');

        $response->assertSuccessful();
        $response->assertJsonPath('data.0.value', 'low@example.com');
        $response->assertJsonPath('data.1.value', 'high@example.com');
    }

    #[Test]
    public function store_response_includes_blocked_fields(): void
    {
        $response = $this->postJson('/api/v1/blocklist', [
            'type' => 'email',
            'value' => 'new@example.com',
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'data' => [
                'id',
                'user_id',
                'type',
                'value',
                'blocked',
                'last_blocked',
                'created_at',
                'updated_at',
            ],
        ]);
        $response->assertJsonPath('data.blocked', 0);
        $response->assertJsonPath('data.last_blocked', null);
    }

    #[Test]
    public function user_can_add_email_entry(): void
    {
        $response = $this->postJson('/api/v1/blocklist', [
            'type' => 'email',
            'value' => 'spam@example.com',
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.value', 'spam@example.com');
        $response->assertJsonPath('data.type', 'email');

        $this->assertDatabaseHas('blocked_senders', [
            'user_id' => $this->user->id,
            'type' => 'email',
            'value' => 'spam@example.com',
        ]);
    }

    #[Test]
    public function user_can_add_domain_entry(): void
    {
        $response = $this->postJson('/api/v1/blocklist', [
            'type' => 'domain',
            'value' => 'spammer.com',
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.value', 'spammer.com');
        $response->assertJsonPath('data.type', 'domain');

        $this->assertDatabaseHas('blocked_senders', [
            'user_id' => $this->user->id,
            'type' => 'domain',
            'value' => 'spammer.com',
        ]);
    }

    #[Test]
    public function user_can_delete_own_entry(): void
    {
        $entry = BlockedSender::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'email',
            'value' => 'remove@example.com',
        ]);

        $response = $this->deleteJson('/api/v1/blocklist/'.$entry->id);

        $response->assertStatus(204);
        $this->assertDatabaseMissing('blocked_senders', ['id' => $entry->id]);
    }

    #[Test]
    public function user_cannot_delete_another_users_entry(): void
    {
        $otherUser = $this->createUser('other');
        $entry = BlockedSender::factory()->create([
            'user_id' => $otherUser->id,
            'type' => 'email',
            'value' => 'other@example.com',
        ]);

        $response = $this->deleteJson('/api/v1/blocklist/'.$entry->id);

        $response->assertStatus(404);
        $this->assertDatabaseHas('blocked_senders', ['id' => $entry->id]);
    }

    #[Test]
    public function store_validates_invalid_type(): void
    {
        $response = $this->postJson('/api/v1/blocklist', [
            'type' => 'invalid',
            'value' => 'spam@example.com',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('type');
        $this->assertDatabaseMissing('blocked_senders', [
            'user_id' => $this->user->id,
            'value' => 'spam@example.com',
        ]);
    }

    #[Test]
    public function store_validates_invalid_email_value(): void
    {
        $response = $this->postJson('/api/v1/blocklist', [
            'type' => 'email',
            'value' => 'not-an-email',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('value');
    }

    #[Test]
    public function store_validates_duplicate_entry(): void
    {
        BlockedSender::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'email',
            'value' => 'duplicate@example.com',
        ]);

        $response = $this->postJson('/api/v1/blocklist', [
            'type' => 'email',
            'value' => 'duplicate@example.com',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('value');
        $response->assertJsonPath('errors.value.0', BlockedSender::ALREADY_ON_BLOCKLIST_MESSAGE);
        $this->assertSame(1, BlockedSender::where('user_id', $this->user->id)->where('value', 'duplicate@example.com')->count());
    }

    #[Test]
    public function store_returns_422_when_unique_constraint_is_violated(): void
    {
        BlockedSender::creating(function (BlockedSender $sender): void {
            if ($sender->type !== 'domain' || $sender->value !== 'example.com') {
                return;
            }

            if (DB::table('blocked_senders')->where('user_id', $sender->user_id)->where('type', 'domain')->where('value', 'example.com')->exists()) {
                return;
            }

            DB::table('blocked_senders')->insert([
                'id' => (string) Str::uuid(),
                'user_id' => $sender->user_id,
                'type' => 'domain',
                'value' => 'example.com',
                'blocked' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        $response = $this->postJson('/api/v1/blocklist', [
            'type' => 'domain',
            'value' => 'example.com',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('value');
        $response->assertJsonPath('errors.value.0', BlockedSender::ALREADY_ON_BLOCKLIST_MESSAGE);
        $this->assertSame(1, BlockedSender::where('user_id', $this->user->id)->where('value', 'example.com')->count());
    }

    #[Test]
    public function search_filters_list_by_value_and_type(): void
    {
        BlockedSender::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'email',
            'value' => 'spam@example.com',
        ]);
        BlockedSender::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'domain',
            'value' => 'spammer.com',
        ]);
        BlockedSender::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'email',
            'value' => 'news@other.org',
        ]);

        $response = $this->get(route('blocklist.index', ['search' => 'spam']));

        $response->assertSuccessful();
        $response->assertInertia(fn (Assert $page) => $page
            ->has('initialRows.data', 2)
            ->where('search', 'spam')
        );
        $rows = $response->data('page')['props']['initialRows']['data'];
        $values = array_column($rows, 'value');
        $this->assertContains('spam@example.com', $values);
        $this->assertContains('spammer.com', $values);
        $this->assertNotContains('news@other.org', $values);
    }

    #[Test]
    public function user_can_bulk_delete_own_entries(): void
    {
        $entries = BlockedSender::factory()->count(3)->create(['user_id' => $this->user->id]);
        $ids = $entries->pluck('id')->all();

        $response = $this->postJson('/api/v1/blocklist/delete/bulk', ['ids' => $ids]);

        $response->assertStatus(200);
        $response->assertJsonPath('message', '3 entries removed from blocklist');
        $this->assertEqualsCanonicalizing($ids, $response->json('ids'));
        foreach ($ids as $id) {
            $this->assertDatabaseMissing('blocked_senders', ['id' => $id]);
        }
    }

    #[Test]
    public function bulk_delete_validates_ids_required(): void
    {
        $response = $this->postJson('/api/v1/blocklist/delete/bulk', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('ids');
    }

    #[Test]
    public function user_can_bulk_add_entries(): void
    {
        $response = $this->postJson('/api/v1/blocklist/store/bulk', [
            'type' => 'email',
            'values' => ['bulk1@example.com', 'bulk2@example.com', 'bulk3@example.com'],
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('message', '3 entries added to blocklist.');
        $this->assertCount(3, $response->json('data'));
        $this->assertDatabaseHas('blocked_senders', [
            'user_id' => $this->user->id,
            'type' => 'email',
            'value' => 'bulk1@example.com',
        ]);
        $this->assertDatabaseHas('blocked_senders', [
            'user_id' => $this->user->id,
            'type' => 'email',
            'value' => 'bulk2@example.com',
        ]);
        $this->assertDatabaseHas('blocked_senders', [
            'user_id' => $this->user->id,
            'type' => 'email',
            'value' => 'bulk3@example.com',
        ]);
    }
}
