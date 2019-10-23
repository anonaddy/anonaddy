<?php

namespace Tests\Feature\Api;

use App\Recipient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecipientsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        parent::setUpPassport();
    }

    /** @test */
    public function user_can_get_all_recipients()
    {
        // Arrange
        factory(Recipient::class, 3)->create([
            'user_id' => $this->user->id
        ]);

        // Act
        $response = $this->get('/api/v1/recipients');

        // Assert
        $response->assertSuccessful();
        $this->assertCount(3, $response->json()['data']);
    }

    /** @test */
    public function user_can_get_individual_recipient()
    {
        // Arrange
        $recipient = factory(Recipient::class)->create([
            'user_id' => $this->user->id
        ]);

        // Act
        $response = $this->get('/api/v1/recipients/'.$recipient->id);

        // Assert
        $response->assertSuccessful();
        $this->assertCount(1, $response->json());
        $this->assertEquals($recipient->email, $response->json()['data']['email']);
    }

    /** @test */
    public function user_can_create_new_recipient()
    {
        $response = $this->json('POST', '/api/v1/recipients', [
            'email' => 'johndoe@example.com'
        ]);

        $response->assertStatus(201);
        $this->assertEquals('johndoe@example.com', $response->getData()->data->email);
    }

    /** @test */
    public function user_can_not_create_the_same_recipient()
    {
        factory(Recipient::class)->create([
            'user_id' => $this->user->id,
            'email' => 'johndoe@example.com'
        ]);

        $response = $this->json('POST', '/api/v1/recipients', [
            'email' => 'johndoe@example.com'
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    /** @test */
    public function user_can_not_create_the_same_recipient_in_uppercase()
    {
        factory(Recipient::class)->create([
            'user_id' => $this->user->id,
            'email' => 'johndoe@example.com'
        ]);

        $response = $this->json('POST', '/api/v1/recipients', [
            'email' => 'JOHNdoe@example.com'
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
            'email' => $this->user->email
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    /** @test */
    public function new_recipient_must_have_valid_email()
    {
        $response = $this->json('POST', '/api/v1/recipients', [
            'email' => 'johndoe@example.'
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    /** @test */
    public function user_can_delete_recipient()
    {
        $recipient = factory(Recipient::class)->create([
            'user_id' => $this->user->id
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

        $recipient = factory(Recipient::class)->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->json('PATCH', '/api/v1/recipient-keys/'.$recipient->id, [
            'key_data' => file_get_contents(base_path('tests/keys/AnonAddyPublicKey.asc'))
        ]);

        $response->assertStatus(200);
        $this->assertTrue($response->getData()->data->should_encrypt);
    }

    /** @test */
    public function gpg_key_must_be_correct_format()
    {
        $recipient = factory(Recipient::class)->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->json('PATCH', '/api/v1/recipient-keys/'.$recipient->id, [
            'key_data' => 'Invalid Key Data'
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors('key_data');
    }

    /** @test */
    public function gpg_key_must_be_valid()
    {
        $recipient = factory(Recipient::class)->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->json('PATCH', '/api/v1/recipient-keys/'.$recipient->id, [
            'key_data' => file_get_contents(base_path('tests/keys/InvalidAnonAddyPublicKey.asc'))
        ]);

        $response
            ->assertStatus(404);
    }

    /** @test */
    public function user_can_remove_gpg_key_from_recipient()
    {
        $gnupg = new \gnupg();
        $gnupg->import(file_get_contents(base_path('tests/keys/AnonAddyPublicKey.asc')));

        $recipient = factory(Recipient::class)->create([
            'user_id' => $this->user->id,
            'should_encrypt' => true,
            'fingerprint' => '26A987650243B28802524E2F809FD0D502E2F695'
        ]);

        $response = $this->json('DELETE', '/api/v1/recipient-keys/'.$recipient->id);

        $response->assertStatus(204);
        $this->assertNull($this->user->recipients[0]->fingerprint);
        $this->assertFalse($this->user->recipients[0]->should_encrypt);
    }

    /** @test */
    public function user_can_turn_on_encryption_for_recipient()
    {
        $recipient = factory(Recipient::class)->create([
            'user_id' => $this->user->id,
            'should_encrypt' => false,
            'fingerprint' => '26A987650243B28802524E2F809FD0D502E2F695'
        ]);

        $response = $this->json('POST', '/api/v1/encrypted-recipients/', [
            'id' => $recipient->id
        ]);

        $response->assertStatus(200);
        $this->assertEquals(true, $response->getData()->data->should_encrypt);
    }

    /** @test */
    public function user_can_turn_off_encryption_for_recipient()
    {
        $recipient = factory(Recipient::class)->create([
            'user_id' => $this->user->id,
            'should_encrypt' => true,
            'fingerprint' => '26A987650243B28802524E2F809FD0D502E2F695'
        ]);

        $response = $this->json('DELETE', '/api/v1/encrypted-recipients/'.$recipient->id);

        $response->assertStatus(204);
        $this->assertFalse($this->user->recipients[0]->should_encrypt);
    }
}
