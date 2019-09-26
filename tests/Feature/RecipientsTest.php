<?php

namespace Tests\Feature;

use App\Alias;
use App\AliasRecipient;
use App\Recipient;
use App\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RecipientsTest extends TestCase
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
    public function user_can_view_recipients_from_the_recipients_page()
    {
        $recipients = factory(Recipient::class, 5)->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->get('/recipients');

        $response->assertSuccessful();
        $this->assertCount(5, $response->data('recipients'));
        $recipients->assertEquals($response->data('recipients'));
    }

    /** @test */
    public function latest_recipients_are_listed_first()
    {
        $a = factory(Recipient::class)->create([
            'user_id' => $this->user->id,
            'created_at' => Carbon::now()->subDays(15)
        ]);
        $b = factory(Recipient::class)->create([
            'user_id' => $this->user->id,
            'created_at' => Carbon::now()->subDays(5)
        ]);
        $c = factory(Recipient::class)->create([
            'user_id' => $this->user->id,
            'created_at' => Carbon::now()->subDays(10)
        ]);

        $response = $this->get('/recipients');

        $response->assertSuccessful();
        $this->assertCount(3, $response->data('recipients'));
        $this->assertTrue($response->data('recipients')[0]->is($b));
        $this->assertTrue($response->data('recipients')[1]->is($c));
        $this->assertTrue($response->data('recipients')[2]->is($a));
    }

    /** @test */
    public function recipients_are_listed_with_aliases_count()
    {
        $recipient = factory(Recipient::class)->create([
            'user_id' => $this->user->id
        ]);

        factory(Alias::class, 3)->create(['user_id' => $this->user->id])
        ->each(function ($alias) use ($recipient) {
            AliasRecipient::create([
                'alias' => $alias,
                'recipient' => $recipient
            ]);
        });

        $response = $this->get('/recipients');

        $response->assertSuccessful();
        $this->assertCount(1, $response->data('recipients'));
        $this->assertCount(3, $response->data('recipients')[0]['aliases']);
    }

    /** @test */
    public function user_can_create_new_recipient()
    {
        $response = $this->json('POST', '/recipients', [
            'email' => 'johndoe@example.com'
        ]);

        $response->assertStatus(200);
        $this->assertEquals('johndoe@example.com', $response->getData()->data->email);
    }

    /** @test */
    public function user_can_not_create_the_same_recipient()
    {
        factory(Recipient::class)->create([
            'user_id' => $this->user->id,
            'email' => 'johndoe@example.com'
        ]);

        $response = $this->json('POST', '/recipients', [
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

        $response = $this->json('POST', '/recipients', [
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

        $response = $this->json('POST', '/recipients', [
            'email' => $this->user->email
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    /** @test */
    public function new_recipient_must_have_valid_email()
    {
        $response = $this->json('POST', '/recipients', [
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

        $response = $this->json('DELETE', '/recipients/'.$recipient->id);

        $response->assertStatus(204);
        $this->assertEmpty($this->user->recipients);
    }

    /** @test */
    public function user_can_not_delete_default_recipient()
    {
        $this->user->recipients()->save($this->user->defaultRecipient);

        $defaultRecipient = $this->user->defaultRecipient;

        $response = $this->json('DELETE', '/recipients/'.$defaultRecipient->id);

        $response->assertStatus(403);
        $this->assertCount(1, $this->user->recipients);
        $this->assertEquals($defaultRecipient->id, $this->user->defaultRecipient->id);
    }

    /** @test */
    public function user_can_resend_recipient_verification_email()
    {
        Notification::fake();

        Notification::assertNothingSent();

        $recipient = factory(Recipient::class)->create([
            'user_id' => $this->user->id,
            'email_verified_at' => null
        ]);

        $response = $this->json('POST', '/recipients/email/resend', [
            'recipient_id' => $recipient->id
        ]);

        $response->assertStatus(200);

        Notification::assertSentTo(
            $recipient,
            VerifyEmail::class
        );
    }

    /** @test */
    public function user_must_wait_before_resending_recipient_verification_email()
    {
        Notification::fake();

        Notification::assertNothingSent();

        $recipient = factory(Recipient::class)->create([
            'user_id' => $this->user->id,
            'email_verified_at' => null
        ]);

        $response = $this->json('POST', '/recipients/email/resend', [
            'recipient_id' => $recipient->id
        ]);

        $response->assertStatus(200);

        Notification::assertSentTo(
            $recipient,
            VerifyEmail::class
        );

        $response2 = $this->json('POST', '/recipients/email/resend', [
            'recipient_id' => $recipient->id
        ]);

        $response2->assertStatus(429);
    }

    /** @test */
    public function user_can_add_gpg_key_to_recipient()
    {
        $gnupg = new \gnupg();
        $gnupg->deletekey('26A987650243B28802524E2F809FD0D502E2F695');

        $recipient = factory(Recipient::class)->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->json('PATCH', '/recipient-keys/'.$recipient->id, [
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

        $response = $this->json('PATCH', '/recipient-keys/'.$recipient->id, [
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

        $response = $this->json('PATCH', '/recipient-keys/'.$recipient->id, [
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

        $response = $this->json('DELETE', '/recipient-keys/'.$recipient->id);

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

        $response = $this->json('POST', '/encrypted-recipients/', [
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

        $response = $this->json('DELETE', '/encrypted-recipients/'.$recipient->id);

        $response->assertStatus(200);
        $this->assertEquals(false, $response->getData()->data->should_encrypt);
    }
}
