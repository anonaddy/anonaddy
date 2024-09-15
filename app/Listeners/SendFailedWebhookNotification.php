<?php
// Will be triggered webhook failed to be sent and retry > max retry attempt
// TODO: decide the schema

namespace App\Listeners;

use Illuminate\Mail\Events\MessageSending;

class SendFailedWebhookNotification
{
    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(FinalWebhookCallFailedEvent $event)
    {
        // TODO: implement
    }
}
