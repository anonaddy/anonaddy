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
use Symfony\Component\Mime\Email;
use Tests\TestCase;

class ForwardEmailMessageIdTest extends TestCase
{
    use LazilyRefreshDatabase;

    #[Test]
    public function outlook_message_id_with_two_at_signs_does_not_throw_and_sets_a_message_id(): void
    {
        $outlookMessageId = 'abcda$b3227590$196760b0$@Prajapati@example.com';

        $message = $this->buildForwardedSymfonyMessage($outlookMessageId);

        $messageIdHeader = $message->getHeaders()->get('Message-ID');

        $this->assertNotNull($messageIdHeader);
        $this->assertNotSame('', $messageIdHeader->getBodyAsString());
    }

    #[Test]
    public function valid_original_message_id_is_preserved(): void
    {
        $validMessageId = 'unique123@mail.example.com';

        $message = $this->buildForwardedSymfonyMessage($validMessageId);

        $this->assertSame('<'.$validMessageId.'>', $message->getHeaders()->get('Message-ID')?->getBodyAsString());
    }

    #[Test]
    public function missing_message_id_gets_a_generated_id(): void
    {
        $message = $this->buildForwardedSymfonyMessage(null);

        $messageIdHeader = $message->getHeaders()->get('Message-ID');

        $this->assertNotNull($messageIdHeader);
        $this->assertStringEndsWith('@johndoe.'.config('anonaddy.domain').'>', $messageIdHeader->getBodyAsString());
    }

    private function buildForwardedSymfonyMessage(?string $messageId): Email
    {
        $user = $this->createUser('johndoe');

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

        $raw = file_get_contents(base_path('tests/emails/email.eml'));
        if ($messageId === null) {
            $raw = preg_replace('/^Message-ID:.*\r?\n/m', '', $raw);
        } else {
            $raw = preg_replace_callback(
                '/^Message-ID:.*$/m',
                fn () => 'Message-ID: <'.$messageId.'>',
                $raw
            );
        }

        $parser = new Parser;
        $parser->setText($raw);
        $emailData = new EmailData($parser, 'will@anonaddy.com', 1000);

        $mailable = new ForwardEmail($alias, $emailData, $recipient);
        $mailable->build();

        $message = new Email;
        foreach ($mailable->callbacks as $callback) {
            $callback($message);
        }

        return $message;
    }
}
