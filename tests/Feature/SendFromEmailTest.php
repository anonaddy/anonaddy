<?php

namespace Tests\Feature;

use App\Mail\SendFromEmail;
use App\Models\Alias;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SendFromEmailTest extends TestCase
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
    public function it_can_send_email_from_alias_from_file()
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
                'file' => base_path('tests/emails/email_send_from_alias.eml'),
                '--sender' => $this->user->email,
                '--recipient' => ['ebay+'.$extension.'@johndoe.anonaddy.com'],
                '--local_part' => ['ebay'],
                '--extension' => [$extension],
                '--domain' => ['johndoe.anonaddy.com'],
                '--size' => '1000',
            ]
        )->assertExitCode(0);

        $this->assertEquals(1, $this->user->aliases()->count());

        Mail::assertQueued(SendFromEmail::class, function ($mail) {
            return $mail->hasTo('contact@ebay.com');
        });
    }

    /** @test */
    public function it_can_send_from_alias_to_multiple_emails_from_file()
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
                'file' => base_path('tests/emails/email_multiple_send_from.eml'),
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

        Mail::assertQueued(SendFromEmail::class, function ($mail) {
            return $mail->hasTo('contact@ebay.com');
        });

        Mail::assertQueued(SendFromEmail::class, function ($mail) {
            return $mail->hasTo('support@ebay.com');
        });
    }

    /** @test */
    public function it_can_send_email_from_catch_all_alias_that_does_not_yet_exist()
    {
        Mail::fake();

        Mail::assertNothingSent();

        $extension = 'contact=ebay.com';

        $this->assertDatabaseMissing('aliases', [
            'email' => 'ebay@johndoe.anonaddy.com',
        ]);

        $this->artisan(
            'anonaddy:receive-email',
            [
                'file' => base_path('tests/emails/email_send_from_alias.eml'),
                '--sender' => $this->user->email,
                '--recipient' => ['ebay+'.$extension.'@johndoe.anonaddy.com'],
                '--local_part' => ['ebay'],
                '--extension' => [$extension],
                '--domain' => ['johndoe.anonaddy.com'],
                '--size' => '1000',
            ]
        )->assertExitCode(0);

        $this->assertDatabaseHas('aliases', [
            'email' => 'ebay@johndoe.anonaddy.com',
            'local_part' => 'ebay',
            'domain' => 'johndoe.anonaddy.com',
            'emails_forwarded' => 0,
            'emails_blocked' => 0,
            'emails_replied' => 0,
        ]);
        $this->assertEquals(1, $this->user->aliases()->count());

        Mail::assertQueued(SendFromEmail::class, function ($mail) {
            return $mail->hasTo('contact@ebay.com');
        });
    }
}
