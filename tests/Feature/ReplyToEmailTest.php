<?php

namespace Tests\Feature;

use App\Mail\ReplyToEmail;
use App\Models\Alias;
use App\Models\Recipient;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ReplyToEmailTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create()->fresh();
        $this->user->recipients()->save($this->user->defaultRecipient);
        $this->user->defaultRecipient->email = 'will@anonaddy.com';
        $this->user->defaultRecipient->save();

        $this->user->usernames()->save($this->user->defaultUsername);
        $this->user->defaultUsername->username = 'johndoe';
        $this->user->defaultUsername->save();
    }

    /** @test */
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

    /** @test */
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

    /** @test */
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
}
