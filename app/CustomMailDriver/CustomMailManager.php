<?php

namespace App\CustomMailDriver;

use Illuminate\Mail\MailManager;
use InvalidArgumentException;

class CustomMailManager extends MailManager
{
    /**
     * Resolve the given mailer.
     *
     * @param  string  $name
     * @return Mailer
     */
    protected function resolve($name): CustomMailer
    {
        $config = $this->getConfig($name);

        if ($config === null) {
            throw new InvalidArgumentException("Mailer [{$name}] is not defined.");
        }

        // Once we have created the mailer instance we will set a container instance
        // on the mailer. This allows us to resolve mailer classes via containers
        // for maximum testability on said classes instead of passing Closures.
        $mailer = new CustomMailer(
            $name,
            $this->app['view'],
            $this->createSymfonyTransport($config),
            $this->app['events']
        );

        if ($this->app->bound('queue')) {
            $mailer->setQueue($this->app['queue']);
        }

        // Next we will set all of the global addresses on this mailer, which allows
        // for easy unification of all "from" addresses as well as easy debugging
        // of sent messages since these will be sent to a single email address.
        foreach (['from', 'reply_to', 'to', 'return_path'] as $type) {
            $this->setGlobalAddress($mailer, $config, $type);
        }

        return $mailer;
    }
}
