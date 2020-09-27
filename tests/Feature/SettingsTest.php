<?php

namespace Tests\Feature;

use App\Exports\AliasesExport;
use App\Models\AdditionalUsername;
use App\Models\Alias;
use App\Models\AliasRecipient;
use App\Models\DeletedUsername;
use App\Models\Domain;
use App\Models\Recipient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);
        $this->user->recipients()->save($this->user->defaultRecipient);
    }

    /** @test */
    public function user_can_update_default_recipient()
    {
        $newDefaultRecipient = Recipient::factory()->create([
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
    public function user_cannot_update_to_unverified_default_recipient()
    {
        $newDefaultRecipient = Recipient::factory()->create([
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
    public function user_can_update_default_alias_domain()
    {
        $defaultAliasDomain = $this->user->username.'.anonaddy.me';

        $response = $this->post('/settings/default-alias-domain', [
            'domain' => $defaultAliasDomain
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'default_alias_domain' => $defaultAliasDomain
        ]);
    }

    /** @test */
    public function user_cannot_update_default_alias_domain_if_invalid()
    {
        $response = $this->post('/settings/default-alias-domain', [
            'domain' => 'johndoe.anonaddy.me'
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['domain']);
        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'default_alias_domain' => null
        ]);
    }

    /** @test */
    public function user_can_update_default_alias_format()
    {
        $defaultAliasFormat = 'random_words';

        $response = $this->post('/settings/default-alias-format', [
            'format' => $defaultAliasFormat
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'default_alias_format' => $defaultAliasFormat
        ]);
    }

    /** @test */
    public function user_cannot_update_default_alias_format_if_invalid()
    {
        $response = $this->post('/settings/default-alias-format', [
            'format' => 'invalid_format'
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['format']);
        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'default_alias_format' => null
        ]);
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

        $alias = Alias::factory()->create([
            'user_id' => $this->user->id
        ]);

        $uuidAlias = Alias::factory()->create([
            'user_id' => $this->user->id,
            'domain' => 'anonaddy.me'
        ]);
        $uuidAlias->update(['local_part' => $uuidAlias->id]);


        $recipient = Recipient::factory()->create([
            'user_id' => $this->user->id
        ]);

        AliasRecipient::create([
            'alias' => $alias,
            'recipient' => $recipient
        ]);

        $domain = Domain::factory()->create([
            'user_id' => $this->user->id
        ]);

        $aliasWithCustomDomain = Alias::factory()->create([
            'user_id' => $this->user->id,
            'aliasable_id' => $domain->id,
            'aliasable_type' => 'App\Models\Domain'
        ]);

        $additionalUsername = AdditionalUsername::factory()->create([
            'user_id' => $this->user->id
        ]);

        $aliasWithAdditionalUsername = Alias::factory()->create([
            'user_id' => $this->user->id,
            'aliasable_id' => $additionalUsername->id,
            'aliasable_type' => 'App\Models\AdditionaUsername'
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
            'id' => $uuidAlias->id,
            'user_id' => $this->user->id,
            'deleted_at' => now()
        ]);

        $this->assertDatabaseMissing('aliases', [
            'id' => $aliasWithCustomDomain->id,
            'user_id' => $this->user->id,
            'aliasable_id' => $domain->id,
            'aliasable_type' => 'App\Models\Domain'
        ]);

        $this->assertDatabaseMissing('aliases', [
            'id' => $aliasWithAdditionalUsername->id,
            'user_id' => $this->user->id,
            'aliasable_id' => $additionalUsername->id,
            'aliasable_type' => 'App\Models\AdditionalUsername'
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

    /**
    * @test
    */
    public function user_can_download_aliases_export()
    {
        Excel::fake();

        Alias::factory()->count(3)->create([
            'user_id' => $this->user->id
        ]);

        Alias::factory()->create([
            'user_id' => $this->user->id,
            'deleted_at' => now(),
            'active' => false
        ]);

        Alias::factory()->create();

        $this->actingAs($this->user)
            ->get('/settings/aliases/export');

        Excel::assertDownloaded('aliases-'.now()->toDateString().'.csv', function (AliasesExport $export) {
            $this->assertCount(4, $export->collection());
            return $export->collection()->contains(function ($alias) {
                return $alias['user_id'] === $this->user->id;
            });
        });
    }
}
