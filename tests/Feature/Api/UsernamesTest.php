<?php

namespace Tests\Feature\Api;

use App\Models\DeletedUsername;
use App\Models\Recipient;
use App\Models\User;
use App\Models\Username;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class UsernamesTest extends TestCase
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
    public function user_can_get_all_usernames()
    {
        // Arrange
        Username::factory()->count(3)->create([
            'user_id' => $this->user->id,
        ]);

        // Act
        $response = $this->json('GET', '/api/v1/usernames');

        // Assert
        $response->assertSuccessful();
        $this->assertCount(4, $response->json()['data']);
    }

    /** @test */
    public function user_can_get_individual_username()
    {
        // Arrange
        $username = Username::factory()->create([
            'user_id' => $this->user->id,
        ]);

        // Act
        $response = $this->json('GET', '/api/v1/usernames/'.$username->id);

        // Assert
        $response->assertSuccessful();
        $this->assertCount(1, $response->json());
        $this->assertEquals($username->username, $response->json()['data']['username']);
    }

    /** @test */
    public function user_can_create_username()
    {
        $response = $this->json('POST', '/api/v1/usernames', [
            'username' => 'janedoe',
        ]);

        $response->assertStatus(201);
        $this->assertEquals('janedoe', $response->getData()->data->username);
        $this->assertEquals(1, $this->user->username_count);
    }

    /** @test */
    public function user_can_not_exceed_username_limit()
    {
        $this->json('POST', '/api/v1/usernames', [
            'username' => 'username1',
        ]);

        $this->json('POST', '/api/v1/usernames', [
            'username' => 'username2',
        ]);

        $this->json('POST', '/api/v1/usernames', [
            'username' => 'username3',
        ]);

        $response = $this->json('POST', '/api/v1/usernames', [
            'username' => 'janedoe',
        ]);

        $response->assertStatus(403);
        $this->assertEquals(3, $this->user->username_count);
        $this->assertCount(4, $this->user->usernames);
    }

    /** @test */
    public function user_can_not_create_the_same_username()
    {
        Username::factory()->create([
            'user_id' => $this->user->id,
            'username' => 'janedoe',
        ]);

        $response = $this->json('POST', '/api/v1/usernames', [
            'username' => 'janedoe',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors('username');
    }

    /** @test */
    public function user_can_not_create_username_that_has_been_deleted()
    {
        DeletedUsername::factory()->create([
            'username' => 'janedoe',
        ]);

        $response = $this->json('POST', '/api/v1/usernames', [
            'username' => 'janedoe',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors('username');
    }

    /** @test */
    public function must_be_unique_across_users_and_usernames_tables()
    {
        $user = User::factory()->create()->fresh();

        $response = $this->json('POST', '/api/v1/usernames', [
            'username' => $user->username,
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors('username');
    }

    /** @test */
    public function username_must_be_alpha_numeric()
    {
        $response = $this->json('POST', '/api/v1/usernames', [
            'username' => 'username01_',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors('username');
    }

    /** @test */
    public function username_must_be_less_than_max_length()
    {
        $response = $this->json('POST', '/api/v1/usernames', [
            'username' => 'abcdefghijklmnopqrstu',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors('username');
    }

    /** @test */
    public function user_can_activate_username()
    {
        $username = Username::factory()->create([
            'user_id' => $this->user->id,
            'active' => false,
        ]);

        $response = $this->json('POST', '/api/v1/active-usernames/', [
            'id' => $username->id,
        ]);

        $response->assertStatus(200);
        $this->assertEquals(true, $response->getData()->data->active);
    }

    /** @test */
    public function user_can_deactivate_username()
    {
        $username = Username::factory()->create([
            'user_id' => $this->user->id,
            'active' => true,
        ]);

        $response = $this->json('DELETE', '/api/v1/active-usernames/'.$username->id);

        $response->assertStatus(204);
        $this->assertFalse($this->user->usernames[1]->active);
    }

    /** @test */
    public function user_can_enable_catch_all_for_username()
    {
        $username = Username::factory()->create([
            'user_id' => $this->user->id,
            'catch_all' => false,
        ]);

        $response = $this->json('POST', '/api/v1/catch-all-usernames/', [
            'id' => $username->id,
        ]);

        $response->assertStatus(200);
        $this->assertTrue($response->getData()->data->catch_all);
    }

    /** @test */
    public function user_can_disable_catch_all_for_username()
    {
        $username = Username::factory()->create([
            'user_id' => $this->user->id,
            'catch_all' => true,
        ]);

        $response = $this->json('DELETE', '/api/v1/catch-all-usernames/'.$username->id);

        $response->assertStatus(204);
        $this->assertFalse($this->user->usernames[1]->catch_all);
    }

    /** @test */
    public function user_can_update_usernames_description()
    {
        $username = Username::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->json('PATCH', '/api/v1/usernames/'.$username->id, [
            'description' => 'The new description',
        ]);

        $response->assertStatus(200);
        $this->assertEquals('The new description', $response->getData()->data->description);
    }

    /** @test */
    public function user_can_delete_username()
    {
        $username = Username::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->json('DELETE', '/api/v1/usernames/'.$username->id);

        $response->assertStatus(204);
        $this->assertCount(1, $this->user->usernames);

        $this->assertEquals(DeletedUsername::first()->username, $username->username);
    }

    /** @test */
    public function user_can_not_delete_default_username()
    {
        $this->user->usernames()->save($this->user->defaultUsername);

        $defaultUsername = $this->user->defaultUsername;

        $response = $this->json('DELETE', '/api/v1/usernames/'.$defaultUsername->id);

        $response->assertStatus(403);
        $this->assertCount(1, $this->user->usernames);
        $this->assertEquals($defaultUsername->id, $this->user->defaultUsername->id);
    }

    /** @test */
    public function user_can_update_username_default_recipient()
    {
        $username = Username::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $newDefaultRecipient = Recipient::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->json('PATCH', '/api/v1/usernames/'.$username->id.'/default-recipient', [
            'default_recipient' => $newDefaultRecipient->id,
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('usernames', [
            'id' => $username->id,
            'default_recipient_id' => $newDefaultRecipient->id,
        ]);

        $this->assertEquals($newDefaultRecipient->email, $username->refresh()->defaultRecipient->email);
    }

    /** @test */
    public function user_cannot_update_username_default_recipient_with_unverified_recipient()
    {
        $username = Username::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $newDefaultRecipient = Recipient::factory()->create([
            'user_id' => $this->user->id,
            'email_verified_at' => null,
        ]);

        $response = $this->json('PATCH', '/api/v1/usernames/'.$username->id.'/default-recipient', [
            'default_recipient' => $newDefaultRecipient->id,
        ]);

        $response->assertStatus(404);
        $this->assertDatabaseMissing('usernames', [
            'id' => $username->id,
            'default_recipient_id' => $newDefaultRecipient->id,
        ]);
    }

    /** @test */
    public function user_can_remove_username_default_recipient()
    {
        $defaultRecipient = Recipient::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $username = Username::factory()->create([
            'user_id' => $this->user->id,
            'default_recipient_id' => $defaultRecipient->id,
        ]);

        $response = $this->json('PATCH', '/api/v1/usernames/'.$username->id.'/default-recipient', [
            'default_recipient' => '',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('usernames', [
            'id' => $username->id,
            'default_recipient_id' => null,
        ]);

        $this->assertNull($username->refresh()->defaultRecipient);
    }
}
