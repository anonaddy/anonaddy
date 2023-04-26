<?php

namespace Tests\Unit;

use App\Models\Alias;
use App\Models\AliasRecipient;
use App\Models\Recipient;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AliasTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create()->fresh();
        $this->user->recipients()->save($this->user->defaultRecipient);
    }

    /** @test */
    public function alias_can_get_verified_recipients()
    {
        $alias = Alias::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $verifiedRecipient = Recipient::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $unverifiedRecipient = Recipient::factory()->create([
            'user_id' => $this->user->id,
            'email_verified_at' => null,
        ]);

        AliasRecipient::create([
            'alias' => $alias,
            'recipient' => $verifiedRecipient,
        ]);

        AliasRecipient::create([
            'alias' => $alias,
            'recipient' => $unverifiedRecipient,
        ]);

        $this->assertCount(2, $alias->recipients);
        $this->assertCount(1, $alias->verifiedRecipients);
        $this->assertEquals($verifiedRecipient->id, $alias->verifiedRecipients[0]->id);
    }

    /** @test */
    public function alias_can_set_default_recipient_email()
    {
        Alias::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $recipient = Recipient::factory()->create([
            'user_id' => $this->user->id,
            'email' => 'default@example.com',
        ]);

        $this->user->defaultRecipient = $recipient;
        $this->user->save();

        $this->assertEquals($this->user->default_recipient_id, $recipient->id);
    }

    /** @test */
    public function alias_can_get_default_recipient_email()
    {
        $alias = Alias::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $recipient = Recipient::factory()->create([
            'user_id' => $this->user->id,
            'email' => 'default@example.com',
        ]);

        $this->user->defaultRecipient = $recipient;
        $this->user->save();

        $this->assertEquals($this->user->email, $recipient->email);
        $this->assertCount(1, $alias->verifiedRecipientsOrDefault()->get());
        $alias->verifiedRecipientsOrDefault()->each(function ($recipient) {
            $this->assertEquals($this->user->email, $recipient->email);
        });
    }

    /** @test */
    public function alias_can_get_verified_recipients_or_default()
    {
        $alias = Alias::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $recipientOne = Recipient::factory()->create([
            'user_id' => $this->user->id,
            'email' => 'one@example.com',
            'should_encrypt' => true,
            'fingerprint' => 'ABCDE',
        ]);

        $recipientTwo = Recipient::factory()->create([
            'user_id' => $this->user->id,
            'email' => 'two@example.com',
            'should_encrypt' => true,
            'fingerprint' => 'ABCDE',
        ]);

        $recipientThree = Recipient::factory()->create([
            'user_id' => $this->user->id,
            'email' => 'three@example.com',
            'should_encrypt' => false,
            'fingerprint' => 'ABCDE',
        ]);

        $recipientFour = Recipient::factory()->create([
            'user_id' => $this->user->id,
            'email' => 'four@example.com',
            'should_encrypt' => true,
            'fingerprint' => null,
        ]);

        $recipientFive = Recipient::factory()->create([
            'user_id' => $this->user->id,
            'email' => 'five@example.com',
            'should_encrypt' => false,
            'fingerprint' => null,
            'email_verified_at' => null,
        ]);

        AliasRecipient::create([
            'alias' => $alias,
            'recipient' => $recipientOne,
        ]);

        AliasRecipient::create([
            'alias' => $alias,
            'recipient' => $recipientTwo,
        ]);

        AliasRecipient::create([
            'alias' => $alias,
            'recipient' => $recipientThree,
        ]);

        AliasRecipient::create([
            'alias' => $alias,
            'recipient' => $recipientFour,
        ]);

        AliasRecipient::create([
            'alias' => $alias,
            'recipient' => $recipientFive,
        ]);

        $this->assertCount(4, $alias->verifiedRecipientsOrDefault());
        $this->assertCount(5, $alias->recipients);
    }
}
