<?php

namespace App\Providers;

use Aws\Credentials\CredentialProvider;
use Aws\Credentials\Credentials;
use Aws\ElasticsearchService\ElasticsearchPhpHandler;
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
                $config = Config::get('scout_elastic.client');

                // Provide a custom handler if using AWS.
                if (Config::get('scout_elastic.aws.enabled')) {
                    $config['handler'] = new ElasticsearchPhpHandler(
                        Config::get('scout_elastic.aws.region'),
                        CredentialProvider::fromCredentials(
                            new Credentials(
                                Config::get('scout_elastic.aws.key'),
                                Config::get('scout_elastic.aws.secret')
                            )
                        )
                    );
                }

                return ClientBuilder::fromConfig($config);
            });
    }
}
