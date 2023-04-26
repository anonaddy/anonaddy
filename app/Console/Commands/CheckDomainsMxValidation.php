<?php

namespace App\Console\Commands;

use App\Models\Domain;
use App\Notifications\DomainMxRecordsInvalid;
use Illuminate\Console\Command;

class CheckDomainsMxValidation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'anonaddy:check-domains-mx-validation';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Checks all existing domains to see if they still have valid MX records';

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
     * @return int
     */
    public function handle()
    {
        Domain::all()
            ->each(function ($domain) {
                try {
                    if (! $domain->checkMxRecords()) {
                        // Notify user via email only if domain's MX previously were valid
                        if (! is_null($domain->domain_mx_validated_at)) {
                            $domain->user->notify(new DomainMxRecordsInvalid($domain->domain));
                        }

                        $domain->domain_mx_validated_at = null;
                        $domain->save();
                    }
                } catch (\Exception $e) {
                    //
                }
            });
    }
}
