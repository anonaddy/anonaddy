<?php

namespace App\Console\Commands;

use App\AdditionalUsername;
use App\Alias;
use App\Domain;
use App\EmailData;
use App\Mail\ForwardEmail;
use App\Mail\ReplyToEmail;
use App\Notifications\NearBandwidthLimit;
use App\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use PhpMimeMailParser\Parser;

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
    protected $size;

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

            $recipients = $this->getRecipients();

            // Divide the size of the email by the number of recipients (excluding any unsubscribe recipients) to prevent it being added multiple times.
            $recipientCount = $recipients->where('domain', '!=', 'unsubscribe.'.config('anonaddy.domain'))->count();

            $this->size = $this->option('size') / ($recipientCount ? $recipientCount : 1);

            foreach ($recipients as $key => $recipient) {
                $displayTo = $this->parser->getAddresses('to')[$key]['display'] ?? null;

                $parentDomain = collect(config('anonaddy.all_domains'))
                    ->filter(function ($name) use ($recipient) {
                        return Str::endsWith($recipient['domain'], $name);
                    })
                    ->first();

                $subdomain = substr($recipient['domain'], 0, strrpos($recipient['domain'], '.'.$parentDomain));

                if ($subdomain === 'unsubscribe') {
                    $this->handleUnsubscribe($recipient);
                    continue;
                }

                $user = User::where('username', $subdomain)->first();

                if (is_null($user)) {
                    // Check if this is a custom domain.
                    if ($customDomain = Domain::where('domain', $recipient['domain'])->first()) {
                        $user = $customDomain->user;
                    }

                    // Check if this is an additional username.
                    if ($additionalUsername = AdditionalUsername::where('username', $subdomain)->first()) {
                        $user = $additionalUsername->user;
                    }

                    // Check if this is a uuid generated alias.
                    if ($alias = Alias::find($recipient['local_part'])) {
                        $user = $alias->user;
                    } elseif ($recipient['domain'] === $parentDomain && !empty(config('anonaddy.admin_username'))) {
                        $user = User::where('username', config('anonaddy.admin_username'))->first();
                    }
                }

                // If there is still no user or the user has no verified default recipient then continue.
                if (is_null($user) || !$user->hasVerifiedDefaultRecipient()) {
                    continue;
                }

                $this->checkBandwidthLimit($user);

                $this->checkRateLimit($user);

                // Check whether this email is a reply or a new email to be forwarded.
                if ($recipient['extension'] === sha1(config('anonaddy.secret').$displayTo)) {
                    $this->handleReply($user, $recipient, $displayTo);
                } else {
                    $this->handleForward($user, $recipient, $customDomain->id ?? null);
                }
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage() . PHP_EOL . $e->getTraceAsString());

            $this->error('4.3.0 An error has occurred, please try again later.');

            exit(1);
        }
    }

    protected function handleUnsubscribe($recipient)
    {
        $alias = Alias::find($recipient['local_part']);

        if (!is_null($alias) && $alias->user->isVerifiedRecipient($this->option('sender'))) {
            $alias->deactivate();
        }
    }

    protected function handleReply($user, $recipient, $displayTo)
    {
        $alias = $user->aliases()->where('email', $recipient['local_part'] . '@' . $recipient['domain'])->first();

        if (!is_null($alias) && filter_var($displayTo, FILTER_VALIDATE_EMAIL)) {
            $emailData = new EmailData($this->parser);

            $message = (new ReplyToEmail($user, $alias, $emailData));

            Mail::to($displayTo)->queue($message);

            if (!Mail::failures()) {
                $alias->increment('emails_replied');

                $user->bandwidth += $this->size;
                $user->save();
            }
        }
    }

    protected function handleForward($user, $recipient, $customDomainId)
    {
        $alias = $user->aliases()->firstOrNew([
            'email' => $recipient['local_part'] . '@' . $recipient['domain'],
            'local_part' => $recipient['local_part'],
            'domain' => $recipient['domain'],
            'domain_id' => $customDomainId
        ]);

        if (!isset($alias->id)) {
            // This is a new alias.
            if ($user->hasExceededNewAliasLimit()) {
                $this->error('4.2.1 New aliases per hour limit exceeded for user.');

                exit(1);
            }

            if ($recipient['extension'] !== '') {
                $alias->extension = $recipient['extension'];

                $keys = explode('.', $recipient['extension']);

                $recipientIds = $user
                                    ->recipients()
                                    ->oldest()
                                    ->get()
                                    ->filter(function ($item, $key) use ($keys) {
                                        return in_array($key+1, $keys) && ! is_null($item['email_verified_at']);
                                    })
                                    ->pluck('id')
                                    ->take(10)
                                    ->toArray();
            }
        }

        $alias->save();
        $alias->refresh();

        if (isset($recipientIds)) {
            $alias->recipients()->sync($recipientIds);
        }

        $emailData = new EmailData($this->parser);

        $alias->verifiedRecipientsOrDefault()->each(function ($recipient) use ($alias, $emailData) {
            $message = (new ForwardEmail($alias, $emailData, $recipient->should_encrypt ? $recipient->fingerprint : null));

            Mail::to($recipient->email)->queue($message);
        });

        if (!Mail::failures()) {
            $alias->increment('emails_forwarded');

            $user->bandwidth += $this->size;
            $user->save();
        }
    }

    protected function checkBandwidthLimit($user)
    {
        if ($user->hasReachedBandwidthLimit()) {
            $this->error('5.2.1 Bandwidth limit exceeded for user. Please try again later.');

            exit(1);
        }

        if ($user->nearBandwidthLimit()) {
            $user->notify(new NearBandwidthLimit());
        }
    }

    protected function checkRateLimit($user)
    {
        Redis::throttle("user:{$user->username}:limit:emails")
            ->allow(config('anonaddy.limit'))
            ->every(3600)
            ->then(
                function () {
                },
                function () {
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
                'local_part' => $this->option('local_part')[$key],
                'extension' => $this->option('extension')[$key],
                'domain' => $this->option('domain')[$key]
            ];
        });
    }

    protected function getParser($file)
    {
        $parser = new Parser;

        if ($file == 'stream') {
            $fd = fopen('php://stdin', 'r');
            $this->rawEmail = '';
            while (!feof($fd)) {
                $this->rawEmail .= fread($fd, 1024);
            }
            fclose($fd);
            $parser->setText($this->rawEmail);
        } else {
            $parser->setPath($file);
        }
        return $parser;
    }

    protected function exitIfFromSelf()
    {
        // To prevent recipient alias infinite nested looping.
        if (in_array($this->option('sender'), [config('mail.from.address'), config('anonaddy.return_path')])) {
            exit(0);
        }
    }
}
