<?php

namespace App\Console\Commands;

use App\Mail\ForwardEmail;
use App\Mail\ReplyToEmail;
use App\Mail\SendFromEmail;
use App\Models\Alias;
use App\Models\Domain;
use App\Models\EmailData;
use App\Models\OutboundMessage;
use App\Models\Username;
use App\Notifications\DisallowedReplySendAttempt;
use App\Notifications\FailedDeliveryNotification;
use App\Notifications\NearBandwidthLimit;
use App\Notifications\SpamReplySendAttempt;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ParagonIE\ConstantTime\Base32;
use PhpMimeMailParser\Parser;
use Ramsey\Uuid\Uuid;

class ReceiveEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'anonaddy:receive-email
                            {file=stream : The file of the email}
                            {--sender= : The sender of the email}
                            {--recipient=* : The recipient of the email}
                            {--local_part=* : The local part of the recipient}
                            {--extension=* : The extension of the local part of the recipient}
                            {--domain=* : The domain of the recipient}
                            {--size= : The size of the email in bytes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Receive email from postfix pipe';

    protected $parser;

    protected $senderFrom;

    protected $size;

    protected $rawEmail;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        try {
            $this->exitIfFromSelf();

            $file = $this->argument('file');

            $this->parser = $this->getParser($file);
            $this->senderFrom = $this->getSenderFrom();

            $recipients = $this->getRecipients();

            // Divide the size of the email by the number of recipients (excluding any unsubscribe recipients) to prevent it being added multiple times.
            $recipientCount = $recipients->where('domain', '!=', 'unsubscribe.'.config('anonaddy.domain'))->count();

            $this->size = $this->option('size') / ($recipientCount ? $recipientCount : 1);

            foreach ($recipients as $recipient) {
                // Check if VERP bounce
                if (substr($recipient['email'], 0, 2) === 'b_') {
                    if ($outboundMessageId = $this->getIdFromVerp($recipient['email'])) {
                        // Is a valid bounce
                        $outboundMessage = OutboundMessage::with(['user', 'alias', 'recipient'])->find($outboundMessageId);

                        if (is_null($outboundMessage)) {
                            // Must have been more than 7 days
                            Log::info('VERP outboundMessage not found');

                            exit(0);
                        }

                        $bouncedAlias = $outboundMessage->alias;

                        // If already bounced then forward to the user instead
                        if (! $outboundMessage->bounced) {
                            $this->handleBounce($outboundMessage);
                        }

                        if (in_array(strtolower($this->parser->getHeader('Auto-Submitted')), ['auto-replied', 'auto-generated']) && ! in_array($outboundMessage->email_type, ['R', 'S'])) {
                            Log::info('VERP auto-response to forward/notification, username: '.$outboundMessage->user?->username.' outboundMessageID: '.$outboundMessageId);

                            exit(0);
                        }

                        // If it is a notification then there is no alias so exit and log, may be an auto-reply to a notification.
                        if (is_null($bouncedAlias)) {
                            Log::info('VERP previously bounced/auto-response to notification, username: '.$outboundMessage->user?->username.' outboundMessageID: '.$outboundMessageId);

                            exit(0);
                        }

                        // If it is not a bounce (could be auto-reply) then redirect to alias
                        $recipient['email'] = $bouncedAlias->email;
                        $recipient['local_part'] = $bouncedAlias->local_part;
                        $recipient['domain'] = $bouncedAlias->domain;
                    }
                }

                // First determine if the alias already exists in the database
                if ($alias = Alias::firstWhere('email', $recipient['local_part'].'@'.$recipient['domain'])) {
                    $user = $alias->user;

                    if ($alias->aliasable_id) {
                        $aliasable = $alias->aliasable;
                    }
                } else {
                    // Does not exist, must be a standard, username or custom domain alias
                    $parentDomain = collect(config('anonaddy.all_domains'))
                        ->filter(function ($name) use ($recipient) {
                            return Str::endsWith($recipient['domain'], $name);
                        })
                        ->first();

                    if (! empty($parentDomain)) {
                        // It is standard or username alias
                        $subdomain = substr($recipient['domain'], 0, strrpos($recipient['domain'], '.'.$parentDomain)); // e.g. johndoe

                        if ($subdomain === 'unsubscribe') {
                            $this->handleUnsubscribe($recipient);

                            continue;
                        }

                        // Check if this is an username or standard alias
                        if (! empty($subdomain)) {
                            $username = Username::where('username', $subdomain)->first();
                            $user = $username->user;
                            $aliasable = $username;
                        }
                    } else {
                        // It is a custom domain
                        if ($customDomain = Domain::where('domain', $recipient['domain'])->first()) {
                            $user = $customDomain->user;
                            $aliasable = $customDomain;
                        }
                    }

                    if (! isset($user) && ! empty(config('anonaddy.admin_username'))) {
                        $user = Username::where('username', config('anonaddy.admin_username'))->first()?->user;
                    }
                }

                // If there is still no user or the user has no verified default recipient then continue.
                if (! isset($user) || ! $user->hasVerifiedDefaultRecipient()) {
                    continue;
                }

                $this->checkBandwidthLimit($user);

                $this->checkRateLimit($user);

                // Check whether this email is a reply/send from or a new email to be forwarded.
                $destination = Str::replaceLast('=', '@', $recipient['extension']);
                $validEmailDestination = filter_var($destination, FILTER_VALIDATE_EMAIL);
                if ($validEmailDestination) {
                    $verifiedRecipient = $user->getVerifiedRecipientByEmail($this->senderFrom);
                } else {
                    $verifiedRecipient = null;
                }

                if ($verifiedRecipient?->can_reply_send) {
                    // Check if the Dmarc allow or spam headers are present from Rspamd
                    if (! $this->parser->getHeader('X-AnonAddy-Dmarc-Allow')) {
                        // Notify user and exit
                        $verifiedRecipient->notify(new SpamReplySendAttempt($recipient, $this->senderFrom, $this->parser->getHeader('X-AnonAddy-Authentication-Results')));

                        exit(0);
                    }

                    if ($this->parser->getHeader('In-Reply-To') && $alias) {
                        $this->handleReply($user, $recipient, $alias);
                    } else {
                        $this->handleSendFrom($user, $recipient, $alias ?? null, $aliasable ?? null);
                    }
                } elseif ($verifiedRecipient?->can_reply_send === false) {
                    // Notify user that they have not allowed this recipient to reply and send from aliases
                    $verifiedRecipient->notify(new DisallowedReplySendAttempt($recipient, $this->senderFrom, $this->parser->getHeader('X-AnonAddy-Authentication-Results')));

                    exit(0);
                } else {
                    // Check if the spam header is present from Rspamd
                    $this->handleForward($user, $recipient, $alias ?? null, $aliasable ?? null, $this->parser->getHeader('X-AnonAddy-Spam') === 'Yes');
                }
            }
        } catch (\Exception $e) {
            report($e);

            $this->error('4.3.0 An error has occurred, please try again later.');

            exit(1);
        }
    }

    protected function handleUnsubscribe($recipient)
    {
        $alias = Alias::find($recipient['local_part']);

        if ($alias && $alias->user->isVerifiedRecipient($this->senderFrom) && $this->parser->getHeader('X-AnonAddy-Dmarc-Allow')) {
            $alias->deactivate();
        }
    }

    protected function handleReply($user, $recipient, $alias)
    {
        $sendTo = Str::replaceLast('=', '@', $recipient['extension']);

        $emailData = new EmailData($this->parser, $this->option('sender'), $this->size);

        $message = new ReplyToEmail($user, $alias, $emailData);

        Mail::to($sendTo)->queue($message);
    }

    protected function handleSendFrom($user, $recipient, $alias, $aliasable)
    {
        if (is_null($alias)) {
            $alias = $user->aliases()->create([
                'email' => $recipient['local_part'].'@'.$recipient['domain'],
                'local_part' => $recipient['local_part'],
                'domain' => $recipient['domain'],
                'aliasable_id' => $aliasable?->id,
                'aliasable_type' => $aliasable ? 'App\\Models\\'.class_basename($aliasable) : null,
            ]);

            // Hydrate all alias fields
            $alias->refresh();
        }

        $sendTo = Str::replaceLast('=', '@', $recipient['extension']);

        $emailData = new EmailData($this->parser, $this->option('sender'), $this->size);

        $message = new SendFromEmail($user, $alias, $emailData);

        Mail::to($sendTo)->queue($message);
    }

    protected function handleForward($user, $recipient, $alias, $aliasable, $isSpam)
    {
        if (is_null($alias)) {
            // This is a new alias
            $alias = new Alias([
                'email' => $recipient['local_part'].'@'.$recipient['domain'],
                'local_part' => $recipient['local_part'],
                'domain' => $recipient['domain'],
                'aliasable_id' => $aliasable?->id,
                'aliasable_type' => $aliasable ? 'App\\Models\\'.class_basename($aliasable) : null,
            ]);

            if ($user->hasExceededNewAliasLimit()) {
                $this->error('4.2.1 New aliases per hour limit exceeded for user.');

                exit(1);
            }

            if ($recipient['extension'] !== '') {
                $alias->extension = $recipient['extension'];

                $keys = explode('.', $recipient['extension']);

                $recipientIds = $user
                    ->recipients()
                    ->select(['id', 'email_verified_at'])
                    ->oldest()
                    ->get()
                    ->filter(function ($item, $key) use ($keys) {
                        return in_array($key + 1, $keys) && ! is_null($item['email_verified_at']);
                    })
                    ->pluck('id')
                    ->take(10)
                    ->toArray();
            }

            $user->aliases()->save($alias);

            // Hydrate all alias fields
            $alias->refresh();

            if (isset($recipientIds)) {
                $alias->recipients()->sync($recipientIds);
            }
        }

        $emailData = new EmailData($this->parser, $this->option('sender'), $this->size);

        $alias->verifiedRecipientsOrDefault()->each(function ($recipient) use ($alias, $emailData, $isSpam) {
            $message = (new ForwardEmail($alias, $emailData, $recipient, $isSpam));

            Mail::to($recipient->email)->queue($message);
        });
    }

    protected function handleBounce($outboundMessage)
    {
        // Collect the attachments
        $attachments = collect($this->parser->getAttachments());

        // Find the delivery report
        $deliveryReport = $attachments->filter(function ($attachment) {
            return $attachment->getContentType() === 'message/delivery-status';
        })->first();

        // Is not a bounce, may be an auto-reply so return
        if (! $deliveryReport) {
            return;
        }

        // Mark the outboundMessage as bounced
        $outboundMessage->markAsBounced();

        $dsn = $this->parseDeliveryStatus($deliveryReport->getMimePartStr());

        // Get the bounced email address
        $bouncedEmailAddress = isset($dsn['Final-recipient']) ? trim(Str::after($dsn['Final-recipient'], ';')) : null;

        $remoteMta = isset($dsn['Remote-mta']) ? trim(Str::after($dsn['Remote-mta'], ';')) : '';

        if (isset($dsn['Diagnostic-code']) && isset($dsn['Status'])) {
            // Try to determine the bounce type, HARD, SPAM, SOFT
            $bounceType = $this->getBounceType($dsn['Diagnostic-code'], $dsn['Status']);

            $diagnosticCode = Str::limit($dsn['Diagnostic-code'], 497);
        } else {
            $bounceType = null;
            $diagnosticCode = null;
        }

        // Get the undelivered message
        $undeliveredMessage = $attachments->filter(function ($attachment) {
            return in_array($attachment->getContentType(), ['text/rfc822-headers', 'message/rfc822']);
        })->first();

        $undeliveredMessageHeaders = [];

        if ($undeliveredMessage) {
            $undeliveredMessageHeaders = $this->parseDeliveryStatus($undeliveredMessage->getMimePartStr());
        }

        // Get bounce user information
        $user = $outboundMessage->user;
        $alias = $outboundMessage->alias;
        $recipient = $outboundMessage->recipient;
        $emailType = $outboundMessage->getRawOriginal('email_type');

        if ($user) {
            $failedDeliveryId = Uuid::uuid4();

            if ($undeliveredMessage) {
                // Store the undelivered message if enabled by user. Do not store email verification notifications.
                if ($user->store_failed_deliveries && ! in_array($emailType, ['VR', 'VU'])) {
                    $isStored = Storage::disk('local')->put("{$failedDeliveryId}.eml", $this->trimUndeliveredMessage($undeliveredMessage->getMimePartStr()));
                }
            }

            $failedDelivery = $user->failedDeliveries()->create([
                'id' => $failedDeliveryId,
                'recipient_id' => $recipient->id ?? null,
                'alias_id' => $alias->id ?? null,
                'is_stored' => $isStored ?? false,
                'bounce_type' => $bounceType,
                'remote_mta' => $remoteMta ?? null,
                'sender' => $undeliveredMessageHeaders['X-anonaddy-original-sender'] ?? null,
                'destination' => $bouncedEmailAddress,
                'email_type' => $emailType,
                'status' => $dsn['Status'] ?? null,
                'code' => $diagnosticCode,
                'attempted_at' => $outboundMessage->created_at,
            ]);

            // Check the aliases failed deliveries
            if ($alias) {
                // Decrement the alias forward count due to failed delivery
                if ($failedDelivery->getRawOriginal('email_type') === 'F' && $alias->emails_forwarded > 0) {
                    $alias->decrement('emails_forwarded');
                }

                if ($failedDelivery->getRawOriginal('email_type') === 'R' && $alias->emails_replied > 0) {
                    $alias->decrement('emails_replied');
                }

                if ($failedDelivery->getRawOriginal('email_type') === 'S' && $alias->emails_sent > 0) {
                    $alias->decrement('emails_sent');
                }
            }
        } else {
            Log::info('User not found from outbound message, may have been deleted.');
        }

        // Check if the bounce is a Failed delivery notification and if so do not notify the user again
        if (! in_array($emailType, ['FDN'])) {

            $notifiable = $recipient?->email_verified_at ? $recipient : $user?->defaultRecipient;

            // Notify user of failed delivery
            if ($notifiable?->email_verified_at) {

                $notifiable->notify(new FailedDeliveryNotification($alias->email ?? null, $undeliveredMessageHeaders['X-anonaddy-original-sender'] ?? null, $undeliveredMessageHeaders['Subject'] ?? null, $failedDelivery?->is_stored, $user?->store_failed_deliveries, $recipient?->email));

                Log::info('FDN '.$emailType.': '.$notifiable->email);
            }
        }

        exit(0);
    }

    protected function checkBandwidthLimit($user)
    {
        if ($user->hasReachedBandwidthLimit()) {
            $user->update(['reject_until' => now()->endOfMonth()]);

            $this->error('4.2.1 Bandwidth limit exceeded for user. Please try again later.');

            exit(1);
        }

        if ($user->nearBandwidthLimit() && ! Cache::has("user:{$user->id}:near-bandwidth")) {
            $user->notify(new NearBandwidthLimit());

            Cache::put("user:{$user->id}:near-bandwidth", now()->toDateTimeString(), now()->addDay());
        }
    }

    protected function checkRateLimit($user)
    {
        \Illuminate\Support\Facades\Redis::throttle("user:{$user->id}:limit:emails")
            ->allow(config('anonaddy.limit'))
            ->every(3600)
            ->then(
                function () {
                },
                function () use ($user) {
                    $user->update(['defer_until' => now()->addHour()]);

                    $this->error('4.2.1 Rate limit exceeded for user. Please try again later.');

                    exit(1);
                }
            );
    }

    protected function getRecipients()
    {
        return collect($this->option('recipient'))->map(function ($item, $key) {
            return [
                'email' => $item,
                'local_part' => strtolower($this->option('local_part')[$key]),
                'extension' => $this->option('extension')[$key],
                'domain' => strtolower($this->option('domain')[$key]),
            ];
        });
    }

    protected function getParser($file)
    {
        $parser = new Parser();

        // Fix some edge cases in from name e.g. "\" John Doe \"" <johndoe@example.com>
        $parser->addMiddleware(function ($mimePart, $next) {
            $part = $mimePart->getPart();

            if (isset($part['headers']['from'])) {
                $value = $part['headers']['from'];
                $value = (is_array($value)) ? $value[0] : $value;

                try {
                    mailparse_rfc822_parse_addresses($value);
                } catch (\Exception $e) {
                    $part['headers']['from'] = str_replace('\\', '', $part['headers']['from']);
                    $mimePart->setPart($part);
                }
            }

            return $next($mimePart);
        });

        if ($file == 'stream') {
            $fd = fopen('php://stdin', 'r');
            $this->rawEmail = '';
            while (! feof($fd)) {
                $this->rawEmail .= fread($fd, 1024);
            }
            fclose($fd);
            $parser->setText($this->rawEmail);
        } else {
            $parser->setPath($file);
        }

        return $parser;
    }

    protected function parseDeliveryStatus($deliveryStatus)
    {
        $lines = explode(PHP_EOL, $deliveryStatus);

        $result = [];

        foreach ($lines as $line) {
            if (preg_match('#^([^\s.]*):\s*(.*)\s*#', $line, $matches)) {
                $key = ucfirst(strtolower($matches[1]));

                if (empty($result[$key])) {
                    $result[$key] = trim($matches[2]);
                }
            } elseif (preg_match('/^\s+(.+)\s*/', $line) && isset($key)) {
                $result[$key] .= ' '.$line;
            }
        }

        return $result;
    }

    protected function trimUndeliveredMessage($message)
    {
        return Str::after($message, 'Content-Type: message/rfc822'.PHP_EOL.PHP_EOL);
    }

    protected function getBounceType($code, $status)
    {
        if (preg_match("/(:?mailbox|address|user|account|recipient|@).*(:?rejected|unknown|disabled|unavailable|invalid|inactive|not exist|does(n't| not) exist)|(:?rejected|unknown|unavailable|no|illegal|invalid|no such).*(:?mailbox|address|user|account|recipient|alias)|(:?address|user|recipient) does(n't| not) have .*(:?mailbox|account)|returned to sender|(:?auth).*(:?required)/i", $code)) {
            return 'hard';
        }

        if (preg_match('/(:?spam|unsolicited|blacklisting|blacklisted|blacklist|554|mail content denied|reject for policy reason|mail rejected by destination domain|security issue)/i', $code)) {
            return 'spam';
        }

        // No match for code but status starts with 5 e.g. 5.2.2
        if (Str::startsWith($status, '5')) {
            return 'hard';
        }

        return 'soft';
    }

    protected function getSenderFrom()
    {
        try {
            // Ensure contains '@', may be malformed header which causes sends/replies to fail
            $address = $this->parser->getAddresses('from')[0]['address'];

            return Str::contains($address, '@') ? $address : $this->option('sender');
        } catch (\Exception $e) {
            return $this->option('sender');
        }
    }

    protected function getIdFromVerp($verp)
    {
        $localPart = Str::beforeLast($verp, '@');

        $parts = explode('_', $localPart);

        if (count($parts) !== 3) {
            Log::channel('single')->info('VERP invalid email: '.$verp);

            return;
        }

        try {
            $id = Base32::decodeNoPadding($parts[1]);

            $signature = Base32::decodeNoPadding($parts[2]);
        } catch (\Exception $e) {
            Log::channel('single')->info('VERP base32 decode failure: '.$verp.' '.$e->getMessage());

            return;
        }

        $expectedSignature = substr(hash_hmac('sha3-224', $id, config('anonaddy.secret')), 0, 8);

        if ($signature !== $expectedSignature) {
            Log::channel('single')->info('VERP invalid signature: '.$verp);

            return;
        }

        return $id;
    }

    protected function exitIfFromSelf()
    {
        // To prevent recipient alias infinite nested looping.
        if (in_array($this->option('sender'), [config('mail.from.address'), config('anonaddy.return_path')])) {
            exit(0);
        }
    }
}
