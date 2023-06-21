<?php

namespace App\Console\Commands;

use App\Helpers\GitVersionHelper;
use Illuminate\Console\Command;

class UpdateAppVersion extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'anonaddy:update-app-version';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updates the cached app version';

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
        $version = GitVersionHelper::cacheFreshVersion();
        $this->info("AnonAddy version: {$version}");

        return 0;
    }
}
