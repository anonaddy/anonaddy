<?php

namespace App\CustomMailDriver;

use Illuminate\Mail\MailManager;

class CustomMailManager extends MailManager
{
    /**
     * Create an instance of the Sendmail Swift Transport driver.
     *
     * @param  array  $config
     * @return \Swift_SendmailTransport
     */
    protected function createSendmailTransport(array $config)
    {
        return new CustomSendmailTransport(
            $config['path'] ?? $this->app['config']->get('mail.sendmail')
        );
    }
}
