<?php

namespace App\Console\Commands;

use App\Models\Domain;
use App\Notifications\DomainUnverifiedForSending;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckDomainsSendingVerification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'anonaddy:check-domains-sending-verification';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Checks all existing domains to see if they are still verified for sending';

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
        Domain::with(['user.defaultUsername', 'user.defaultRecipient'])
            ->whereNotNull('domain_sending_verified_at')->get()
            ->each(function ($domain) {
                try {
                    $result = $domain->checkVerificationForSending();
                    $data = $result->getData();

                    if (! empty($data->dns_error)) {
                        Log::info('Domain sending verification skipped due to DNS error: '.$domain->domain);

                        return;
                    }

                    if ($data->success === false) {
                        // Notify user via email, give reason
                        $domain->user->notify(new DomainUnverifiedForSending($domain->domain, $data->message));

                        $domain->domain_sending_verified_at = null;
                        $domain->save();
                    }
                } catch (\Exception $e) {
                    //
                }
            });
    }
}
