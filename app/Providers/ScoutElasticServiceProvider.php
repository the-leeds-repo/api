<?php

namespace App\Providers;

use Elasticsearch\ClientBuilder;
use Illuminate\Support\Facades\Config;
use ScoutElastic\ScoutElasticServiceProvider as BaseScoutElasticServiceProvider;

class ScoutElasticServiceProvider extends BaseScoutElasticServiceProvider
{
    /**
     * Register the service provider.
     */
    public function register()
    {
        $this
            ->app
            ->singleton('scout_elastic.client', function () {
                return ClientBuilder::fromConfig(
                    Config::get('scout_elastic.client')
                );
            });
    }
}
