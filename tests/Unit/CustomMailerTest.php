<?php

namespace Tests\Unit;

use App\CustomMailDriver\CustomMailer;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use ReflectionProperty;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\RawMessage;
use Tests\TestCase;

class CustomMailerTest extends TestCase
{
    #[Test]
    public function send_symfony_message_normalises_a_duplicated_bracketed_to_address(): void
    {
        $transport = new class implements TransportInterface
        {
            public ?RawMessage $sent = null;

            public function send(RawMessage $message, ?Envelope $envelope = null): ?SentMessage
            {
                $this->sent = $message;

                return null;
            }

            public function __toString(): string
            {
                return 'smtp://fake';
            }
        };

        $mailer = new CustomMailer('smtp', $this->app['view'], $transport, $this->app['events']);

        $data = new ReflectionProperty(CustomMailer::class, 'data');
        $data->setValue($mailer, [
            'tos' => ['<sry4dnq7@johndoe.anonaddy.com> <sry4dnq7@johndoe.anonaddy.com>'],
        ]);

        $sendSymfonyMessage = new ReflectionMethod(CustomMailer::class, 'sendSymfonyMessage');
        $sendSymfonyMessage->invoke($mailer, (new Email)
            ->from('sender@example.com')
            ->to('recipient@example.com')
            ->text('Test'));

        $this->assertInstanceOf(Email::class, $transport->sent);
        $this->assertSame(
            ['sry4dnq7@johndoe.anonaddy.com'],
            array_map(fn ($address) => $address->getAddress(), $transport->sent->getTo())
        );
    }

    #[Test]
    public function send_symfony_message_normalises_a_malformed_original_sender_header(): void
    {
        $transport = new class implements TransportInterface
        {
            public ?RawMessage $sent = null;

            public function send(RawMessage $message, ?Envelope $envelope = null): ?SentMessage
            {
                $this->sent = $message;

                return null;
            }

            public function __toString(): string
            {
                return 'smtp://fake';
            }
        };

        $mailer = new CustomMailer('smtp', $this->app['view'], $transport, $this->app['events']);

        $data = new ReflectionProperty(CustomMailer::class, 'data');
        $data->setValue($mailer, []);

        $message = (new Email)
            ->from('sender@example.com')
            ->to('recipient@example.com')
            ->text('Test');
        $message->getHeaders()->addTextHeader(
            'Original-Sender',
            '<noreply@abc.example.com>" <noreply@abc.example.com>'
        );

        $sendSymfonyMessage = new ReflectionMethod(CustomMailer::class, 'sendSymfonyMessage');
        $sendSymfonyMessage->invoke($mailer, $message);

        $this->assertInstanceOf(Email::class, $transport->sent);
        $this->assertNull($transport->sent->getHeaders()->get('Original-Sender'));
        $this->assertSame('noreply@abc.example.com', $transport->sent->getSender()?->getAddress());
    }
}
