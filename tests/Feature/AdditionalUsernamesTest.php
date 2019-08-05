<?php

namespace Tests\Feature;

use App\AdditionalUsername;
use App\DeletedUsername;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AdditionalUsernamesTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = factory(User::class)->create();
        $this->actingAs($this->user);
    }

    /** @test */
    public function user_can_view_usernames_from_the_usernames_page()
    {
        $usernames = factory(AdditionalUsername::class, 3)->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->get('/usernames');

        $response->assertSuccessful();
        $this->assertCount(3, $response->data('usernames'));
        $usernames->assertEquals($response->data('usernames'));
    }

    /** @test */
    public function latest_usernames_are_listed_first()
    {
        $a = factory(AdditionalUsername::class)->create([
            'user_id' => $this->user->id,
            'created_at' => Carbon::now()->subDays(15)
        ]);
        $b = factory(AdditionalUsername::class)->create([
            'user_id' => $this->user->id,
            'created_at' => Carbon::now()->subDays(5)
        ]);
        $c = factory(AdditionalUsername::class)->create([
            'user_id' => $this->user->id,
            'created_at' => Carbon::now()->subDays(10)
        ]);

        $response = $this->get('/usernames');

        $response->assertSuccessful();
        $this->assertCount(3, $response->data('usernames'));
        $this->assertTrue($response->data('usernames')[0]->is($b));
        $this->assertTrue($response->data('usernames')[1]->is($c));
        $this->assertTrue($response->data('usernames')[2]->is($a));
    }

    /** @test */
    public function user_can_create_additional_username()
    {
        $response = $this->json('POST', '/usernames', [
            'username' => 'janedoe'
        ]);

        $response->assertStatus(200);
        $this->assertEquals('janedoe', $response->getData()->data->username);
        $this->assertEquals(1, $this->user->username_count);
    }

    /** @test */
    public function user_can_not_exceed_additional_username_limit()
    {
        $this->json('POST', '/usernames', [
            'username' => 'username1'
        ]);

        $this->json('POST', '/usernames', [
            'username' => 'username2'
        ]);

        $this->json('POST', '/usernames', [
            'username' => 'username3'
        ]);

        $response = $this->json('POST', '/usernames', [
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

        $response = $this->json('POST', '/usernames', [
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

        $response = $this->json('POST', '/usernames', [
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

        $response = $this->json('POST', '/usernames', [
            'username' => $user->username
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors('username');
    }

    /** @test */
    public function additional_username_must_be_alpha_numeric()
    {
        $response = $this->json('POST', '/usernames', [
            'username' => 'username01_'
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors('username');
    }

    /** @test */
    public function additional_username_must_be_less_than_max_length()
    {
        $response = $this->json('POST', '/usernames', [
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

        $response = $this->json('POST', '/active-usernames/', [
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

        $response = $this->json('DELETE', '/active-usernames/'.$username->id);

        $response->assertStatus(200);
        $this->assertEquals(false, $response->getData()->data->active);
    }

    /** @test */
    public function user_can_update_additional_usernames_description()
    {
        $username = factory(AdditionalUsername::class)->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->json('PATCH', '/usernames/'.$username->id, [
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

        $response = $this->json('DELETE', '/usernames/'.$username->id);

        $response->assertStatus(204);
        $this->assertEmpty($this->user->additionalUsernames);

        $this->assertEquals(DeletedUsername::first()->username, $username->username);
    }
}
