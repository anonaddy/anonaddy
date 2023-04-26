<?php

namespace Tests\Feature\Api;

use App\Models\Domain;
use App\Models\Recipient;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RecipientsTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        parent::setUpSanctum();
    }

    /** @test */
    public function user_can_get_all_recipients()
    {
        // Arrange
        Recipient::factory()->count(3)->create([
            'user_id' => $this->user->id,
        ]);

        // Act
        $response = $this->json('GET', '/api/v1/recipients');

        // Assert
        $response->assertSuccessful();
        $this->assertCount(3, $response->json()['data']);
    }

    /** @test */
    public function user_can_get_individual_recipient()
    {
        // Arrange
        $recipient = Recipient::factory()->create([
            'user_id' => $this->user->id,
        ]);

        // Act
        $response = $this->json('GET', '/api/v1/recipients/'.$recipient->id);

        // Assert
        $response->assertSuccessful();
        $this->assertCount(1, $response->json());
        $this->assertEquals($recipient->email, $response->json()['data']['email']);
    }

    /** @test */
    public function user_can_create_new_recipient()
    {
        $response = $this->json('POST', '/api/v1/recipients', [
            'email' => 'johndoe@example.com',
        ]);

        $response->assertStatus(201);
        $this->assertEquals('johndoe@example.com', $response->getData()->data->email);
    }

    /** @test */
    public function user_can_create_auto_verified_recipient()
    {
        Notification::fake();

        Notification::assertNothingSent();

        config(['anonaddy.auto_verify_new_recipients' => true]);

        $response = $this->json('POST', '/api/v1/recipients', [
            'email' => 'johndoe@example.com',
        ]);

        $response->assertCreated();

        $recipient = Recipient::find($response->json('data.id'));

        $this->assertNotEmpty($recipient->email_verified_at);

        Notification::assertNotSentTo(
            $recipient,
            CustomVerifyEmail::class
        );
    }

    /** @test */
    public function user_can_not_create_the_same_recipient()
    {
        Recipient::factory()->create([
            'user_id' => $this->user->id,
            'email' => 'johndoe@example.com',
        ]);

        $response = $this->json('POST', '/api/v1/recipients', [
            'email' => 'johndoe@example.com',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    /** @test */
    public function user_can_not_create_the_same_recipient_in_uppercase()
    {
        Recipient::factory()->create([
            'user_id' => $this->user->id,
            'email' => 'johndoe@example.com',
        ]);

        $response = $this->json('POST', '/api/v1/recipients', [
            'email' => 'JOHNdoe@example.com',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    /** @test */
    public function user_can_not_create_the_same_recipient_as_default()
    {
        $this->user->recipients()->save($this->user->defaultRecipient);

        $response = $this->json('POST', '/api/v1/recipients', [
            'email' => $this->user->email,
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    /** @test */
    public function user_can_not_create_recipient_with_local_domain()
    {
        $response = $this->json('POST', '/api/v1/recipients', [
            'email' => 'johndoe@anonaddy.com',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    /** @test */
    public function user_can_not_create_recipient_with_local_custom_domain()
    {
        Domain::factory()->create([
            'user_id' => $this->user->id,
            'domain' => 'example.com',
            'domain_verified_at' => now(),
        ]);

        $response = $this->json('POST', '/api/v1/recipients', [
            'email' => 'johndoe@example.com',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    /** @test */
    public function new_recipient_must_have_valid_email()
    {
        $response = $this->json('POST', '/api/v1/recipients', [
            'email' => 'johndoe@example.',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    /** @test */
    public function user_can_delete_recipient()
    {
        $recipient = Recipient::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->json('DELETE', '/api/v1/recipients/'.$recipient->id);

        $response->assertStatus(204);
        $this->assertEmpty($this->user->recipients);
    }

    /** @test */
    public function user_can_not_delete_default_recipient()
    {
        $this->user->recipients()->save($this->user->defaultRecipient);

        $defaultRecipient = $this->user->defaultRecipient;

        $response = $this->json('DELETE', '/api/v1/recipients/'.$defaultRecipient->id);

        $response->assertStatus(403);
        $this->assertCount(1, $this->user->recipients);
        $this->assertEquals($defaultRecipient->id, $this->user->defaultRecipient->id);
    }

    /** @test */
    public function user_can_add_gpg_key_to_recipient()
    {
        $gnupg = new \gnupg();
        $gnupg->deletekey('26A987650243B28802524E2F809FD0D502E2F695');

        $recipient = Recipient::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->json('PATCH', '/api/v1/recipient-keys/'.$recipient->id, [
            'key_data' => file_get_contents(base_path('tests/keys/AnonAddyPublicKey.asc')),
        ]);

        $response->assertStatus(200);
        $this->assertTrue($response->getData()->data->should_encrypt);
    }

    /** @test */
    public function gpg_key_must_be_correct_format()
    {
        $recipient = Recipient::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->json('PATCH', '/api/v1/recipient-keys/'.$recipient->id, [
            'key_data' => 'Invalid Key Data',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors('key_data');
    }

    /** @test */
    public function gpg_key_must_be_valid()
    {
        $recipient = Recipient::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->json('PATCH', '/api/v1/recipient-keys/'.$recipient->id, [
            'key_data' => file_get_contents(base_path('tests/keys/InvalidAnonAddyPublicKey.asc')),
        ]);

        $response
            ->assertStatus(404);
    }

    /** @test */
    public function user_can_remove_gpg_key_from_recipient()
    {
        $gnupg = new \gnupg();
        $gnupg->import(file_get_contents(base_path('tests/keys/AnonAddyPublicKey.asc')));

        $recipient = Recipient::factory()->create([
            'user_id' => $this->user->id,
            'should_encrypt' => true,
            'fingerprint' => '26A987650243B28802524E2F809FD0D502E2F695',
        ]);

        $response = $this->json('DELETE', '/api/v1/recipient-keys/'.$recipient->id);

        $response->assertStatus(204);
        $this->assertNull($this->user->recipients[0]->fingerprint);
        $this->assertFalse($this->user->recipients[0]->should_encrypt);
    }

    /** @test */
    public function user_can_turn_on_encryption_for_recipient()
    {
        $recipient = Recipient::factory()->create([
            'user_id' => $this->user->id,
            'should_encrypt' => false,
            'fingerprint' => '26A987650243B28802524E2F809FD0D502E2F695',
        ]);

        $response = $this->json('POST', '/api/v1/encrypted-recipients/', [
            'id' => $recipient->id,
        ]);

        $response->assertStatus(200);
        $this->assertEquals(true, $response->getData()->data->should_encrypt);
    }

    /** @test */
    public function user_can_turn_off_encryption_for_recipient()
    {
        $recipient = Recipient::factory()->create([
            'user_id' => $this->user->id,
            'should_encrypt' => true,
            'fingerprint' => '26A987650243B28802524E2F809FD0D502E2F695',
        ]);

        $response = $this->json('DELETE', '/api/v1/encrypted-recipients/'.$recipient->id);

        $response->assertStatus(204);
        $this->assertFalse($this->user->recipients[0]->should_encrypt);
    }

    /** @test */
    public function user_can_allow_recipient_to_send_or_reply()
    {
        $recipient = Recipient::factory()->create([
            'user_id' => $this->user->id,
            'can_reply_send' => false,
        ]);

        $response = $this->json('POST', '/api/v1/allowed-recipients/', [
            'id' => $recipient->id,
        ]);

        $response->assertStatus(200);
        $this->assertEquals(true, $response->getData()->data->can_reply_send);
    }

    /** @test */
    public function user_can_disallow_recipient_from_sending_or_replying()
    {
        $recipient = Recipient::factory()->create([
            'user_id' => $this->user->id,
            'can_reply_send' => true,
        ]);

        $response = $this->json('DELETE', '/api/v1/allowed-recipients/'.$recipient->id);

        $response->assertStatus(204);
        $this->assertFalse($this->user->recipients[0]->can_reply_send);
    }

    /** @test */
    public function user_can_turn_on_inline_encryption()
    {
        $recipient = Recipient::factory()->create([
            'user_id' => $this->user->id,
            'inline_encryption' => false,
            'fingerprint' => '26A987650243B28802524E2F809FD0D502E2F695',
        ]);

        $response = $this->json('POST', '/api/v1/inline-encrypted-recipients/', [
            'id' => $recipient->id,
        ]);

        $response->assertStatus(200);
        $this->assertEquals(true, $response->getData()->data->inline_encryption);
    }

    /** @test */
    public function user_can_turn_off_inline_encryption()
    {
        $recipient = Recipient::factory()->create([
            'user_id' => $this->user->id,
            'inline_encryption' => true,
            'fingerprint' => '26A987650243B28802524E2F809FD0D502E2F695',
        ]);

        $response = $this->json('DELETE', '/api/v1/inline-encrypted-recipients/'.$recipient->id);

        $response->assertStatus(204);
        $this->assertFalse($this->user->recipients[0]->inline_encryption);
    }

    /** @test */
    public function user_can_turn_on_protected_headers()
    {
        $recipient = Recipient::factory()->create([
            'user_id' => $this->user->id,
            'protected_headers' => false,
            'fingerprint' => '26A987650243B28802524E2F809FD0D502E2F695',
        ]);

        $response = $this->json('POST', '/api/v1/protected-headers-recipients/', [
            'id' => $recipient->id,
        ]);

        $response->assertStatus(200);
        $this->assertEquals(true, $response->getData()->data->protected_headers);
    }

    /** @test */
    public function user_can_turn_off_protected_headers()
    {
        $recipient = Recipient::factory()->create([
            'user_id' => $this->user->id,
            'protected_headers' => true,
            'fingerprint' => '26A987650243B28802524E2F809FD0D502E2F695',
        ]);

        $response = $this->json('DELETE', '/api/v1/protected-headers-recipients/'.$recipient->id);

        $response->assertStatus(204);
        $this->assertFalse($this->user->recipients[0]->protected_headers);
    }
}
