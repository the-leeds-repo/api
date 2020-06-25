<?php

namespace App\Console\Commands\Tlr;

use App\Models\IndexConfigurators\ResourcesIndexConfigurator;
use App\Models\IndexConfigurators\ServicesIndexConfigurator;
use App\Models\Resource;
use App\Models\Service;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Throwable;

class ReindexElasticsearchCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tlr:reindex-elasticsearch';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deletes the indices if they exist, recreates them, and then imports all data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (Config::get('scout.driver') !== 'elastic') {
            $this->warn('Did not reindex due to not using the [elastic] Scout driver.');

            return;
        }

        if (Schema::hasTable((new Service())->getTable())) {
            $this->reindex(ServicesIndexConfigurator::class, Service::class);
        }

        if (Schema::hasTable((new Resource())->getTable())) {
            $this->reindex(ResourcesIndexConfigurator::class, Resource::class);
        }
    }

    protected function reindex(string $indexConfigurator, string $model): void
    {
        try {
            $this->line("Dropping index for [{$model}]...");
            $this->call('elastic:drop-index', ['index-configurator' => $indexConfigurator]);
        } catch (Throwable $exception) {
            // If the index already does not exist then do nothing.
            $this->warn('Could not drop index, this is most likely due to the index not already existing.');
        }

        $this->line("Creating index for [{$model}]...");
        $this->call('elastic:create-index', ['index-configurator' => $indexConfigurator]);

        $this->line("Updating index mapping for [{$model}]...");
        $this->call('elastic:update-mapping', ['model' => $model]);

        $this->line("Importing documents for [{$model}]...");
        $this->call('tlr:scout-import', ['model' => $model]);
    }
}
