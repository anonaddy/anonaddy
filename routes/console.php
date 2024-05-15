<?php

use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

Schedule::command('anonaddy:reset-bandwidth')->monthlyOn(1, '00:00');
Schedule::command('anonaddy:check-domains-sending-verification')->daily();
Schedule::command('anonaddy:check-domains-mx-validation')->daily();
Schedule::command('anonaddy:clear-failed-deliveries')->daily();
Schedule::command('anonaddy:clear-outbound-messages')->everySixHours();
Schedule::command('anonaddy:email-users-with-token-expiring-soon')->daily();
Schedule::command('auth:clear-resets')->daily();
Schedule::command('sanctum:prune-expired --hours=168')->daily();
Schedule::command('cache:prune-stale-tags')->hourly();
