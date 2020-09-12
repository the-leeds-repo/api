<?php

namespace App\Providers;

use App\CiviCrm\ClientInterface;
use App\CiviCrm\LogClient;
use App\Contracts\VariableSubstituter;
use App\RoleManagement\RoleAuthorizer;
use App\RoleManagement\RoleAuthorizerInterface;
use App\RoleManagement\RoleChecker;
use App\RoleManagement\RoleCheckerInterface;
use App\RoleManagement\RoleManager;
use App\RoleManagement\RoleManagerInterface;
use App\VariableSubstitution\DoubleParenthesisVariableSubstituter;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot()
    {
        // Use CarbonImmutable instead of Carbon.
        Date::use(CarbonImmutable::class);

        // Geocode.
        switch (config('tlr.geocode_driver')) {
            case 'google':
                $this->app->singleton(\App\Contracts\Geocoder::class, \App\Geocode\GoogleGeocoder::class);
                break;
            case 'nominatim':
                $this->app->singleton(\App\Contracts\Geocoder::class, \App\Geocode\NominatimGeocoder::class);
                break;
            case 'stub':
            default:
                $this->app->singleton(\App\Contracts\Geocoder::class, \App\Geocode\StubGeocoder::class);
                break;
        }

        // Search.
        switch (config('scout.driver')) {
            case 'elastic':
            default:
                $this->app->singleton(\App\Contracts\ServiceSearch::class, \App\Search\ElasticsearchServiceSearch::class);
                $this->app->singleton(\App\Contracts\ResourceSearch::class, \App\Search\ElasticsearchResourceSearch::class);
                break;
        }

        // Email Sender.
        switch (config('tlr.email_driver')) {
            case 'gov':
                $this->app->singleton(\App\Contracts\EmailSender::class, \App\EmailSenders\GovNotifyEmailSender::class);
                break;
            case 'mailgun':
                $this->app->singleton(\App\Contracts\EmailSender::class, \App\EmailSenders\MailgunEmailSender::class);
                break;
            case 'null':
                $this->app->singleton(\App\Contracts\EmailSender::class, \App\EmailSenders\NullEmailSender::class);
                break;
            case 'log':
            default:
                $this->app->singleton(\App\Contracts\EmailSender::class, \App\EmailSenders\LogEmailSender::class);
                break;
        }

        // SMS Sender.
        switch (config('tlr.sms_driver')) {
            case 'gov':
                $this->app->singleton(\App\Contracts\SmsSender::class, \App\SmsSenders\GovNotifySmsSender::class);
                break;
            case 'twilio':
                $this->app->singleton(\App\Contracts\SmsSender::class, \App\SmsSenders\TwilioSmsSender::class);
                break;
            case 'null':
                $this->app->singleton(\App\Contracts\SmsSender::class, \App\SmsSenders\NullSmsSender::class);
                break;
            case 'log':
            default:
                $this->app->singleton(\App\Contracts\SmsSender::class, \App\SmsSenders\LogSmsSender::class);
                break;
        }

        // Variable substitution.
        $this->app->bind(VariableSubstituter::class, DoubleParenthesisVariableSubstituter::class);

        $this->app->bind(RoleAuthorizerInterface::class, RoleAuthorizer::class);
        $this->app->bind(RoleCheckerInterface::class, RoleChecker::class);
        $this->app->bind(RoleManagerInterface::class, RoleManager::class);

        $this->app->bind(ClientInterface::class, LogClient::class);
    }

    /**
     * Register any application services.
     */
    public function register()
    {
        //
    }
}
