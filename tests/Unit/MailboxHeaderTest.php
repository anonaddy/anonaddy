<?php

namespace Tests\Unit;

use App\Mail\MailboxHeader;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Mime\Address;
use Tests\TestCase;

class MailboxHeaderTest extends TestCase
{
    #[Test]
    public function format_omits_display_when_it_is_the_address_wrapped_in_brackets(): void
    {
        $formatted = MailboxHeader::format(
            '<sry4dnq7@johndoe.anonaddy.com>',
            'sry4dnq7@johndoe.anonaddy.com'
        );

        $this->assertSame('sry4dnq7@johndoe.anonaddy.com', $formatted);
        $this->assertSame('sry4dnq7@johndoe.anonaddy.com', Address::create($formatted)->getAddress());
    }

    #[Test]
    public function format_keeps_a_real_display_name(): void
    {
        $this->assertSame('Harry <non-alias@example.com>', MailboxHeader::format('Harry', 'non-alias@example.com'));
    }

    #[Test]
    public function for_symfony_splits_a_duplicated_bracketed_address(): void
    {
        $addresses = MailboxHeader::forSymfony(
            '<sry4dnq7@johndoe.anonaddy.com> <sry4dnq7@johndoe.anonaddy.com>'
        );

        $this->assertSame(['sry4dnq7@johndoe.anonaddy.com'], $addresses);
        $this->assertSame('sry4dnq7@johndoe.anonaddy.com', Address::create($addresses[0])->getAddress());
    }

    #[Test]
    public function for_symfony_keeps_a_valid_named_mailbox(): void
    {
        $this->assertSame(
            ['Harry <non-alias@example.com>'],
            MailboxHeader::forSymfony('Harry <non-alias@example.com>')
        );
    }

    #[Test]
    public function for_symfony_extracts_the_address_when_a_quoted_display_contains_a_stray_bracket(): void
    {
        $addresses = MailboxHeader::forSymfony(
            '<noreply@abc.example.com>" <noreply@abc.example.com>'
        );

        $this->assertSame(['noreply@abc.example.com'], $addresses);
        $this->assertSame('noreply@abc.example.com', Address::create($addresses[0])->getAddress());
    }
}
