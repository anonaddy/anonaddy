<?php

namespace Tests\Feature\Api;

use App\AdditionalUsername;
use App\DeletedUsername;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdditionalUsernamesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        parent::setUpPassport();
    }

    /** @test */
    public function user_can_get_all_additional_usernames()
    {
        // Arrange
        factory(AdditionalUsername::class, 3)->create([
            'user_id' => $this->user->id
        ]);

        // Act
        $response = $this->get('/api/v1/usernames');

        // Assert
        $response->assertSuccessful();
        $this->assertCount(3, $response->json()['data']);
    }

    /** @test */
    public function user_can_get_individual_additional_username()
    {
        // Arrange
        $username = factory(AdditionalUsername::class)->create([
            'user_id' => $this->user->id
        ]);

        // Act
        $response = $this->get('/api/v1/usernames/'.$username->id);

        // Assert
        $response->assertSuccessful();
        $this->assertCount(1, $response->json());
        $this->assertEquals($username->username, $response->json()['data']['username']);
    }

    /** @test */
    public function user_can_create_additional_username()
    {
        $response = $this->json('POST', '/api/v1/usernames', [
            'username' => 'janedoe'
        ]);

        $response->assertStatus(201);
        $this->assertEquals('janedoe', $response->getData()->data->username);
        $this->assertEquals(1, $this->user->username_count);
    }

    /** @test */
    public function user_can_not_exceed_additional_username_limit()
    {
        $this->json('POST', '/api/v1/usernames', [
            'username' => 'username1'
        ]);

        $this->json('POST', '/api/v1/usernames', [
            'username' => 'username2'
        ]);

        $this->json('POST', '/api/v1/usernames', [
            'username' => 'username3'
        ]);

        $response = $this->json('POST', '/api/v1/usernames', [
            'username' => 'janedoe'
        ]);

        $response->assertStatus(403);
        $this->assertEquals(3, $this->user->username_count);
        $this->assertCount(3, $this->user->additionalUsernames);
    }

    /** @test */
    public function user_can_not_create_the_same_username()
    {
        factory(AdditionalUsername::class)->create([
            'user_id' => $this->user->id,
            'username' => 'janedoe'
        ]);

        $response = $this->json('POST', '/api/v1/usernames', [
            'username' => 'janedoe'
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors('username');
    }

    /** @test */
    public function user_can_not_create_additional_username_that_has_been_deleted()
    {
        factory(DeletedUsername::class)->create([
            'username' => 'janedoe'
        ]);

        $response = $this->json('POST', '/api/v1/usernames', [
            'username' => 'janedoe'
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors('username');
    }

    /** @test */
    public function must_be_unique_across_users_and_additional_usernames_tables()
    {
        $user = factory(User::class)->create();

        $response = $this->json('POST', '/api/v1/usernames', [
            'username' => $user->username
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors('username');
    }

    /** @test */
    public function additional_username_must_be_alpha_numeric()
    {
        $response = $this->json('POST', '/api/v1/usernames', [
            'username' => 'username01_'
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors('username');
    }

    /** @test */
    public function additional_username_must_be_less_than_max_length()
    {
        $response = $this->json('POST', '/api/v1/usernames', [
            'username' => 'abcdefghijklmnopqrstu'
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors('username');
    }

    /** @test */
    public function user_can_activate_additional_username()
    {
        $username = factory(AdditionalUsername::class)->create([
            'user_id' => $this->user->id,
            'active' => false
        ]);

        $response = $this->json('POST', '/api/v1/active-usernames/', [
            'id' => $username->id
        ]);

        $response->assertStatus(200);
        $this->assertEquals(true, $response->getData()->data->active);
    }

    /** @test */
    public function user_can_deactivate_additional_username()
    {
        $username = factory(AdditionalUsername::class)->create([
            'user_id' => $this->user->id,
            'active' => true
        ]);

        $response = $this->json('DELETE', '/api/v1/active-usernames/'.$username->id);

        $response->assertStatus(204);
        $this->assertFalse($this->user->additionalUsernames[0]->active);
    }

    /** @test */
    public function user_can_update_additional_usernames_description()
    {
        $username = factory(AdditionalUsername::class)->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->json('PATCH', '/api/v1/usernames/'.$username->id, [
            'description' => 'The new description'
        ]);

        $response->assertStatus(200);
        $this->assertEquals('The new description', $response->getData()->data->description);
    }

    /** @test */
    public function user_can_delete_additional_username()
    {
        $username = factory(AdditionalUsername::class)->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->json('DELETE', '/api/v1/usernames/'.$username->id);

        $response->assertStatus(204);
        $this->assertEmpty($this->user->additionalUsernames);

        $this->assertEquals(DeletedUsername::first()->username, $username->username);
    }
}
