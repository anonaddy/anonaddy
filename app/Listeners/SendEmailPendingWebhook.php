<?php
// Will be triggered if email is being queued
// TODO: decide the schema

namespace App\Listeners;

use Illuminate\Mail\Events\MessageSending;

class SendEmailPendingWebhook
{
    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(MessageSending $event)
    {
        // TODO: implement
    }
}
