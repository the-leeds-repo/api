<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Mailgun\Mailgun;

class MailgunServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register()
    {
        $this->app->singleton(Mailgun::class, function () {
            return Mailgun::create(
                config('services.mailgun.secret'),
                config('services.mailgun.endpoint')
            );
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot()
    {
        //
    }
}
