<?php

namespace Tests\Feature;

use App\Mail\ForwardEmail;
use App\Mail\ReplyToEmail;
use App\Models\Alias;
use App\Models\OutboundMessage;
use App\Models\Recipient;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use ParagonIE\ConstantTime\Base32;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReplyToEmailTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->createUser('johndoe', 'will@anonaddy.com');
    }

    #[Test]
    public function it_can_reply_to_email_from_file()
    {
        Mail::fake();

        Mail::assertNothingSent();

        Alias::factory()->create([
            'user_id' => $this->user->id,
            'email' => 'ebay@johndoe.'.config('anonaddy.domain'),
            'local_part' => 'ebay',
            'domain' => 'johndoe.'.config('anonaddy.domain'),
        ]);

        $extension = 'contact=ebay.com';

        $this->artisan(
            'anonaddy:receive-email',
            [
                'file' => base_path('tests/emails/email_reply.eml'),
                '--sender' => $this->user->email,
                '--recipient' => ['ebay+'.$extension.'@johndoe.anonaddy.com'],
                '--local_part' => ['ebay'],
                '--extension' => [$extension],
                '--domain' => ['johndoe.anonaddy.com'],
                '--size' => '1000',
            ]
        )->assertExitCode(0);

        $this->assertEquals(1, $this->user->aliases()->count());

        Mail::assertQueued(ReplyToEmail::class, function ($mail) {
            return $mail->hasTo('contact@ebay.com');
        });
    }

    #[Test]
    public function it_cannot_reply_using_unverified_recipient()
    {
        Mail::fake();

        Mail::assertNothingSent();

        Alias::factory()->create([
            'user_id' => $this->user->id,
            'email' => 'ebay@johndoe.'.config('anonaddy.domain'),
            'local_part' => 'ebay',
            'domain' => 'johndoe.'.config('anonaddy.domain'),
        ]);

        $recipient = Recipient::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $this->user->email_verified_at = null;
        $this->user->save();

        $this->user->defaultRecipient = $recipient;
        $this->user->save();

        $extension = 'contact=ebay.com';

        $this->artisan(
            'anonaddy:receive-email',
            [
                'file' => base_path('tests/emails/email_reply.eml'),
                '--sender' => $recipient->email,
                '--recipient' => ['ebay+'.$extension.'@johndoe.anonaddy.com'],
                '--local_part' => ['ebay'],
                '--extension' => [$extension],
                '--domain' => ['johndoe.anonaddy.com'],
                '--size' => '1000',
            ]
        )->assertExitCode(0);

        $this->assertEquals(1, $this->user->aliases()->count());

        Mail::assertNotQueued(ReplyToEmail::class, function ($mail) {
            return $mail->hasTo('contact@ebay.com');
        });
    }

    #[Test]
    public function it_can_reply_to_multiple_emails_from_file()
    {
        Mail::fake();

        Mail::assertNothingSent();

        Alias::factory()->create([
            'user_id' => $this->user->id,
            'email' => 'ebay@johndoe.'.config('anonaddy.domain'),
            'local_part' => 'ebay',
            'domain' => 'johndoe.'.config('anonaddy.domain'),
        ]);

        $extension1 = 'contact=ebay.com';
        $extension2 = 'support=ebay.com';

        $this->artisan(
            'anonaddy:receive-email',
            [
                'file' => base_path('tests/emails/email_multiple_reply.eml'),
                '--sender' => $this->user->email,
                '--recipient' => [
                    'ebay+'.$extension1.'@johndoe.anonaddy.com',
                    'ebay+'.$extension2.'@johndoe.anonaddy.com',
                ],
                '--local_part' => ['ebay', 'ebay'],
                '--extension' => [$extension1, $extension2],
                '--domain' => ['johndoe.anonaddy.com', 'johndoe.anonaddy.com'],
                '--size' => '1000',
            ]
        )->assertExitCode(0);

        $this->assertEquals(1, $this->user->aliases()->count());

        Mail::assertQueued(ReplyToEmail::class, function ($mail) {
            return $mail->hasTo('contact@ebay.com');
        });

        Mail::assertQueued(ReplyToEmail::class, function ($mail) {
            return $mail->hasTo('support@ebay.com');
        });
    }

    #[Test]
    public function delivery_report_for_a_reply_from_a_non_null_sender_is_not_treated_as_a_bounce()
    {
        Mail::fake();

        $alias = Alias::factory()->create([
            'user_id' => $this->user->id,
            'email' => 'ebay@johndoe.'.config('anonaddy.domain'),
            'local_part' => 'ebay',
            'domain' => 'johndoe.'.config('anonaddy.domain'),
        ]);

        $outboundMessage = OutboundMessage::query()->create([
            'id' => 'abcd1234wxyz',
            'user_id' => $this->user->id,
            'alias_id' => $alias->id,
            'recipient_id' => $this->user->default_recipient_id,
            'email_type' => 'R',
            'encrypted' => false,
        ]);

        $signature = substr(hash_hmac('sha3-224', $outboundMessage->id, config('anonaddy.secret')), 0, 8);
        $verp = 'b_'.Base32::encodeUnpadded($outboundMessage->id).'_'.Base32::encodeUnpadded($signature).'@johndoe.'.config('anonaddy.domain');

        $this->artisan(
            'anonaddy:receive-email',
            [
                'file' => base_path('tests/emails/email_bounce.eml'),
                '--sender' => 'contact@ebay.com',
                '--recipient' => [$verp],
                '--local_part' => [Str::before($verp, '@')],
                '--extension' => [''],
                '--domain' => ['johndoe.'.config('anonaddy.domain')],
                '--size' => '1000',
            ]
        )->assertExitCode(0);

        $this->assertFalse($outboundMessage->refresh()->bounced);
        $this->assertSame(0, $this->user->failedDeliveries()->count());
        Mail::assertQueued(ForwardEmail::class);
    }
}
