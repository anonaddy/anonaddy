<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Failed;
use Illuminate\Support\Facades\Log;

class LogFailedLoginAttempt
{
    /**
     * Handle the event.
     */
    public function handle(Failed $event): void
    {
        Log::warning('Authentication failed', [
            'source_ip' => request()->ip(),
        ]);
    }
}
