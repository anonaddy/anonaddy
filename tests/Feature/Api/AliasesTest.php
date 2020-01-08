<?php

namespace Tests\Feature\Api;

use App\Alias;
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
        factory(Alias::class, 3)->create([
            'user_id' => $this->user->id
        ]);

        // Act
        $response = $this->get('/api/v1/aliases');

        // Assert
        $response->assertSuccessful();
        $this->assertCount(3, $response->json()['data']);
    }

    /** @test */
    public function user_can_get_individual_alias()
    {
        // Arrange
        $alias = factory(Alias::class)->create([
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
            'uuid' => false
        ]);

        $response->assertStatus(201);
        $this->assertCount(1, $this->user->aliases);
        $this->assertNotEquals($this->user->aliases[0]->id, $response->getData()->data->local_part);
        $this->assertNotEquals($this->user->aliases[0]->id, $this->user->aliases[0]->local_part);
    }

    /** @test */
    public function user_can_update_alias_description()
    {
        $alias = factory(Alias::class)->create([
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
        $alias = factory(Alias::class)->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->json('DELETE', '/api/v1/aliases/'.$alias->id);

        $response->assertStatus(204);
        $this->assertEmpty($this->user->aliases);
    }

    /** @test */
    public function user_can_activate_alias()
    {
        $alias = factory(Alias::class)->create([
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
        $alias = factory(Alias::class)->create([
            'user_id' => $this->user->id,
            'active' => true
        ]);

        $response = $this->json('DELETE', '/api/v1/active-aliases/'.$alias->id);

        $response->assertStatus(204);
        $this->assertFalse($this->user->aliases[0]->active);
    }
}
