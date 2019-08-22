<?php

namespace Tests\Feature;

use App\AdditionalUsername;
use App\Alias;
use App\AliasRecipient;
use App\DeletedUsername;
use App\Domain;
use App\Recipient;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = factory(User::class)->create();
        $this->actingAs($this->user);
        $this->user->recipients()->save($this->user->defaultRecipient);
    }

    /** @test */
    public function user_can_update_default_recipient()
    {
        $newDefaultRecipient = factory(Recipient::class)->create([
            'user_id' => $this->user->id
        ]);

        $this->assertNotEquals($this->user->default_recipient_id, $newDefaultRecipient->id);

        $response = $this->post('/settings/default-recipient', [
            'default_recipient' => $newDefaultRecipient->id
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'default_recipient_id' => $newDefaultRecipient->id
        ]);
    }

    /** @test */
    public function user_can_not_update_to_unverified_default_recipient()
    {
        $newDefaultRecipient = factory(Recipient::class)->create([
            'user_id' => $this->user->id,
            'email_verified_at' => null
        ]);

        $this->assertNotEquals($this->user->default_recipient_id, $newDefaultRecipient->id);

        $response = $this->post('/settings/default-recipient', [
            'default_recipient' => $newDefaultRecipient->id
        ]);

        $response->assertStatus(404);
        $this->assertNotEquals($this->user->default_recipient_id, $newDefaultRecipient->id);
    }

    /** @test */
    public function user_can_update_reply_from_name()
    {
        $this->assertNull($this->user->from_name);

        $response = $this->post('/settings/from-name', [
            'from_name' => 'John Doe'
        ]);

        $response->assertStatus(302);
        $this->assertEquals('John Doe', $this->user->from_name);
    }

    /** @test */
    public function user_can_update_reply_from_name_to_empty()
    {
        $this->assertNull($this->user->from_name);

        $response = $this->post('/settings/from-name', [
            'from_name' => ''
        ]);

        $response->assertStatus(302);
        $this->assertEquals(null, $this->user->from_name);
    }

    /** @test */
    public function user_can_update_email_subject()
    {
        $this->assertNull($this->user->email_subject);

        $response = $this->post('/settings/email-subject', [
            'email_subject' => 'The subject'
        ]);

        $response->assertStatus(302);
        $this->assertEquals('The subject', $this->user->email_subject);
    }

    /** @test */
    public function user_can_update_email_subject_to_empty()
    {
        $this->assertNull($this->user->email_subject);

        $response = $this->post('/settings/email-subject', [
            'email_subject' => ''
        ]);

        $response->assertStatus(302);
        $this->assertEquals(null, $this->user->email_subject);
    }

    /** @test */
    public function user_can_update_email_banner_location()
    {
        $this->assertEquals('top', $this->user->banner_location);

        $response = $this->post('/settings/banner-location', [
            'banner_location' => 'bottom'
        ]);

        $response->assertStatus(302);
        $this->assertEquals('bottom', $this->user->banner_location);
    }

    /** @test */
    public function user_cannot_update_email_banner_location_to_incorrect_value()
    {
        $this->assertEquals('top', $this->user->banner_location);

        $response = $this->post('/settings/banner-location', [
            'banner_location' => 'side'
        ]);

        $response->assertStatus(302);

        $response->assertSessionHasErrors(['banner_location']);
        $this->assertEquals('top', $this->user->banner_location);
    }

    /** @test */
    public function user_can_delete_account()
    {
        $this->assertNotNull($this->user->id);

        $this->user->update(['password' => Hash::make('mypassword')]);

        if (!Hash::check('mypassword', $this->user->password)) {
            $this->fail('Password does not match');
        }

        $alias = factory(Alias::class)->create([
            'user_id' => $this->user->id
        ]);

        $recipient = factory(Recipient::class)->create([
            'user_id' => $this->user->id
        ]);

        AliasRecipient::create([
            'alias' => $alias,
            'recipient' => $recipient
        ]);

        $domain = factory(Domain::class)->create([
            'user_id' => $this->user->id
        ]);

        $aliasWithCustomDomain = factory(Alias::class)->create([
            'user_id' => $this->user->id,
            'domain_id' => $domain->id
        ]);

        $additionalUsername = factory(AdditionalUsername::class)->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->post('/settings/account', [
            'current_password_delete' => 'mypassword'
        ]);

        $response->assertRedirect('/login');

        $this->assertDatabaseMissing('users', [
            'id' => $this->user->id,
            'username' => $this->user->username,
        ]);

        $this->assertDatabaseMissing('alias_recipients', [
            'alias_id' => $alias->id,
            'recipient_id' => $recipient->username,
        ]);

        $this->assertDatabaseMissing('aliases', [
            'id' => $alias->id,
            'user_id' => $this->user->id
        ]);

        $this->assertDatabaseHas('aliases', [
            'id' => $aliasWithCustomDomain->id,
            'user_id' => $this->user->id,
            'domain_id' => $domain->id,
            'deleted_at' => now()
        ]);

        $this->assertDatabaseMissing('recipients', [
            'id' => $recipient->id,
            'user_id' => $this->user->id,
        ]);

        $this->assertDatabaseMissing('domains', [
            'id' => $domain->id,
            'user_id' => $this->user->id,
        ]);

        $this->assertDatabaseMissing('additional_usernames', [
            'id' => $additionalUsername->id,
            'user_id' => $this->user->id
        ]);

        $this->assertEquals(DeletedUsername::first()->username, $this->user->username);
        $this->assertEquals(DeletedUsername::skip(1)->first()->username, $additionalUsername->username);
    }

    /** @test */
    public function user_must_enter_correct_password_to_delete_account()
    {
        $this->assertNotNull($this->user->id);

        $this->user->update(['password' => Hash::make('mypassword')]);

        if (!Hash::check('mypassword', $this->user->password)) {
            $this->fail('Password does not match');
        }

        $response = $this->post('/settings/account', [
            'current_password_delete' => 'wrongpassword'
        ]);

        $response->assertStatus(302);

        $response->assertSessionHasErrors(['current_password_delete']);
        $this->assertNull(DeletedUsername::first());

        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'username' => $this->user->username,
        ]);
    }
}
