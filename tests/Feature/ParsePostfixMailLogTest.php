<?php

namespace Tests\Feature;

use App\Models\Alias;
use App\Models\FailedDelivery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ParsePostfixMailLogTest extends TestCase
{
    use RefreshDatabase;

    protected $logPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->createUser();

        Storage::fake('local');
        $this->logPath = storage_path('app/mail.log');
        Config::set('anonaddy.postfix_log_path', $this->logPath);
        Config::set('anonaddy.all_domains', ['anonaddy.me']);
    }

    protected function tearDown(): void
    {
        if (file_exists($this->logPath)) {
            unlink($this->logPath);
        }
        parent::tearDown();
    }

    public function test_it_parses_rejection_lines_and_creates_failed_delivery_for_users()
    {
        $alias = Alias::factory()->create(['user_id' => $this->user->id, 'email' => 'test@anonaddy.me']);

        $logContent = "Mar 17 10:30:00 server postfix/smtpd[12345]: NOQUEUE: reject: RCPT from unknown[1.2.3.4]: 550 5.7.1 Client host rejected: policy; from=<s@x.com> to=<test@anonaddy.me> proto=ESMTP helo=<1.2.3.4>\n";
        file_put_contents($this->logPath, $logContent);

        $this->artisan('anonaddy:parse-postfix-mail-log')->assertExitCode(0);

        $this->assertDatabaseHas('failed_deliveries', [
            'user_id' => $this->user->id,
            'alias_id' => $alias->id,
            'email_type' => 'IR',
            'code' => '550 5.7.1 Client host rejected: policy',
            'status' => '550',
            'remote_mta' => 'unknown[1.2.3.4]',
        ]);

        $failedDelivery = FailedDelivery::first();
        $this->assertEquals('s@x.com', $failedDelivery->sender);
        $this->assertEquals('test@anonaddy.me', $failedDelivery->destination);
    }

    public function test_it_skips_transient_four_xx_rejections()
    {
        Alias::factory()->create(['user_id' => $this->user->id, 'email' => 'test@anonaddy.me']);

        $logContent = "Mar 17 10:30:00 server postfix/smtpd[12345]: NOQUEUE: reject: RCPT from unknown[1.2.3.4]: 450 4.7.1 Client host rejected: cannot find your hostname; from=<s@x.com> to=<test@anonaddy.me> proto=ESMTP helo=<1.2.3.4>\n";
        file_put_contents($this->logPath, $logContent);

        $this->artisan('anonaddy:parse-postfix-mail-log')->assertExitCode(0);

        $this->assertDatabaseCount('failed_deliveries', 0);
    }

    public function test_it_skips_missing_alias()
    {
        $logContent = "Mar 17 10:30:00 server postfix/smtpd[12345]: NOQUEUE: reject: RCPT from unknown[1.2.3.4]: 550 5.1.1 User unknown; from=<s@x.com> to=<nobody@anonaddy.me> proto=ESMTP helo=<1.2.3.4>\n";
        file_put_contents($this->logPath, $logContent);

        $this->artisan('anonaddy:parse-postfix-mail-log')->assertExitCode(0);

        $this->assertDatabaseCount('failed_deliveries', 0);
    }

    public function test_it_handles_log_rotation_and_maintains_position()
    {
        $alias = Alias::factory()->create(['user_id' => $this->user->id, 'email' => 'test@anonaddy.me']);

        $logContent1 = "Mar 17 10:30:00 server postfix/smtpd[12345]: NOQUEUE: reject: RCPT from unknown[1.2.3.4]: 550 5.7.1 Client host rejected; from=<s@x.com> to=<test@anonaddy.me>\n";
        file_put_contents($this->logPath, $logContent1);

        $this->artisan('anonaddy:parse-postfix-mail-log')->assertExitCode(0);
        $this->assertDatabaseCount('failed_deliveries', 1);

        // Add a second line to same file
        $logContent2 = "Mar 17 10:31:00 server postfix/smtpd[12345]: NOQUEUE: reject: RCPT from unknown[1.2.3.4]: 550 5.7.1 Client host rejected; from=<b@x.com> to=<test@anonaddy.me>\n";
        file_put_contents($this->logPath, $logContent1.$logContent2);

        $this->artisan('anonaddy:parse-postfix-mail-log')->assertExitCode(0);
        $this->assertDatabaseCount('failed_deliveries', 2);

        // Simulate log rotation (file smaller)
        $logContent3 = "Mar 17 10:32:00 server postfix/smtpd[12345]: NOQUEUE: reject: RCPT from unknown[1.2.3.4]: 550 5.7.1 Client host rejected; from=<c@x.com> to=<test@anonaddy.me>\n";
        file_put_contents($this->logPath, $logContent3);

        $this->artisan('anonaddy:parse-postfix-mail-log')->assertExitCode(0);
        $this->assertDatabaseCount('failed_deliveries', 3);
    }

    public function test_it_prevents_duplicate_rejections()
    {
        $alias = Alias::factory()->create(['user_id' => $this->user->id, 'email' => 'test@anonaddy.me']);

        $logContent = "Mar 17 10:30:00 server postfix/smtpd[12345]: NOQUEUE: reject: RCPT from unknown[1.2.3.4]: 550 5.7.1 Client host rejected; from=<s@x.com> to=<test@anonaddy.me>\n";
        file_put_contents($this->logPath, $logContent);

        $this->artisan('anonaddy:parse-postfix-mail-log')->assertExitCode(0);
        $this->assertDatabaseCount('failed_deliveries', 1);

        // Reset position to force re-reading the same line
        Storage::disk('local')->put('postfix_log_position.txt', '0');

        $this->artisan('anonaddy:parse-postfix-mail-log')->assertExitCode(0);
        $this->assertDatabaseCount('failed_deliveries', 1); // Should not duplicate
    }

    public function test_it_parses_milter_reject_lines()
    {
        $alias = Alias::factory()->create(['user_id' => $this->user->id, 'email' => 'test@anonaddy.com']);

        $logContent = "Mar 18 12:53:05 mail2 postfix/cleanup[1661539]: 7EB9BFF16A: milter-reject: END-OF-MESSAGE from mx.abc.eu[86.106.123.126]: 5.7.1 Spam message rejected; from=<noreply@hi.market> to=<test@anonaddy.com> proto=ESMTP helo=<mx.abc.eu>\n";
        file_put_contents($this->logPath, $logContent);

        $this->artisan('anonaddy:parse-postfix-mail-log')->assertExitCode(0);

        $this->assertDatabaseHas('failed_deliveries', [
            'user_id' => $this->user->id,
            'alias_id' => $alias->id,
            'email_type' => 'IR',
            'code' => '5.7.1 Spam message rejected',
            'status' => '5.7.1',
            'remote_mta' => 'mx.abc.eu[86.106.123.126]',
            'bounce_type' => 'spam',
        ]);

        $failedDelivery = FailedDelivery::first();
        $this->assertEquals('noreply@hi.market', $failedDelivery->sender);
        $this->assertEquals('test@anonaddy.com', $failedDelivery->destination);
    }

    public function test_it_parses_discard_lines()
    {
        $alias = Alias::factory()->create(['user_id' => $this->user->id, 'email' => 'caloric.test@anonaddy.com']);

        $logContent = "Mar 18 06:55:15 mail2 postfix/smtpd[1491842]: NOQUEUE: discard: RCPT from a.b.com[52.48.1.81]: <caloric.test@anonaddy.com>: Recipient address is inactive alias; from=<takedown@b.com> to=<caloric.test@anonaddy.com> proto=SMTP helo=<a.b.com>\n";
        file_put_contents($this->logPath, $logContent);

        $this->artisan('anonaddy:parse-postfix-mail-log')->assertExitCode(0);

        $this->assertDatabaseHas('failed_deliveries', [
            'user_id' => $this->user->id,
            'alias_id' => $alias->id,
            'email_type' => 'IR',
            'code' => 'Email discarded because this alias is deactivated',
            'status' => '',
            'remote_mta' => 'a.b.com[52.48.1.81]',
            'bounce_type' => 'hard',
        ]);

        $failedDelivery = FailedDelivery::first();
        $this->assertEquals('takedown@b.com', $failedDelivery->sender);
        $this->assertEquals('caloric.test@anonaddy.com', $failedDelivery->destination);
    }

    public function test_it_parses_reject_lines_with_ipv6_client_address()
    {
        Alias::factory()->create(['user_id' => $this->user->id, 'email' => 'xyz@addy.io']);

        $logContent = "Mar 18 10:00:00 mail postfix/smtpd[1]: NOQUEUE: reject: RCPT from unknown[1450:4864:20::441]: <xyz@addy.io>: Recipient address is inactive alias; from=<spam@example.com> to=<xyz@addy.io> proto=ESMTP\n";
        file_put_contents($this->logPath, $logContent);

        $this->artisan('anonaddy:parse-postfix-mail-log')->assertExitCode(0);

        $failedDelivery = FailedDelivery::first();
        $this->assertEquals('unknown[1450:4864:20::441]', $failedDelivery->remote_mta);
        $this->assertEquals('Email discarded because this alias is deactivated', $failedDelivery->code);
        $this->assertEquals('', $failedDelivery->status);
    }

    public function test_it_parses_reject_lines_with_ipv6_and_smtp_status_codes()
    {
        Alias::factory()->create(['user_id' => $this->user->id, 'email' => 'abc@example.com']);

        $logContent = "Mar 18 10:00:00 mail postfix/smtpd[1]: NOQUEUE: reject: RCPT from unknown[f8b0:4864:20::34f]: 554 5.1.8 <abc@example.com>: Sender address rejected: Domain not found; from=<bad@x.com> to=<abc@example.com> proto=ESMTP\n";
        file_put_contents($this->logPath, $logContent);

        $this->artisan('anonaddy:parse-postfix-mail-log')->assertExitCode(0);

        $failedDelivery = FailedDelivery::first();
        $this->assertEquals('unknown[f8b0:4864:20::34f]', $failedDelivery->remote_mta);
        $this->assertEquals('554 5.1.8 <abc@example.com>: Sender address rejected: Domain not found', $failedDelivery->code);
        $this->assertEquals('554', $failedDelivery->status);
    }

    public function test_it_maps_blocklist_masked_reject_to_user_friendly_reason()
    {
        Alias::factory()->create(['user_id' => $this->user->id, 'email' => 'test@anonaddy.me']);

        $logContent = "Mar 19 10:30:00 server postfix/smtpd[12345]: NOQUEUE: reject: RCPT from unknown[1.2.3.4]: 5.7.1 550 5.1.1 Address not found; from=<s@x.com> to=<test@anonaddy.me> proto=ESMTP helo=<1.2.3.4>\n";
        file_put_contents($this->logPath, $logContent);

        $this->artisan('anonaddy:parse-postfix-mail-log')->assertExitCode(0);

        $this->assertDatabaseHas('failed_deliveries', [
            'user_id' => $this->user->id,
            'email_type' => 'IR',
            'code' => 'Email blocked because the sender is on your blocklist',
            'status' => '5.7.1',
            'remote_mta' => 'unknown[1.2.3.4]',
        ]);
    }

    public function test_it_maps_inactive_alias_reason_to_user_friendly_reason()
    {
        Alias::factory()->create(['user_id' => $this->user->id, 'email' => 'test@anonaddy.me']);

        $logContent = "Mar 19 10:31:00 server postfix/smtpd[12345]: NOQUEUE: discard: RCPT from unknown[1.2.3.4]: <test@anonaddy.me>: Recipient address is inactive alias; from=<s@x.com> to=<test@anonaddy.me> proto=ESMTP helo=<1.2.3.4>\n";
        file_put_contents($this->logPath, $logContent);

        $this->artisan('anonaddy:parse-postfix-mail-log')->assertExitCode(0);

        $this->assertDatabaseHas('failed_deliveries', [
            'user_id' => $this->user->id,
            'email_type' => 'IR',
            'code' => 'Email discarded because this alias is deactivated',
        ]);
    }

    public function test_it_maps_inactive_username_reason_to_user_friendly_reason()
    {
        Alias::factory()->create(['user_id' => $this->user->id, 'email' => 'test@anonaddy.me']);

        $logContent = "Mar 19 10:32:00 server postfix/smtpd[12345]: NOQUEUE: discard: RCPT from unknown[1.2.3.4]: <test@anonaddy.me>: Recipient address has inactive username; from=<s@x.com> to=<test@anonaddy.me> proto=ESMTP helo=<1.2.3.4>\n";
        file_put_contents($this->logPath, $logContent);

        $this->artisan('anonaddy:parse-postfix-mail-log')->assertExitCode(0);

        $this->assertDatabaseHas('failed_deliveries', [
            'user_id' => $this->user->id,
            'email_type' => 'IR',
            'code' => 'Email discarded because this alias username is deactivated',
        ]);
    }

    public function test_it_maps_inactive_domain_reason_to_user_friendly_reason()
    {
        Alias::factory()->create(['user_id' => $this->user->id, 'email' => 'test@anonaddy.me']);

        $logContent = "Mar 19 10:33:00 server postfix/smtpd[12345]: NOQUEUE: discard: RCPT from unknown[1.2.3.4]: <test@anonaddy.me>: Recipient address has inactive domain; from=<s@x.com> to=<test@anonaddy.me> proto=ESMTP helo=<1.2.3.4>\n";
        file_put_contents($this->logPath, $logContent);

        $this->artisan('anonaddy:parse-postfix-mail-log')->assertExitCode(0);

        $this->assertDatabaseHas('failed_deliveries', [
            'user_id' => $this->user->id,
            'email_type' => 'IR',
            'code' => 'Email discarded because this alias custom domain is deactivated',
        ]);
    }

    public function test_it_maps_deleted_alias_reason_to_user_friendly_reason()
    {
        Alias::factory()->create(['user_id' => $this->user->id, 'email' => 'example@alias.com']);

        $logContent = "Mar 19 10:34:00 server postfix/smtpd[12345]: NOQUEUE: reject: RCPT from unknown[1.2.3.4]: 550 5.1.1 <example@alias.com>: Recipient address rejected: Address does not exist; from=<s@x.com> to=<example@alias.com> proto=ESMTP helo=<1.2.3.4>\n";
        file_put_contents($this->logPath, $logContent);

        $this->artisan('anonaddy:parse-postfix-mail-log')->assertExitCode(0);

        $this->assertDatabaseHas('failed_deliveries', [
            'user_id' => $this->user->id,
            'email_type' => 'IR',
            'code' => 'Email rejected because this alias was deleted',
            'status' => '550',
        ]);
    }
}
