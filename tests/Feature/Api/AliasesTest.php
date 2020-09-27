<?php

namespace Tests\Feature\Api;

use App\Models\AdditionalUsername;
use App\Models\Alias;
use App\Models\Domain;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AliasesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        parent::setUpPassport();
    }

    /** @test */
    public function user_can_get_all_aliases()
    {
        // Arrange
        Alias::factory()->count(3)->create([
            'user_id' => $this->user->id
        ]);

        // Act
        $response = $this->get('/api/v1/aliases');

        // Assert
        $response->assertSuccessful();
        $this->assertCount(3, $response->json()['data']);
    }

    /** @test */
    public function user_can_get_all_aliases_including_deleted()
    {
        // Arrange
        Alias::factory()->count(2)->create([
            'user_id' => $this->user->id
        ]);

        Alias::factory()->create([
            'user_id' => $this->user->id,
            'deleted_at' => now()
        ]);

        // Act
        $response = $this->get('/api/v1/aliases?deleted=with');

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
            'deleted_at' => now()
        ]);

        Alias::factory()->create([
            'user_id' => $this->user->id
        ]);

        // Act
        $response = $this->get('/api/v1/aliases?deleted=only');

        // Assert
        $response->assertSuccessful();
        $this->assertCount(2, $response->json()['data']);
    }

    /** @test */
    public function user_can_get_individual_alias()
    {
        // Arrange
        $alias = Alias::factory()->create([
            'user_id' => $this->user->id
        ]);

        // Act
        $response = $this->get('/api/v1/aliases/'.$alias->id);

        // Assert
        $response->assertSuccessful();
        $this->assertCount(1, $response->json());
        $this->assertEquals($alias->email, $response->json()['data']['email']);
    }

    /** @test */
    public function user_can_generate_new_alias()
    {
        $response = $this->json('POST', '/api/v1/aliases', [
            'domain' => 'anonaddy.me',
            'description' => 'the description'
        ]);

        $response->assertStatus(201);
        $this->assertCount(1, $this->user->aliases);
        $this->assertEquals($this->user->aliases[0]->id, $response->getData()->data->local_part);
        $this->assertEquals($this->user->aliases[0]->id, $this->user->aliases[0]->local_part);
    }

    /** @test */
    public function user_can_generate_new_random_word_alias()
    {
        $response = $this->json('POST', '/api/v1/aliases', [
            'domain' => 'anonaddy.me',
            'description' => 'the description',
            'format' => 'random_words'
        ]);

        $response->assertStatus(201);
        $this->assertCount(1, $this->user->aliases);
        $this->assertNotEquals($this->user->aliases[0]->id, $response->getData()->data->local_part);
        $this->assertNotEquals($this->user->aliases[0]->id, $this->user->aliases[0]->local_part);
    }

    /** @test */
    public function user_can_generate_new_alias_with_correct_aliasable_type()
    {
        AdditionalUsername::factory()->create([
            'user_id' => $this->user->id,
            'username' => 'john'
        ]);

        $domain = Domain::factory()->create([
            'user_id' => $this->user->id,
            'domain' => 'john.xyz',
            'domain_verified_at' => now()
        ]);

        $response = $this->json('POST', '/api/v1/aliases', [
            'domain' => 'john.xyz',
            'description' => 'the description'
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
            'user_id' => $this->user->id
        ]);

        $response = $this->json('PATCH', '/api/v1/aliases/'.$alias->id, [
            'description' => 'The new description'
        ]);

        $response->assertStatus(200);
        $this->assertEquals('The new description', $response->getData()->data->description);
    }

    /** @test */
    public function user_can_delete_alias()
    {
        $alias = Alias::factory()->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->json('DELETE', '/api/v1/aliases/'.$alias->id);

        $response->assertStatus(204);
        $this->assertEmpty($this->user->aliases);
    }

    /** @test */
    public function user_can_restore_deleted_alias()
    {
        $alias = Alias::factory()->create([
            'user_id' => $this->user->id,
            'deleted_at' => now()
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
            'active' => false
        ]);

        $response = $this->json('POST', '/api/v1/active-aliases/', [
            'id' => $alias->id
        ]);

        $response->assertStatus(200);
        $this->assertEquals(true, $response->getData()->data->active);
    }

    /** @test */
    public function user_can_deactivate_alias()
    {
        $alias = Alias::factory()->create([
            'user_id' => $this->user->id,
            'active' => true
        ]);

        $response = $this->json('DELETE', '/api/v1/active-aliases/'.$alias->id);

        $response->assertStatus(204);
        $this->assertFalse($this->user->aliases[0]->active);
    }
}
