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
use Symfony\Component\Mime\Address;
use Tests\TestCase;

class ForwardEmailToHeaderTest extends TestCase
{
    use LazilyRefreshDatabase;

    #[Test]
    public function quoted_bracket_to_header_formats_a_valid_alias_mailbox(): void
    {
        $user = $this->createUser('johndoe');

        $recipient = Recipient::factory()->create([
            'user_id' => $user->id,
            'email' => 'john@example.com',
        ]);

        $domain = config('anonaddy.domain');
        $aliasEmail = 'ebay@johndoe.'.$domain;
        $alias = Alias::factory()->create([
            'user_id' => $user->id,
            'email' => $aliasEmail,
            'local_part' => 'ebay',
            'domain' => 'johndoe.'.$domain,
            'aliasable_id' => $user->default_username_id,
            'aliasable_type' => Username::class,
        ]);

        $raw = file_get_contents(base_path('tests/emails/email.eml'));
        $raw = preg_replace(
            '/^To:.*$/m',
            'To: "<'.$aliasEmail.'>" <'.$aliasEmail.'>',
            $raw
        );

        $parser = new Parser;
        $parser->setText($raw);
        $emailData = new EmailData($parser, 'will@anonaddy.com', 1000);

        $mailable = new ForwardEmail($alias, $emailData, $recipient);

        $tos = (new ReflectionObject($mailable))->getProperty('tos');
        $tos->setAccessible(true);
        $formattedTos = $tos->getValue($mailable);

        $this->assertSame([$aliasEmail], $formattedTos);
        $this->assertSame($aliasEmail, Address::create($formattedTos[0])->getAddress());
    }
}
