<?php

namespace App\Listeners;

use App\Notifications\IncorrectOtpNotification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SendIncorrectOtpNotification
{
    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle($event)
    {
        if (! $user = $event->user) {
            return;
        }

        if (! Cache::has("user:{$user->id}:failed-otp-notification")) {
            // Add key to cache
            Cache::put("user:{$user->id}:failed-otp-notification", now()->toDateTimeString(), now()->addMinutes(5));

            // Log in auth.log
            Log::channel('auth')->info('Failed OTP Notification sent: '.$user->username);

            $user->notify(new IncorrectOtpNotification());
        }
    }
}
