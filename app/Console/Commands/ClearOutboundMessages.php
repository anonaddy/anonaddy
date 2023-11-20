<?php

namespace App\Console\Commands;

use App\Models\OutboundMessage;
use Illuminate\Console\Command;

class ClearOutboundMessages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'anonaddy:clear-outbound-messages';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clears outbound messages that are older than 7 days';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        OutboundMessage::where('created_at', '<', now()->subDays(7))->delete();

        return Command::SUCCESS;
    }
}
