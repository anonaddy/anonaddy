<?php

namespace App\Providers;

use App\CustomMailDriver\CustomMailManager;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Mail\MailServiceProvider;

class CustomMailServiceProvider extends MailServiceProvider
{
    /**
     * Register the Illuminate mailer instance.
     *
     * @return void
     */
    protected function registerIlluminateMailer()
    {
        $this->app->singleton('mail.manager', static function (Application $app) {
            return new CustomMailManager($app);
        });

        $this->app->bind('mailer', static function (Application $app) {
            return $app->make('mail.manager')->mailer();
        });
    }
}
