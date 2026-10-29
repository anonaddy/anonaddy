<?php

namespace Tests\Feature\Api;

use App\Models\Alias;
use App\Models\Domain;
use App\Models\Label;
use App\Models\Recipient;
use App\Models\Username;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AliasesTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        parent::setUpSanctum();

        $this->user->defaultUsername->username = 'johndoe';
        $this->user->defaultUsername->save();
    }

    #[Test]
    public function user_can_get_all_aliases()
    {
        // Arrange
        Alias::factory()->count(3)->create([
            'user_id' => $this->user->id,
        ]);

        // Act
        $response = $this->json('GET', '/api/v1/aliases');

        // Assert
        $response->assertSuccessful();
        $this->assertCount(3, $response->json()['data']);
    }

    #[Test]
    public function user_can_get_all_aliases_including_deleted()
    {
        // Arrange
        Alias::factory()->count(2)->create([
            'user_id' => $this->user->id,
        ]);

        Alias::factory()->create([
            'user_id' => $this->user->id,
            'deleted_at' => now(),
        ]);

        // Act
        $response = $this->json('GET', '/api/v1/aliases?deleted=with');

        // Assert
        $response->assertSuccessful();
        $this->assertCount(3, $response->json()['data']);
    }

    #[Test]
    public function user_can_get_only_deleted_aliases()
    {
        // Arrange
        Alias::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'deleted_at' => now(),
        ]);

        Alias::factory()->create([
            'user_id' => $this->user->id,
        ]);

        // Act
        $response = $this->json('GET', '/api/v1/aliases?filter[deleted]=only');

        // Assert
        $response->assertSuccessful();
        $this->assertCount(2, $response->json()['data']);
    }

    #[Test]
    public function user_can_get_only_active_aliases()
    {
        // Arrange
        Alias::factory()->create([
            'user_id' => $this->user->id,
            'active' => true,
        ]);

        Alias::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'active' => false,
        ]);

        // Act
        $response = $this->json('GET', '/api/v1/aliases?filter[active]=true');

        // Assert
        $response->assertSuccessful();
        $this->assertCount(1, $response->json()['data']);
    }

    #[Test]
    public function pinned_aliases_appear_first_when_listing()
    {
        $unpinnedFirst = Alias::factory()->create([
            'user_id' => $this->user->id,
            'email' => 'aaa@example.com',
            'local_part' => 'aaa',
            'domain' => 'example.com',
            'pinned' => false,
        ]);
        $pinned = Alias::factory()->create([
            'user_id' => $this->user->id,
            'email' => 'zzz@example.com',
            'local_part' => 'zzz',
            'domain' => 'example.com',
            'pinned' => true,
        ]);
        $unpinnedSecond = Alias::factory()->create([
            'user_id' => $this->user->id,
            'email' => 'mmm@example.com',
            'local_part' => 'mmm',
            'domain' => 'example.com',
            'pinned' => false,
        ]);

        $response = $this->json('GET', '/api/v1/aliases');

        $response->assertSuccessful();
        $data = $response->json()['data'];
        $this->assertCount(3, $data);
        $this->assertEquals($pinned->id, $data[0]['id']);
        $this->assertTrue($data[0]['pinned']);
    }

    #[Test]
    public function user_can_get_only_pinned_aliases()
    {
        Alias::factory()->create([
            'user_id' => $this->user->id,
            'pinned' => true,
        ]);
        Alias::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'pinned' => false,
        ]);

        $response = $this->json('GET', '/api/v1/aliases?filter[pinned]=true');

        $response->assertSuccessful();
        $this->assertCount(1, $response->json()['data']);
        $this->assertTrue($response->json()['data'][0]['pinned']);
    }

    #[Test]
    public function user_can_get_only_unpinned_aliases()
    {
        Alias::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'pinned' => true,
        ]);
        Alias::factory()->create([
            'user_id' => $this->user->id,
            'pinned' => false,
        ]);

        $response = $this->json('GET', '/api/v1/aliases?filter[pinned]=false');

        $response->assertSuccessful();
        $this->assertCount(1, $response->json()['data']);
        $this->assertFalse($response->json()['data'][0]['pinned']);
    }

    #[Test]
    public function user_can_get_individual_alias()
    {
        // Arrange
        $alias = Alias::factory()->create([
            'user_id' => $this->user->id,
        ]);

        // Act
        $response = $this->json('GET', '/api/v1/aliases/'.$alias->id);

        // Assert
        $response->assertSuccessful();
        $this->assertCount(1, $response->json());
        $this->assertEquals($alias->email, $response->json()['data']['email']);
    }

    #[Test]
    public function user_can_get_individual_alias_with_labels(): void
    {
        $label = Label::factory()->create(['user_id' => $this->user->id]);
        $alias = Alias::factory()->create(['user_id' => $this->user->id]);
        $alias->labels()->attach($label->id);

        $response = $this->json('GET', '/api/v1/aliases/'.$alias->id);

        $response->assertSuccessful();
        $this->assertEquals($label->id, $response->json()['data']['labels'][0]['id']);
    }

    #[Test]
    public function user_can_generate_new_alias()
    {
        $response = $this->json('POST', '/api/v1/aliases', [
            'domain' => 'Anonaddy.me',
            'description' => 'the description',
            'local_part' => 'not-required-for-shared-alias',
        ]);

        $response->assertStatus(201);
        $this->assertCount(1, $this->user->aliases);
        $this->assertEquals($this->user->aliases[0]->local_part, $response->getData()->data->local_part);
    }

    #[Test]
    public function user_can_generate_alias_with_recipients()
    {
        $recipient = Recipient::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $recipient2 = Recipient::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->json('POST', '/api/v1/aliases', [
            'domain' => 'anonaddy.me',
            'description' => 'the description',
            'recipient_ids' => [
                $recipient->id,
                $recipient2->id,
            ],
        ]);

        $response->assertStatus(201);
        $this->assertCount(2, $this->user->aliases[0]->recipients);
        $this->assertContains($recipient->email, $this->user->aliases[0]->recipients->pluck('email'));
    }

    #[Test]
    public function user_can_generate_new_uuid_alias()
    {
        $response = $this->json('POST', '/api/v1/aliases', [
            'domain' => 'anonaddy.me',
            'format' => 'uuid',
            'description' => 'the description',
            'local_part' => 'not-required-for-shared-alias',
        ]);

        $response->assertStatus(201);
        $this->assertCount(1, $this->user->aliases);
        $this->assertEquals($this->user->aliases[0]->id, $response->getData()->data->local_part);
        $this->assertEquals($this->user->aliases[0]->id, $this->user->aliases[0]->local_part);
    }

    #[Test]
    public function user_can_generate_new_alias_with_local_part()
    {
        $response = $this->json('POST', '/api/v1/aliases', [
            'domain' => $this->user->username.'.anonaddy.com',
            'format' => 'custom',
            'description' => 'the description',
            'local_part' => 'valid-local-part',
        ]);

        $response->assertStatus(201);
        $this->assertCount(1, $this->user->aliases);
        $this->assertEquals('valid-local-part', $response->getData()->data->local_part);
        $this->assertEquals('valid-local-part@'.$this->user->username.'.anonaddy.com', $this->user->aliases[0]->email);
    }

    #[Test]
    public function user_can_generate_new_alias_with_local_part_and_extension()
    {
        $response = $this->json('POST', '/api/v1/aliases', [
            'domain' => $this->user->username.'.anonaddy.com',
            'format' => 'custom',
            'description' => 'the description',
            'local_part' => 'valid-local-part+extension',
        ]);

        $response->assertStatus(201);
        $this->assertCount(1, $this->user->aliases);
        $this->assertEquals('valid-local-part', $response->getData()->data->local_part);
        $this->assertEquals('extension', $response->getData()->data->extension);
        $this->assertEquals('valid-local-part@'.$this->user->username.'.anonaddy.com', $this->user->aliases[0]->email);
    }

    #[Test]
    public function user_cannot_generate_new_alias_with_invalid_local_part()
    {
        $response = $this->json('POST', '/api/v1/aliases', [
            'domain' => $this->user->username.'.anonaddy.com',
            'format' => 'custom',
            'description' => 'the description',
            'local_part' => 'invalid-local-part.',
        ]);

        $response->assertStatus(422);
        $this->assertCount(0, $this->user->aliases);
        $response->assertJsonValidationErrors('local_part_without_extension');
    }

    #[Test]
    public function user_cannot_generate_custom_alias_that_already_exists()
    {
        Alias::factory()->create([
            'user_id' => $this->user->id,
            'local_part' => 'exists',
            'extension' => '1',
            'domain' => $this->user->username.'.anonaddy.com',
            'email' => 'exists@'.$this->user->username.'.anonaddy.com',
        ]);

        $response = $this->json('POST', '/api/v1/aliases', [
            'domain' => $this->user->username.'.anonaddy.com',
            'format' => 'custom',
            'description' => 'the description',
            'local_part' => 'exists+2',
        ]);

        $response->assertStatus(422);
        $this->assertCount(1, $this->user->aliases);
        $response->assertJsonValidationErrors('local_part_without_extension');
    }

    #[Test]
    public function user_can_generate_new_random_word_alias()
    {
        $response = $this->json('POST', '/api/v1/aliases', [
            'domain' => 'anonaddy.me',
            'description' => 'the description',
            'format' => 'random_words',
        ]);

        $response->assertStatus(201);
        $this->assertCount(1, $this->user->aliases);
        $localPart = $response->getData()->data->local_part;
        $this->assertNotEquals($this->user->aliases[0]->id, $localPart);
        $this->assertNotEquals($this->user->aliases[0]->id, $this->user->aliases[0]->local_part);
        $this->assertMatchesRegularExpression('/^[a-z]+[._-][a-z]+\d{1,3}$/', $localPart);
    }

    #[Test]
    public function user_can_generate_new_random_male_name_alias()
    {
        $response = $this->json('POST', '/api/v1/aliases', [
            'domain' => 'anonaddy.me',
            'description' => 'the description',
            'format' => 'random_male_name',
        ]);

        $response->assertStatus(201);
        $this->assertCount(1, $this->user->aliases);
        $localPart = $response->getData()->data->local_part;
        $this->assertNotEquals($this->user->aliases[0]->id, $localPart);
        $this->assertNotEquals($this->user->aliases[0]->id, $this->user->aliases[0]->local_part);
        $this->assertMatchesRegularExpression('/^[a-z]+[._-][a-z]+\d{1,3}$/', $localPart);
    }

    #[Test]
    public function user_can_generate_new_random_female_name_alias()
    {
        $response = $this->json('POST', '/api/v1/aliases', [
            'domain' => 'anonaddy.me',
            'description' => 'the description',
            'format' => 'random_female_name',
        ]);

        $response->assertStatus(201);
        $this->assertCount(1, $this->user->aliases);
        $localPart = $response->getData()->data->local_part;
        $this->assertNotEquals($this->user->aliases[0]->id, $localPart);
        $this->assertNotEquals($this->user->aliases[0]->id, $this->user->aliases[0]->local_part);
        $this->assertMatchesRegularExpression('/^[a-z]+[._-][a-z]+\d{1,3}$/', $localPart);
    }

    #[Test]
    public function user_can_generate_new_random_noun_alias()
    {
        $response = $this->json('POST', '/api/v1/aliases', [
            'domain' => 'anonaddy.me',
            'description' => 'the description',
            'format' => 'random_noun',
        ]);

        $response->assertStatus(201);
        $this->assertCount(1, $this->user->aliases);
        $localPart = $response->getData()->data->local_part;
        $this->assertNotEquals($this->user->aliases[0]->id, $localPart);
        $this->assertNotEquals($this->user->aliases[0]->id, $this->user->aliases[0]->local_part);
        $this->assertMatchesRegularExpression('/^[a-z]+[._-][a-z]+\d{1,3}$/', $localPart);
    }

    #[Test]
    public function user_can_generate_new_alias_with_correct_aliasable_type()
    {
        Username::factory()->create([
            'user_id' => $this->user->id,
            'username' => 'john',
        ]);

        $domain = Domain::factory()->create([
            'user_id' => $this->user->id,
            'domain' => 'john.xyz',
            'domain_verified_at' => now(),
        ]);

        $response = $this->json('POST', '/api/v1/aliases', [
            'domain' => 'john.xyz',
            'description' => 'the description',
        ]);

        $response->assertStatus(201);
        $this->assertCount(1, $this->user->aliases);
        $this->assertEquals('App\Models\Domain', $response->getData()->data->aliasable_type);
        $this->assertEquals($domain->id, $this->user->aliases[0]->aliasable_id);
    }

    #[Test]
    public function user_can_update_alias_description()
    {
        $alias = Alias::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->json('PATCH', '/api/v1/aliases/'.$alias->id, [
            'description' => 'The new description',
        ]);

        $response->assertStatus(200);
        $this->assertEquals('The new description', $response->getData()->data->description);
    }

    #[Test]
    public function user_can_update_alias_from_name()
    {
        $alias = Alias::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->json('PATCH', '/api/v1/aliases/'.$alias->id, [
            'from_name' => 'John Doe',
        ]);

        $response->assertStatus(200);
        $this->assertEquals('John Doe', $response->getData()->data->from_name);
    }

    #[Test]
    public function user_can_delete_alias()
    {
        $alias = Alias::factory()->create([
            'user_id' => $this->user->id,
            'active' => true,
        ]);

        $response = $this->json('DELETE', '/api/v1/aliases/'.$alias->id);

        $response->assertStatus(204);
        $this->assertEmpty($this->user->aliases);
        $this->assertFalse($alias->refresh()->active);
    }

    #[Test]
    public function user_can_forget_alias()
    {
        $alias = Alias::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->json('DELETE', '/api/v1/aliases/'.$alias->id.'/forget');

        $response->assertStatus(204);
        $this->assertEmpty($this->user->aliases()->withTrashed()->get());

        $this->assertDatabaseMissing('aliases', [
            'id' => $alias->id,
        ]);
    }

    #[Test]
    public function user_can_forget_shared_domain_alias()
    {
        $sharedDomainAlias = Alias::factory()->create([
            'user_id' => $this->user->id,
            'domain' => 'anonaddy.me',
            'local_part' => '9nmrhanm',
            'email' => '9nmrhanm@anonaddy.me',
            'extension' => 'ext',
            'description' => 'Alias',
            'emails_forwarded' => 10,
            'emails_blocked' => 1,
            'emails_replied' => 2,
            'emails_sent' => 3,
        ]);

        $response = $this->json('DELETE', '/api/v1/aliases/'.$sharedDomainAlias->id.'/forget');

        $response->assertStatus(204);
        $this->assertEmpty($this->user->aliases()->withTrashed()->get());

        $this->assertDatabaseHas('aliases', [
            'id' => $sharedDomainAlias->id,
            'user_id' => '00000000-0000-0000-0000-000000000000',
            'extension' => null,
            'description' => null,
            'emails_forwarded' => 0,
            'emails_blocked' => 0,
            'emails_replied' => 0,
            'emails_sent' => 0,
            'deleted_at' => now(),
        ]);
    }

    #[Test]
    public function user_can_restore_deleted_alias()
    {
        $alias = Alias::factory()->create([
            'user_id' => $this->user->id,
            'deleted_at' => now(),
        ]);

        $response = $this->json('PATCH', '/api/v1/aliases/'.$alias->id.'/restore');

        $response->assertStatus(200);
        $this->assertFalse($this->user->aliases[0]->trashed());
    }

    #[Test]
    public function user_can_activate_alias()
    {
        $alias = Alias::factory()->create([
            'user_id' => $this->user->id,
            'active' => false,
        ]);

        $response = $this->json('POST', '/api/v1/active-aliases/', [
            'id' => $alias->id,
        ]);

        $response->assertStatus(200);
        $this->assertEquals(true, $response->getData()->data->active);
    }

    #[Test]
    public function user_can_deactivate_alias()
    {
        $alias = Alias::factory()->create([
            'user_id' => $this->user->id,
            'active' => true,
        ]);

        $response = $this->json('DELETE', '/api/v1/active-aliases/'.$alias->id);

        $response->assertStatus(204);
        $this->assertFalse($this->user->aliases[0]->active);
    }

    #[Test]
    public function user_can_pin_alias()
    {
        $alias = Alias::factory()->create([
            'user_id' => $this->user->id,
            'pinned' => false,
        ]);

        $response = $this->json('POST', '/api/v1/pinned-aliases/', [
            'id' => $alias->id,
        ]);

        $response->assertStatus(200);
        $this->assertEquals(true, $response->getData()->data->pinned);
    }

    #[Test]
    public function user_can_unpin_alias()
    {
        $alias = Alias::factory()->create([
            'user_id' => $this->user->id,
            'pinned' => true,
        ]);

        $response = $this->json('DELETE', '/api/v1/pinned-aliases/'.$alias->id);

        $response->assertStatus(204);
        $this->assertFalse($this->user->aliases[0]->pinned);
    }

    #[Test]
    public function user_can_bulk_get_aliases()
    {
        $alias = Alias::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $alias2 = Alias::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->json('POST', '/api/v1/aliases/get/bulk', [
            'ids' => [
                $alias->id,
                $alias2->id,
                null,
            ],
        ]);

        $response->assertStatus(200);
        $this->assertCount(2, $response->getData()->data);
    }

    #[Test]
    public function user_cannot_bulk_get_invalid_aliases()
    {
        $alias = Alias::factory()->create([
            'user_id' => '00000000-0000-0000-0000-000000000000',
        ]);

        $response = $this->json('POST', '/api/v1/aliases/get/bulk', [
            'ids' => [
                $alias->id,
            ],
        ]);

        $response->assertStatus(404);
        $this->assertEquals('No aliases found', $response->getData()->message);
    }

    #[Test]
    public function user_can_bulk_activate_aliases()
    {
        $alias = Alias::factory()->create([
            'user_id' => $this->user->id,
            'active' => false,
        ]);

        $alias2 = Alias::factory()->create([
            'user_id' => $this->user->id,
            'active' => false,
        ]);

        $response = $this->json('POST', '/api/v1/aliases/activate/bulk', [
            'ids' => [
                $alias->id,
                $alias2->id,
                null,
            ],
        ]);

        $response->assertStatus(200);
        $this->assertCount(2, $response->getData()->ids);
        $this->assertEquals('2 aliases activated successfully', $response->getData()->message);
        $this->assertDatabaseHas('aliases', [
            'id' => $alias->id,
            'active' => true,
        ]);
    }

    #[Test]
    public function user_cannot_bulk_activate_invalid_aliases()
    {
        $alias = Alias::factory()->create([
            'user_id' => $this->user->id,
            'active' => false,
        ]);

        $alias2 = Alias::factory()->create([
            'user_id' => '00000000-0000-0000-0000-000000000000',
            'active' => false,
        ]);

        $response = $this->json('POST', '/api/v1/aliases/activate/bulk', [
            'ids' => [
                $alias->id,
                $alias2->id,
            ],
        ]);

        $response->assertStatus(200);
        $this->assertCount(1, $response->getData()->ids);
        $this->assertEquals('1 alias activated successfully', $response->getData()->message);
        $this->assertDatabaseHas('aliases', [
            'id' => $alias->id,
            'active' => true,
        ]);
        $this->assertDatabaseHas('aliases', [
            'id' => $alias2->id,
            'active' => false,
        ]);
    }

    #[Test]
    public function user_can_bulk_deactivate_aliases()
    {
        $alias = Alias::factory()->create([
            'user_id' => $this->user->id,
            'active' => true,
        ]);

        $alias2 = Alias::factory()->create([
            'user_id' => $this->user->id,
            'active' => true,
        ]);

        $response = $this->json('POST', '/api/v1/aliases/deactivate/bulk', [
            'ids' => [
                $alias->id,
                $alias2->id,
                null,
            ],
        ]);

        $response->assertStatus(200);
        $this->assertCount(2, $response->getData()->ids);
        $this->assertEquals('2 aliases deactivated successfully', $response->getData()->message);
        $this->assertDatabaseHas('aliases', [
            'id' => $alias->id,
            'active' => false,
        ]);
    }

    #[Test]
    public function user_can_bulk_delete_aliases()
    {
        $alias = Alias::factory()->create([
            'user_id' => $this->user->id,
            'active' => true,
            'deleted_at' => null,
        ]);

        $alias2 = Alias::factory()->create([
            'user_id' => $this->user->id,
            'active' => true,
            'deleted_at' => null,
        ]);

        $response = $this->json('POST', '/api/v1/aliases/delete/bulk', [
            'ids' => [
                $alias->id,
                $alias2->id,
                null,
            ],
        ]);

        $response->assertStatus(200);
        $this->assertCount(2, $response->getData()->ids);
        $this->assertEquals('2 aliases deleted successfully', $response->getData()->message);
        $this->assertDatabaseHas('aliases', [
            'id' => $alias->id,
            'active' => false,
            'deleted_at' => now(),
        ]);
    }

    #[Test]
    public function user_can_bulk_restore_aliases()
    {
        $alias = Alias::factory()->create([
            'user_id' => $this->user->id,
            'active' => false,
            'deleted_at' => now(),
        ]);

        $alias2 = Alias::factory()->create([
            'user_id' => $this->user->id,
            'active' => false,
            'deleted_at' => now(),
        ]);

        $response = $this->json('POST', '/api/v1/aliases/restore/bulk', [
            'ids' => [
                $alias->id,
                $alias2->id,
                null,
            ],
        ]);

        $response->assertStatus(200);
        $this->assertCount(2, $response->getData()->ids);
        $this->assertEquals('2 aliases restored successfully', $response->getData()->message);
        $this->assertDatabaseHas('aliases', [
            'id' => $alias->id,
            'active' => true,
            'deleted_at' => null,
        ]);
    }

    #[Test]
    public function user_can_bulk_forget_aliases()
    {
        $alias = Alias::factory()->create([
            'user_id' => $this->user->id,
            'active' => true,
        ]);

        $alias2 = Alias::factory()->create([
            'user_id' => $this->user->id,
            'active' => true,
        ]);

        $response = $this->json('POST', '/api/v1/aliases/forget/bulk', [
            'ids' => [
                $alias->id,
                $alias2->id,
                null,
            ],
        ]);

        $response->assertStatus(200);
        $this->assertCount(2, $response->getData()->ids);
        $this->assertEquals('2 aliases forgotten successfully', $response->getData()->message);
        $this->assertDatabaseMissing('aliases', [
            'id' => $alias->id,
        ]);
    }

    #[Test]
    public function user_can_bulk_update_recipients_for_aliases()
    {
        $alias = Alias::factory()->create([
            'user_id' => $this->user->id,
            'active' => true,
        ]);

        $alias2 = Alias::factory()->create([
            'user_id' => $this->user->id,
            'active' => true,
        ]);

        $recipient = Recipient::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->json('POST', '/api/v1/aliases/recipients/bulk', [
            'ids' => [
                $alias->id,
                $alias2->id,
                null,
            ],
            'recipient_ids' => [
                $recipient->id,
            ],
        ]);

        $response->assertStatus(200);
        $this->assertCount(2, $response->getData()->ids);
        $this->assertEquals('recipients updated for 2 aliases successfully', $response->getData()->message);
        $this->assertDatabaseHas('alias_recipients', [
            'alias_id' => $alias->id,
            'recipient_id' => $recipient->id,
        ]);
    }

    #[Test]
    public function user_cannot_bulk_update_recipients_for_invalid_aliases()
    {
        $alias = Alias::factory()->create([
            'user_id' => $this->user->id,
            'active' => true,
        ]);

        $alias2 = Alias::factory()->create([
            'user_id' => '00000000-0000-0000-0000-000000000000',
            'active' => true,
        ]);

        $recipient = Recipient::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->json('POST', '/api/v1/aliases/recipients/bulk', [
            'ids' => [
                $alias->id,
                $alias2->id,
                null,
            ],
            'recipient_ids' => [
                $recipient->id,
            ],
        ]);

        $response->assertStatus(200);
        $this->assertCount(1, $response->getData()->ids);
        $this->assertEquals('recipients updated for 1 alias successfully', $response->getData()->message);
        $this->assertDatabaseHas('alias_recipients', [
            'alias_id' => $alias->id,
            'recipient_id' => $recipient->id,
        ]);
    }

    #[Test]
    public function custom_alias_random_characters_uses_a_free_suffix_when_others_are_taken()
    {
        $domain = $this->user->username.'.anonaddy.com';

        $this->user->update([
            'custom_alias_random_characters' => true,
            'custom_alias_random_length' => 1,
            'custom_alias_random_charset' => 'numbers',
            'alias_separator' => '.',
        ]);

        foreach (range(0, 8) as $digit) {
            Alias::factory()->create([
                'user_id' => $this->user->id,
                'local_part' => 'ab.'.$digit,
                'domain' => $domain,
                'email' => 'ab.'.$digit.'@'.$domain,
                'deleted_at' => now(),
            ]);
        }

        $response = $this->json('POST', '/api/v1/aliases', [
            'domain' => $domain,
            'format' => 'custom',
            'local_part' => 'ab',
        ]);

        $response->assertStatus(201);
        $this->assertSame('ab.9', $response->json('data.local_part'));
    }

    #[Test]
    public function custom_alias_random_characters_rejects_when_every_suffix_is_taken()
    {
        $domain = $this->user->username.'.anonaddy.com';

        $this->user->update([
            'custom_alias_random_characters' => true,
            'custom_alias_random_length' => 1,
            'custom_alias_random_charset' => 'numbers',
            'alias_separator' => '.',
        ]);

        foreach (range(0, 9) as $digit) {
            Alias::factory()->create([
                'user_id' => $this->user->id,
                'local_part' => 'ab.'.$digit,
                'domain' => $domain,
                'email' => 'ab.'.$digit.'@'.$domain,
            ]);
        }

        $response = $this->json('POST', '/api/v1/aliases', [
            'domain' => $domain,
            'format' => 'custom',
            'local_part' => 'ab',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('local_part_without_extension');
        $this->assertCount(10, $this->user->aliases()->withTrashed()->get());
    }

    #[Test]
    public function user_cannot_generate_custom_alias_that_matches_a_deleted_alias()
    {
        Alias::factory()->create([
            'user_id' => $this->user->id,
            'local_part' => 'exists',
            'domain' => $this->user->username.'.anonaddy.com',
            'email' => 'exists@'.$this->user->username.'.anonaddy.com',
            'deleted_at' => now(),
        ]);

        $response = $this->json('POST', '/api/v1/aliases', [
            'domain' => $this->user->username.'.anonaddy.com',
            'format' => 'custom',
            'local_part' => 'exists',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'local_part_without_extension' => 'That alias already exists.',
        ]);
        $this->assertCount(1, $this->user->aliases()->withTrashed()->get());
    }
}
