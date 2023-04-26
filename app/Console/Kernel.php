<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        'App\Console\Commands\ResetBandwidth',
    ];

    /**
     * Define the application's command schedule.
     *
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('anonaddy:reset-bandwidth')->monthlyOn(1, '00:00');
        $schedule->command('anonaddy:check-domains-sending-verification')->daily();
        $schedule->command('anonaddy:check-domains-mx-validation')->daily();
        $schedule->command('anonaddy:clear-failed-deliveries')->daily();
        $schedule->command('anonaddy:clear-postfix-queue-ids')->hourly();
        $schedule->command('auth:clear-resets')->daily();
        $schedule->command('cache:prune-stale-tags')->hourly();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
