<?php

namespace Tests\Unit;

use App\Mail\ForwardEmail;
use App\Models\Alias;
use App\Models\EmailData;
use App\Models\Recipient;
use App\Models\Username;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PhpMimeMailParser\Parser;
use PHPUnit\Framework\Attributes\Test;
use ReflectionObject;
use Tests\TestCase;

class ForwardEmailBannerActionsTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['anonaddy.all_domains' => ['anonaddy.local']]);
    }

    #[Test]
    public function banner_has_block_email_and_block_domain_links(): void
    {
        $user = $this->createUser('johndoe');

        $mailable = $this->buildForwardEmail($user, 'News@Example.com');
        $html = $mailable->render();

        $this->assertStringContainsString('To:', $html);
        $this->assertStringContainsString('From:', $html);
        $this->assertStringContainsString('Actions:', $html);
        $this->assertStringContainsString('Deactivate', $html);
        $this->assertStringContainsString('/deactivate/', $html);
        $this->assertStringContainsString('Block&nbsp;email', $html);
        $this->assertStringContainsString('Block&nbsp;domain', $html);
        $this->assertStringContainsString('action=block_email', $html);
        $this->assertStringContainsString('action=block_domain', $html);
        $this->assertStringContainsString('email=news%40example.com', $html);

        $blockEmailUrl = $this->mailableProperty($mailable, 'blockEmailUrl');
        $blockDomainUrl = $this->mailableProperty($mailable, 'blockDomainUrl');

        $this->assertIsString($blockEmailUrl);
        $this->assertIsString($blockDomainUrl);
        $this->assertStringContainsString('action=block_email', $blockEmailUrl);
        $this->assertStringContainsString('action=block_domain', $blockDomainUrl);
    }

    #[Test]
    public function banner_does_not_get_a_block_domain_link_for_protected_alias_domains(): void
    {
        config(['anonaddy.all_domains' => ['addy.test']]);

        $user = $this->createUser('johndoe');

        $mailable = $this->buildForwardEmail($user, 'spoof@addy.test');
        $html = $mailable->render();

        $this->assertStringContainsString('Block&nbsp;email', $html);
        $this->assertStringNotContainsString('Block&nbsp;domain', $html);
        $this->assertIsString($this->mailableProperty($mailable, 'blockEmailUrl'));
        $this->assertNull($this->mailableProperty($mailable, 'blockDomainUrl'));
    }

    private function buildForwardEmail($user, string $from): ForwardEmail
    {
        $recipient = Recipient::factory()->create([
            'user_id' => $user->id,
            'email' => 'john@example.com',
        ]);

        $domain = config('anonaddy.domain');
        $alias = Alias::factory()->create([
            'user_id' => $user->id,
            'email' => 'ebay@johndoe.'.$domain,
            'local_part' => 'ebay',
            'domain' => 'johndoe.'.$domain,
            'aliasable_id' => $user->default_username_id,
            'aliasable_type' => Username::class,
        ]);

        $raw = "From: {$from}\r\n".
            "To: ebay@johndoe.{$domain}\r\n".
            "Subject: Banner actions test\r\n".
            "Message-ID: <banner-actions-test@example.com>\r\n".
            "MIME-Version: 1.0\r\n".
            "Content-Type: multipart/mixed; boundary=\"----=_Banner\"\r\n".
            "\r\n".
            "------=_Banner\r\n".
            "Content-Type: text/html; charset=UTF-8\r\n".
            "\r\n".
            "<p>Hello world</p>\r\n".
            "------=_Banner--\r\n";

        $parser = new Parser;
        $parser->setText($raw);
        $emailData = new EmailData($parser, $from, strlen($raw));

        return new ForwardEmail($alias, $emailData, $recipient);
    }

    private function mailableProperty(ForwardEmail $mailable, string $name): mixed
    {
        $reflection = new ReflectionObject($mailable);
        $property = $reflection->getProperty($name);
        $property->setAccessible(true);

        return $property->getValue($mailable);
    }
}
