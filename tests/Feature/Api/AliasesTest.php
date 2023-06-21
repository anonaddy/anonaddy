<?php

namespace Tests\Feature\Api;

use App\Models\Alias;
use App\Models\Domain;
use App\Models\Recipient;
use App\Models\Username;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AliasesTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        parent::setUpSanctum();

        $this->user->recipients()->save($this->user->defaultRecipient);
        $this->user->usernames()->save($this->user->defaultUsername);
        $this->user->defaultUsername->username = 'johndoe';
        $this->user->defaultUsername->save();
    }

    /** @test */
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

    /** @test */
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

    /** @test */
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

    /** @test */
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

    /** @test */
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

    /** @test */
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

    /** @test */
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

    /** @test */
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

    /** @test */
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

    /** @test */
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

    /** @test */
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
        $response->assertJsonValidationErrors('local_part');
    }

    /** @test */
    public function user_can_generate_new_random_word_alias()
    {
        $response = $this->json('POST', '/api/v1/aliases', [
            'domain' => 'anonaddy.me',
            'description' => 'the description',
            'format' => 'random_words',
        ]);

        $response->assertStatus(201);
        $this->assertCount(1, $this->user->aliases);
        $this->assertNotEquals($this->user->aliases[0]->id, $response->getData()->data->local_part);
        $this->assertNotEquals($this->user->aliases[0]->id, $this->user->aliases[0]->local_part);
    }

    /** @test */
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

    /** @test */
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

    /** @test */
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

    /** @test */
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

    /** @test */
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

    /** @test */
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

    /** @test */
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

    /** @test */
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
}
