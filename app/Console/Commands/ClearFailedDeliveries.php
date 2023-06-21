<?php

namespace App\Console\Commands;

use App\Models\FailedDelivery;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ClearFailedDeliveries extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'anonaddy:clear-failed-deliveries';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clears failed deliveries that are older than 3 days';

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
        FailedDelivery::where('created_at', '<=', now()->subDays(7))->delete();

        // Delete any stored failed deliveries older than 7 days
        collect(Storage::disk('local')->listContents(''))
            ->each(function ($file) {
                if ($file['type'] == 'file' && $file['lastModified'] < now()->subDays(7)->getTimestamp()) {
                    Storage::disk('local')->delete($file['path']);
                }
            });
    }
}
