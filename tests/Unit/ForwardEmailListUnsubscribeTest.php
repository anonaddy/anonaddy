<?php

namespace Tests\Unit;

use App\Enums\ListUnsubscribeBehaviour;
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

class ForwardEmailListUnsubscribeTest extends TestCase
{
    use LazilyRefreshDatabase;

    #[Test]
    public function original_header_is_rewritten_when_no_fallback_is_selected(): void
    {
        $message = $this->buildForwardedSymfonyMessage(
            ListUnsubscribeBehaviour::OriginalWithNoFallback,
            includeListUnsubscribe: true,
            includeListUnsubscribePost: true,
        );

        $domain = config('anonaddy.domain');

        $this->assertSame(
            '<mailto:ebay+unsubscribe=example.com@johndoe.'.$domain.'>',
            $message->getHeaders()->get('List-Unsubscribe')?->getBodyAsString()
        );
        $this->assertSame(
            'List-Unsubscribe=One-Click',
            $message->getHeaders()->get('List-Unsubscribe-Post')?->getBodyAsString()
        );
    }

    #[Test]
    public function no_list_unsubscribe_header_is_added_when_the_original_email_has_none(): void
    {
        $message = $this->buildForwardedSymfonyMessage(
            ListUnsubscribeBehaviour::OriginalWithNoFallback,
            includeListUnsubscribe: false,
        );

        $this->assertFalse($message->getHeaders()->has('List-Unsubscribe'));
        $this->assertFalse($message->getHeaders()->has('List-Unsubscribe-Post'));
    }

    #[Test]
    public function missing_original_header_still_falls_back_to_one_click_deactivate(): void
    {
        $message = $this->buildForwardedSymfonyMessage(
            ListUnsubscribeBehaviour::OriginalWithFallback,
            includeListUnsubscribe: false,
        );

        $listUnsubscribe = $message->getHeaders()->get('List-Unsubscribe')?->getBodyAsString();

        $this->assertIsString($listUnsubscribe);
        $this->assertStringContainsString('/deactivate-one-click/', $listUnsubscribe);
        $this->assertSame(
            'List-Unsubscribe=One-Click',
            $message->getHeaders()->get('List-Unsubscribe-Post')?->getBodyAsString()
        );
    }

    private function buildForwardedSymfonyMessage(
        ListUnsubscribeBehaviour $behaviour,
        bool $includeListUnsubscribe,
        bool $includeListUnsubscribePost = false,
    ): Email {
        $user = $this->createUser('johndoe');
        $user->update([
            'list_unsubscribe_behaviour' => $behaviour,
        ]);

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
        if ($includeListUnsubscribe) {
            if ($includeListUnsubscribePost) {
                $raw = preg_replace(
                    '/^List-Unsubscribe:.*$/m',
                    "List-Unsubscribe: <mailto:unsubscribe@example.com>\nList-Unsubscribe-Post: List-Unsubscribe=One-Click",
                    $raw,
                    1
                );
            }
        } else {
            $raw = preg_replace('/^List-Unsubscribe:.*\r?\n/m', '', $raw);
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
