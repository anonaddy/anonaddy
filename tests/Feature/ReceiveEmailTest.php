<?php

namespace Tests\Feature;

use App\AdditionalUsername;
use App\Alias;
use App\AliasRecipient;
use App\Domain;
use App\Mail\ForwardEmail;
use App\Notifications\NearBandwidthLimit;
use App\Recipient;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ReceiveEmailTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = factory(User::class)->create(['username' => 'johndoe']);
        $this->user->recipients()->save($this->user->defaultRecipient);
    }

    /** @test */
    public function it_can_forward_email_from_file()
    {
        Mail::fake();
        Notification::fake();

        Mail::assertNothingSent();

        $this->artisan(
            'anonaddy:receive-email',
            [
                'file' => base_path('tests/emails/email.eml'),
                '--sender' => 'will@anonaddy.com',
                '--recipient' => ['ebay@johndoe.anonaddy.com'],
                '--local_part' => ['ebay'],
                '--extension' => [''],
                '--domain' => ['johndoe.anonaddy.com'],
                '--size' => '1000'
            ]
        )->assertExitCode(0);

        $this->assertDatabaseHas('aliases', [
            'email' => 'ebay@johndoe.'.config('anonaddy.domain'),
            'local_part' => 'ebay',
            'domain' => 'johndoe.'.config('anonaddy.domain'),
            'emails_forwarded' => 1,
            'emails_blocked' => 0
        ]);
        $this->assertEquals(1, $this->user->aliases()->count());
        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'username' => 'johndoe',
            'bandwidth' => '1000'
        ]);

        Mail::assertQueued(ForwardEmail::class, function ($mail) {
            return $mail->hasTo($this->user->email);
        });

        Notification::assertNothingSent();
    }

    /** @test */
    public function it_can_forward_email_from_file_with_attachment()
    {
        Mail::fake();

        Mail::assertNothingSent();

        $this->artisan(
            'anonaddy:receive-email',
            [
                'file' => base_path('tests/emails/email_with_attachment.eml'),
                '--sender' => 'will@anonaddy.com',
                '--recipient' => ['attachment@johndoe.anonaddy.com'],
                '--local_part' => ['attachment'],
                '--extension' => [''],
                '--domain' => ['johndoe.anonaddy.com'],
                '--size' => '1000'
            ]
        )->assertExitCode(0);

        $this->assertDatabaseHas('aliases', [
            'email' => 'attachment@johndoe.'.config('anonaddy.domain'),
            'local_part' => 'attachment',
            'domain' => 'johndoe.'.config('anonaddy.domain'),
            'emails_forwarded' => 1,
            'emails_blocked' => 0
        ]);
        $this->assertEquals(1, $this->user->aliases()->count());
        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'username' => 'johndoe',
            'bandwidth' => '1000'
        ]);

        Mail::assertQueued(ForwardEmail::class, function ($mail) {
            return $mail->hasTo($this->user->email);
        });
    }

    /** @test */
    public function it_can_forward_email_from_file_to_multiple_recipients()
    {
        Mail::fake();

        Mail::assertNothingSent();

        $this->artisan(
            'anonaddy:receive-email',
            [
                'file' => base_path('tests/emails/email_multiple_recipients.eml'),
                '--sender' => 'will@anonaddy.com',
                '--recipient' => ['ebay@johndoe.anonaddy.com', 'amazon@johndoe.anonaddy.com', 'paypal@johndoe.anonaddy.me'],
                '--local_part' => ['ebay', 'amazon', 'paypal'],
                '--extension' => ['', '', ''],
                '--domain' => ['johndoe.anonaddy.com', 'johndoe.anonaddy.com', 'johndoe.anonaddy.com'],
                '--size' => '1217'
            ]
        )->assertExitCode(0);

        $this->assertDatabaseHas('aliases', [
            'email' => 'ebay@johndoe.'.config('anonaddy.domain'),
            'local_part' => 'ebay',
            'domain' => 'johndoe.'.config('anonaddy.domain'),
            'emails_forwarded' => 1,
            'emails_blocked' => 0
        ]);
        $this->assertDatabaseHas('aliases', [
            'email' => 'amazon@johndoe.'.config('anonaddy.domain'),
            'local_part' => 'amazon',
            'domain' => 'johndoe.'.config('anonaddy.domain'),
            'emails_forwarded' => 1,
            'emails_blocked' => 0
        ]);
        $this->assertDatabaseHas('aliases', [
            'email' => 'paypal@johndoe.'.config('anonaddy.domain'),
            'local_part' => 'paypal',
            'domain' => 'johndoe.'.config('anonaddy.domain'),
            'emails_forwarded' => 1,
            'emails_blocked' => 0
        ]);
        $this->assertEquals(3, $this->user->aliases()->count());
        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'username' => 'johndoe',
            'bandwidth' => '1217'
        ]);

        Mail::assertQueued(ForwardEmail::class, function ($mail) {
            return $mail->hasTo($this->user->email);
        });
    }

    /** @test */
    public function it_can_forward_email_from_file_with_extension()
    {
        Mail::fake();

        Mail::assertNothingSent();

        $this->artisan(
            'anonaddy:receive-email',
            [
                'file' => base_path('tests/emails/email_with_extension.eml'),
                '--sender' => 'will@anonaddy.com',
                '--recipient' => ['ebay+a@johndoe.anonaddy.com'],
                '--local_part' => ['ebay'],
                '--extension' => ['2.3'],
                '--domain' => ['johndoe.anonaddy.com'],
                '--size' => '789'
            ]
        )->assertExitCode(0);

        $this->assertDatabaseHas('aliases', [
            'email' => 'ebay@johndoe.'.config('anonaddy.domain'),
            'local_part' => 'ebay',
            'domain' => 'johndoe.'.config('anonaddy.domain'),
            'emails_forwarded' => 1,
            'emails_blocked' => 0
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'username' => 'johndoe',
            'bandwidth' => '789'
        ]);
        $this->assertEquals(1, $this->user->aliases()->count());

        Mail::assertQueued(ForwardEmail::class, function ($mail) {
            return $mail->hasTo($this->user->email);
        });
    }

    /** @test */
    public function it_can_forward_email_with_existing_alias()
    {
        Mail::fake();

        Mail::assertNothingSent();

        factory(Alias::class)->create([
            'user_id' => $this->user->id,
            'email' => 'ebay@johndoe.'.config('anonaddy.domain'),
            'local_part' => 'ebay',
            'domain' => 'johndoe.'.config('anonaddy.domain'),
        ]);

        $defaultRecipient = $this->user->defaultRecipient;

        $this->artisan(
            'anonaddy:receive-email',
            [
                'file' => base_path('tests/emails/email.eml'),
                '--sender' => 'will@anonaddy.com',
                '--recipient' => ['ebay@johndoe.anonaddy.com'],
                '--local_part' => ['ebay'],
                '--extension' => [''],
                '--domain' => ['johndoe.anonaddy.com'],
                '--size' => '559'
            ]
        )->assertExitCode(0);

        $this->assertDatabaseHas('aliases', [
            'email' => 'ebay@johndoe.'.config('anonaddy.domain'),
            'emails_forwarded' => 1,
            'emails_blocked' => 0
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'username' => 'johndoe',
            'bandwidth' => '559'
        ]);
        $this->assertCount(1, $this->user->aliases);

        Mail::assertQueued(ForwardEmail::class, function ($mail) use ($defaultRecipient) {
            return $mail->hasTo($defaultRecipient->email);
        });
    }

    /** @test */
    public function it_can_forward_email_with_uuid_generated_alias()
    {
        Mail::fake();

        Mail::assertNothingSent();

        $uuid = '86064c92-da41-443e-a2bf-5a7b0247842f';

        config([
            'anonaddy.admin_username' => 'random'
        ]);

        factory(Alias::class)->create([
            'id' => $uuid,
            'user_id' => $this->user->id,
            'email' => $uuid.'@anonaddy.me',
            'local_part' => $uuid,
            'domain' => 'anonaddy.me',
        ]);

        $defaultRecipient = $this->user->defaultRecipient;

        $this->artisan(
            'anonaddy:receive-email',
            [
                'file' => base_path('tests/emails/email_with_uuid.eml'),
                '--sender' => 'will@anonaddy.com',
                '--recipient' => [$uuid.'@anonaddy.me'],
                '--local_part' => [$uuid],
                '--extension' => [''],
                '--domain' => ['anonaddy.me'],
                '--size' => '892'
            ]
        )->assertExitCode(0);

        $this->assertDatabaseHas('aliases', [
            'local_part' => $uuid,
            'domain' => 'anonaddy.me',
            'email' => $uuid.'@anonaddy.me',
            'emails_forwarded' => 1,
            'emails_blocked' => 0
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'username' => 'johndoe',
            'bandwidth' => '892'
        ]);
        $this->assertCount(1, $this->user->aliases);

        Mail::assertQueued(ForwardEmail::class, function ($mail) use ($defaultRecipient) {
            return $mail->hasTo($defaultRecipient->email);
        });
    }

    /** @test */
    public function it_can_forward_email_with_existing_alias_and_receipients()
    {
        Mail::fake();

        Mail::assertNothingSent();

        $alias = factory(Alias::class)->create([
            'user_id' => $this->user->id,
            'email' => 'ebay@johndoe.'.config('anonaddy.domain'),
            'local_part' => 'ebay',
            'domain' => 'johndoe.'.config('anonaddy.domain'),
        ]);

        $recipient = factory(Recipient::class)->create([
            'user_id' => $this->user->id,
            'email' => 'one@example.com'
        ]);

        $recipient2 = factory(Recipient::class)->create([
            'user_id' => $this->user->id,
            'email' => 'two@example.com'
        ]);

        AliasRecipient::create([
            'alias' => $alias,
            'recipient' => $recipient
        ]);

        AliasRecipient::create([
            'alias' => $alias,
            'recipient' => $recipient2
        ]);

        $this->artisan(
            'anonaddy:receive-email',
            [
                'file' => base_path('tests/emails/email.eml'),
                '--sender' => 'will@anonaddy.com',
                '--recipient' => ['ebay@johndoe.anonaddy.com'],
                '--local_part' => ['ebay'],
                '--extension' => [''],
                '--domain' => ['johndoe.anonaddy.com'],
                '--size' => '444'
            ]
        )->assertExitCode(0);

        $this->assertDatabaseHas('aliases', [
            'email' => 'ebay@johndoe.'.config('anonaddy.domain'),
            'emails_forwarded' => 1,
            'emails_blocked' => 0
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'username' => 'johndoe',
            'bandwidth' => '444'
        ]);

        Mail::assertQueued(ForwardEmail::class, function ($mail) {
            return $mail->hasTo('one@example.com') &&
                   $mail->hasTo('two@example.com');
        });
    }

    /** @test */
    public function it_can_attach_recipients_to_new_alias_with_extension()
    {
        Mail::fake();

        Mail::assertNothingSent();

        $recipient = factory(Recipient::class)->create([
            'user_id' => $this->user->id,
            'email' => 'one@example.com'
        ]);

        $recipient2 = factory(Recipient::class)->create([
            'user_id' => $this->user->id,
            'email' => 'two@example.com'
        ]);

        $this->artisan(
            'anonaddy:receive-email',
            [
                'file' => base_path('tests/emails/email_with_extension.eml'),
                '--sender' => 'will@anonaddy.com',
                '--recipient' => ['ebay@johndoe.anonaddy.com'],
                '--local_part' => ['ebay'],
                '--extension' => ['2.3'],
                '--domain' => ['johndoe.anonaddy.com'],
                '--size' => '444'
            ]
        )->assertExitCode(0);

        $this->assertDatabaseHas('aliases', [
            'extension' => '2.3',
            'email' => 'ebay@johndoe.'.config('anonaddy.domain'),
            'emails_forwarded' => 1,
            'emails_blocked' => 0
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'username' => 'johndoe',
            'bandwidth' => '444'
        ]);

        Mail::assertQueued(ForwardEmail::class, function ($mail) use ($recipient, $recipient2) {
            return $mail->hasTo($recipient->email) &&
                   $mail->hasTo($recipient2->email);
        });
    }

    /** @test */
    public function it_can_not_attach_unverified_recipient_to_new_alias_with_extension()
    {
        Mail::fake();

        Mail::assertNothingSent();

        $defaultRecipient = $this->user->defaultRecipient;

        factory(Recipient::class)->create([
            'user_id' => $this->user->id,
            'email' => 'one@example.com',
            'email_verified_at' => null
        ]);

        $verifiedRecipient = factory(Recipient::class)->create([
            'user_id' => $this->user->id,
            'email' => 'two@example.com'
        ]);

        $this->artisan(
            'anonaddy:receive-email',
            [
                'file' => base_path('tests/emails/email_with_extension.eml'),
                '--sender' => 'will@anonaddy.com',
                '--recipient' => ['ebay@johndoe.anonaddy.com'],
                '--local_part' => ['ebay'],
                '--extension' => ['2.3'],
                '--domain' => ['johndoe.anonaddy.com'],
                '--size' => '444'
            ]
        )->assertExitCode(0);

        $this->assertDatabaseHas('aliases', [
            'email' => 'ebay@johndoe.'.config('anonaddy.domain'),
            'emails_forwarded' => 1,
            'emails_blocked' => 0
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'username' => 'johndoe',
            'bandwidth' => '444'
        ]);

        $alias = $this->user->aliases()->where('email', 'ebay@johndoe.'.config('anonaddy.domain'))->first();

        $this->assertCount(1, $alias->recipients);

        Mail::assertQueued(ForwardEmail::class, function ($mail) use ($defaultRecipient, $verifiedRecipient) {
            return $mail->hasTo($verifiedRecipient->email);
        });
    }

    /** @test */
    public function it_does_not_send_email_if_default_recipient_has_not_yet_been_verified()
    {
        Mail::fake();

        Mail::assertNothingSent();

        $this->user->defaultRecipient->update(['email_verified_at' => null]);

        $this->assertNull($this->user->defaultRecipient->email_verified_at);

        $this->artisan(
            'anonaddy:receive-email',
            [
                'file' => base_path('tests/emails/email.eml'),
                '--sender' => 'will@anonaddy.com',
                '--recipient' => ['ebay@johndoe.anonaddy.com'],
                '--local_part' => ['ebay'],
                '--extension' => [''],
                '--domain' => ['johndoe.anonaddy.com'],
                '--size' => '1000'
            ]
        )->assertExitCode(0);

        $this->assertDatabaseMissing('aliases', [
            'email' => 'ebay@johndoe.'.config('anonaddy.domain'),
            'emails_blocked' => 0
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'username' => 'johndoe',
            'bandwidth' => '0'
        ]);

        Mail::assertNotSent(ForwardEmail::class);
    }

    /** @test */
    public function it_can_unsubscribe_alias_by_emailing_list_unsubscribe()
    {
        Mail::fake();

        Mail::assertNothingSent();

        factory(Alias::class)->create([
            'id' => '8f36380f-df4e-4875-bb12-9c4448573712',
            'user_id' => $this->user->id,
            'email' => 'ebay@johndoe.'.config('anonaddy.domain'),
            'local_part' => 'ebay',
            'domain' => 'johndoe.'.config('anonaddy.domain')
        ]);

        factory(Recipient::class)->create([
            'user_id' => $this->user->id,
            'email' => 'will@anonaddy.com'
        ]);

        $this->assertDatabaseHas('aliases', [
            'id' => '8f36380f-df4e-4875-bb12-9c4448573712',
            'user_id' => $this->user->id,
            'email' => 'ebay@johndoe.'.config('anonaddy.domain'),
            'active' => true
        ]);

        $this->artisan(
            'anonaddy:receive-email',
            [
                'file' => base_path('tests/emails/email_unsubscribe.eml'),
                '--sender' => 'will@anonaddy.com',
                '--recipient' => ['8f36380f-df4e-4875-bb12-9c4448573712@unsubscribe.anonaddy.com'],
                '--local_part' => ['8f36380f-df4e-4875-bb12-9c4448573712'],
                '--extension' => [''],
                '--domain' => ['unsubscribe.anonaddy.com'],
                '--size' => '1000'
            ]
        )->assertExitCode(0);

        $this->assertDatabaseHas('aliases', [
            'id' => '8f36380f-df4e-4875-bb12-9c4448573712',
            'user_id' => $this->user->id,
            'email' => 'ebay@johndoe.'.config('anonaddy.domain'),
            'local_part' => 'ebay',
            'domain' => 'johndoe.'.config('anonaddy.domain'),
            'active' => false
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'username' => 'johndoe',
            'bandwidth' => '0'
        ]);

        Mail::assertNotSent(ForwardEmail::class);
    }

    /** @test */
    public function it_does_not_count_unsubscribe_recipient_when_calculating_size()
    {
        Mail::fake();

        Mail::assertNothingSent();

        factory(Alias::class)->create([
            'id' => '8f36380f-df4e-4875-bb12-9c4448573712',
            'user_id' => $this->user->id,
            'email' => 'ebay@johndoe.'.config('anonaddy.domain'),
            'local_part' => 'ebay',
            'domain' => 'johndoe.'.config('anonaddy.domain')
        ]);

        factory(Recipient::class)->create([
            'user_id' => $this->user->id,
            'email' => 'will@anonaddy.com'
        ]);

        $this->assertDatabaseHas('aliases', [
            'id' => '8f36380f-df4e-4875-bb12-9c4448573712',
            'user_id' => $this->user->id,
            'email' => 'ebay@johndoe.'.config('anonaddy.domain'),
            'active' => true
        ]);

        $this->artisan(
            'anonaddy:receive-email',
            [
                'file' => base_path('tests/emails/email_unsubscribe_plus_other_recipient.eml'),
                '--sender' => 'will@anonaddy.com',
                '--recipient' => ['8f36380f-df4e-4875-bb12-9c4448573712@unsubscribe.anonaddy.com', 'another@johndoe.anonaddy.com'],
                '--local_part' => ['8f36380f-df4e-4875-bb12-9c4448573712', 'another'],
                '--extension' => ['', ''],
                '--domain' => ['unsubscribe.anonaddy.com', 'johndoe.anonaddy.com'],
                '--size' => '1000'
            ]
        )->assertExitCode(0);

        $this->assertDatabaseHas('aliases', [
            'id' => '8f36380f-df4e-4875-bb12-9c4448573712',
            'user_id' => $this->user->id,
            'email' => 'ebay@johndoe.'.config('anonaddy.domain'),
            'local_part' => 'ebay',
            'domain' => 'johndoe.'.config('anonaddy.domain'),
            'active' => false
        ]);
        $this->assertDatabaseHas('aliases', [
            'user_id' => $this->user->id,
            'email' => 'another@johndoe.'.config('anonaddy.domain'),
            'local_part' => 'another',
            'domain' => 'johndoe.'.config('anonaddy.domain'),
            'active' => true
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'username' => 'johndoe',
            'bandwidth' => '1000'
        ]);

        Mail::assertNotSent(ForwardEmail::class);
    }

    /** @test */
    public function it_cannot_unsubscribe_alias_if_not_a_verified_user_recipient()
    {
        Mail::fake();

        Mail::assertNothingSent();

        factory(Alias::class)->create([
            'id' => '8f36380f-df4e-4875-bb12-9c4448573712',
            'user_id' => $this->user->id,
            'email' => 'ebay@johndoe.'.config('anonaddy.domain'),
            'local_part' => 'ebay',
            'domain' => 'johndoe.'.config('anonaddy.domain')
        ]);

        $this->assertDatabaseHas('aliases', [
            'id' => '8f36380f-df4e-4875-bb12-9c4448573712',
            'user_id' => $this->user->id,
            'email' => 'ebay@johndoe.'.config('anonaddy.domain'),
            'active' => true
        ]);

        $this->artisan(
            'anonaddy:receive-email',
            [
                'file' => base_path('tests/emails/email_unsubscribe.eml'),
                '--sender' => 'will@anonaddy.com',
                '--recipient' => ['8f36380f-df4e-4875-bb12-9c4448573712@unsubscribe.anonaddy.com'],
                '--local_part' => ['8f36380f-df4e-4875-bb12-9c4448573712'],
                '--extension' => [''],
                '--domain' => ['unsubscribe.anonaddy.com'],
                '--size' => '1000'
            ]
        )->assertExitCode(0);

        $this->assertDatabaseHas('aliases', [
            'id' => '8f36380f-df4e-4875-bb12-9c4448573712',
            'user_id' => $this->user->id,
            'email' => 'ebay@johndoe.'.config('anonaddy.domain'),
            'local_part' => 'ebay',
            'domain' => 'johndoe.'.config('anonaddy.domain'),
            'active' => true
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'username' => 'johndoe',
            'bandwidth' => '0'
        ]);

        Mail::assertNotSent(ForwardEmail::class);
    }

    /** @test */
    public function it_can_forward_email_to_admin_username_for_root_domain()
    {
        Mail::fake();

        Mail::assertNothingSent();

        config(['anonaddy.admin_username' => 'johndoe']);

        $this->artisan(
            'anonaddy:receive-email',
            [
                'file' => base_path('tests/emails/email_admin.eml'),
                '--sender' => 'will@anonaddy.com',
                '--recipient' => ['ebay@anonaddy.me'],
                '--local_part' => ['ebay'],
                '--extension' => [''],
                '--domain' => ['anonaddy.me'],
                '--size' => '1346'
            ]
        )->assertExitCode(0);

        $this->assertDatabaseHas('aliases', [
            'email' => 'ebay@anonaddy.me',
            'local_part' => 'ebay',
            'domain' => 'anonaddy.me',
            'emails_forwarded' => 1,
            'emails_blocked' => 0
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'username' => 'johndoe',
            'bandwidth' => '1346'
        ]);
        $this->assertEquals(1, $this->user->aliases()->count());

        Mail::assertQueued(ForwardEmail::class, function ($mail) {
            return $mail->hasTo($this->user->email);
        });
    }

    /** @test */
    public function it_can_forward_email_for_custom_domain()
    {
        Mail::fake();

        Mail::assertNothingSent();

        $domain = factory(Domain::class)->create([
            'user_id' => $this->user->id,
            'domain' => 'example.com'
        ]);

        $this->artisan(
            'anonaddy:receive-email',
            [
                'file' => base_path('tests/emails/email_custom_domain.eml'),
                '--sender' => 'will@anonaddy.com',
                '--recipient' => ['ebay@example.com'],
                '--local_part' => ['ebay'],
                '--extension' => [''],
                '--domain' => ['example.com'],
                '--size' => '871'
            ]
        )->assertExitCode(0);

        $this->assertDatabaseHas('aliases', [
            'domain_id' => $domain->id,
            'email' => 'ebay@example.com',
            'local_part' => 'ebay',
            'domain' => 'example.com',
            'emails_forwarded' => 1,
            'emails_blocked' => 0
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'username' => 'johndoe',
            'bandwidth' => '871'
        ]);
        $this->assertEquals(1, $this->user->aliases()->count());

        Mail::assertQueued(ForwardEmail::class, function ($mail) {
            return $mail->hasTo($this->user->email);
        });
    }

    /** @test */
    public function it_can_forward_email_for_additional_username()
    {
        Mail::fake();

        Mail::assertNothingSent();

        factory(AdditionalUsername::class)->create([
            'user_id' => $this->user->id,
            'username' => 'janedoe'
        ]);

        $this->artisan(
            'anonaddy:receive-email',
            [
                'file' => base_path('tests/emails/email_additional_username.eml'),
                '--sender' => 'will@anonaddy.com',
                '--recipient' => ['ebay@janedoe.anonaddy.com'],
                '--local_part' => ['ebay'],
                '--extension' => [''],
                '--domain' => ['janedoe.anonaddy.com'],
                '--size' => '638'
            ]
        )->assertExitCode(0);

        $this->assertDatabaseHas('aliases', [
            'email' => 'ebay@janedoe.anonaddy.com',
            'local_part' => 'ebay',
            'domain' => 'janedoe.anonaddy.com',
            'emails_forwarded' => 1,
            'emails_blocked' => 0
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'username' => 'johndoe',
            'bandwidth' => '638'
        ]);
        $this->assertEquals(1, $this->user->aliases()->count());

        Mail::assertQueued(ForwardEmail::class, function ($mail) {
            return $mail->hasTo($this->user->email);
        });
    }

    /** @test */
    public function it_can_send_near_bandwidth_limit_notification()
    {
        Notification::fake();

        Notification::assertNothingSent();

        $this->user->update(['bandwidth' => 100943820]);

        $this->artisan(
            'anonaddy:receive-email',
            [
                'file' => base_path('tests/emails/email.eml'),
                '--sender' => 'will@anonaddy.com',
                '--recipient' => ['ebay@johndoe.anonaddy.com'],
                '--local_part' => ['ebay'],
                '--extension' => [''],
                '--domain' => ['johndoe.anonaddy.com'],
                '--size' => '1000'
            ]
        )->assertExitCode(0);

        $this->assertDatabaseHas('aliases', [
            'email' => 'ebay@johndoe.'.config('anonaddy.domain'),
            'local_part' => 'ebay',
            'domain' => 'johndoe.'.config('anonaddy.domain'),
            'emails_forwarded' => 1,
            'emails_blocked' => 0
        ]);
        $this->assertEquals(1, $this->user->aliases()->count());
        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'username' => 'johndoe',
            'bandwidth' => '100944820'
        ]);

        Notification::assertSentTo(
            $this->user,
            NearBandwidthLimit::class
        );
    }

    /** @test */
    public function it_can_forward_email_from_file_for_all_domains()
    {
        Mail::fake();

        Mail::assertNothingSent();

        $this->artisan(
            'anonaddy:receive-email',
            [
                'file' => base_path('tests/emails/email_other_domain.eml'),
                '--sender' => 'will@anonaddy.com',
                '--recipient' => ['ebay@johndoe.anonaddy.me'],
                '--local_part' => ['ebay'],
                '--extension' => [''],
                '--domain' => ['johndoe.anonaddy.me'],
                '--size' => '1000'
            ]
        )->assertExitCode(0);

        $this->assertDatabaseHas('aliases', [
            'email' => 'ebay@johndoe.anonaddy.me',
            'local_part' => 'ebay',
            'domain' => 'johndoe.anonaddy.me',
            'emails_forwarded' => 1,
            'emails_blocked' => 0
        ]);
        $this->assertEquals(1, $this->user->aliases()->count());
        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'username' => 'johndoe',
            'bandwidth' => '1000'
        ]);

        Mail::assertQueued(ForwardEmail::class, function ($mail) {
            return $mail->hasTo($this->user->email);
        });
    }
}
