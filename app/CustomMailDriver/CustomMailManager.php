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

    /**
     * Create an instance of the SMTP Swift Transport driver.
     *
     * @param  array  $config
     * @return \Swift_SmtpTransport
     */
    protected function createSmtpTransport(array $config)
    {
        // The Swift SMTP transport instance will allow us to use any SMTP backend
        // for delivering mail such as Sendgrid, Amazon SES, or a custom server
        // a developer has available. We will just pass this configured host.
        $transport = new CustomSmtpTransport(
            $config['host'],
            $config['port']
        );

        if (! empty($config['encryption'])) {
            $transport->setEncryption($config['encryption']);
        }

        // Once we have the transport we will check for the presence of a username
        // and password. If we have it we will set the credentials on the Swift
        // transporter instance so that we'll properly authenticate delivery.
        if (isset($config['username'])) {
            $transport->setUsername($config['username']);

            $transport->setPassword($config['password']);
        }

        return $this->configureSmtpTransport($transport, $config);
    }
}
