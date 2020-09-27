<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class ResetBandwidth extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'anonaddy:reset-bandwidth';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset bandwidth for all users at the start of each month';

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
        User::where('bandwidth', '>', 0)->update(['bandwidth' => 0]);
    }
}
