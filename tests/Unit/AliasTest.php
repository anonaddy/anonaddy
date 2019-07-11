<?php

namespace Tests\Unit;

use App\Alias;
use App\AliasRecipient;
use App\Recipient;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AliasTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = factory(User::class)->create();
        $this->user->recipients()->save($this->user->defaultRecipient);
    }

    /** @test */
    public function alias_can_get_verified_recipients()
    {
        $alias = factory(Alias::class)->create([
            'user_id' => $this->user->id
        ]);

        $verifiedRecipient = factory(Recipient::class)->create([
            'user_id' => $this->user->id
        ]);

        $unverifiedRecipient = factory(Recipient::class)->create([
            'user_id' => $this->user->id,
            'email_verified_at' => null
        ]);

        AliasRecipient::create([
            'alias' => $alias,
            'recipient' => $verifiedRecipient
        ]);

        AliasRecipient::create([
            'alias' => $alias,
            'recipient' => $unverifiedRecipient
        ]);

        $this->assertCount(2, $alias->recipients);
        $this->assertCount(1, $alias->verifiedRecipients);
        $this->assertEquals($verifiedRecipient->id, $alias->verifiedRecipients[0]->id);
    }

    /** @test */
    public function alias_can_get_verified_recipient_emails()
    {
        $alias = factory(Alias::class)->create([
            'user_id' => $this->user->id
        ]);

        $recipientOne = factory(Recipient::class)->create([
            'user_id' => $this->user->id,
            'email' => 'one@example.com'
        ]);

        $recipientTwo = factory(Recipient::class)->create([
            'user_id' => $this->user->id,
            'email' => 'two@example.com'
        ]);

        $recipientThree = factory(Recipient::class)->create([
            'user_id' => $this->user->id,
            'email' => 'three@example.com'
        ]);

        AliasRecipient::create([
            'alias' => $alias,
            'recipient' => $recipientOne
        ]);

        AliasRecipient::create([
            'alias' => $alias,
            'recipient' => $recipientTwo
        ]);

        AliasRecipient::create([
            'alias' => $alias,
            'recipient' => $recipientThree
        ]);

        $recipientEmails = $alias->nonPgpRecipientEmails();

        $this->assertCount(3, $recipientEmails);
        $this->assertIsArray($recipientEmails);
        $this->assertEquals([$recipientOne->email, $recipientTwo->email, $recipientThree->email], $recipientEmails);
    }

    /** @test */
    public function alias_can_set_default_recipient_email()
    {
        factory(Alias::class)->create([
            'user_id' => $this->user->id
        ]);

        $recipient = factory(Recipient::class)->create([
            'user_id' => $this->user->id,
            'email' => 'default@example.com'
        ]);

        $this->user->defaultRecipient = $recipient;
        $this->user->save();

        $this->assertEquals($this->user->default_recipient_id, $recipient->id);
    }

    /** @test */
    public function alias_can_get_default_recipient_email()
    {
        factory(Alias::class)->create([
            'user_id' => $this->user->id
        ]);

        $recipient = factory(Recipient::class)->create([
            'user_id' => $this->user->id,
            'email' => 'default@example.com'
        ]);

        $this->user->defaultRecipient = $recipient;

        $this->assertEquals($this->user->email, $recipient->email);
    }

    /** @test */
    public function alias_can_get_recipients_using_pgp_or_not()
    {
        $alias = factory(Alias::class)->create([
            'user_id' => $this->user->id
        ]);

        $recipientOne = factory(Recipient::class)->create([
            'user_id' => $this->user->id,
            'email' => 'one@example.com',
            'should_encrypt' => true,
            'fingerprint' => 'ABCDE'
        ]);

        $recipientTwo = factory(Recipient::class)->create([
            'user_id' => $this->user->id,
            'email' => 'two@example.com',
            'should_encrypt' => true,
            'fingerprint' => 'ABCDE'
        ]);

        $recipientThree = factory(Recipient::class)->create([
            'user_id' => $this->user->id,
            'email' => 'three@example.com',
            'should_encrypt' => false,
            'fingerprint' => 'ABCDE'
        ]);

        $recipientFour = factory(Recipient::class)->create([
            'user_id' => $this->user->id,
            'email' => 'four@example.com',
            'should_encrypt' => true,
            'fingerprint' => null
        ]);

        AliasRecipient::create([
            'alias' => $alias,
            'recipient' => $recipientOne
        ]);

        AliasRecipient::create([
            'alias' => $alias,
            'recipient' => $recipientTwo
        ]);

        AliasRecipient::create([
            'alias' => $alias,
            'recipient' => $recipientThree
        ]);

        AliasRecipient::create([
            'alias' => $alias,
            'recipient' => $recipientFour
        ]);

        $pgpRecipients = $alias->recipientsUsingPgp();

        $nonPgpRecipients = $alias->nonPgpRecipientEmails();

        $this->assertCount(1, $nonPgpRecipients);
        $this->assertIsArray($nonPgpRecipients);
        $this->assertEquals([$recipientThree->email], $nonPgpRecipients);

        $this->assertTrue($alias->hasNonPgpRecipients());
        $this->assertCount(2, $pgpRecipients);
    }
}
