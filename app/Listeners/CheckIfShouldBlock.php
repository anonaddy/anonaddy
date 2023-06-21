<?php

namespace App\Listeners;

use Illuminate\Mail\Events\MessageSending;

class CheckIfShouldBlock
{
    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(MessageSending $event)
    {
        if (isset($event->data['shouldBlock'])) {
            if ($event->data['shouldBlock']) {
                return false;
            }
        }
    }
}
