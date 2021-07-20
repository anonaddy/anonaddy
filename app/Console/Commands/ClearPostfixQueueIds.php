<?php

namespace App\Console\Commands;

use App\Models\PostfixQueueId;
use Illuminate\Console\Command;

class ClearPostfixQueueIds extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'anonaddy:clear-postfix-queue-ids';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clears postfix queue ids that are older than 7 days';

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
        PostfixQueueId::where('created_at', '<=', now()->subDays(7))->delete();
    }
}
