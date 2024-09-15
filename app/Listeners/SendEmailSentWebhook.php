<?php
// Will be triggered if email is successfully sent
// TODO: decide the schema

namespace App\Listeners;

use Illuminate\Mail\Events\MessageSent;

class SendEmailSentWebhook
{
    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(MessageSent $event)
    {
        // TODO: implement
    }
}
