<?php

use App\Console\Commands\Tlr\ReindexElasticsearchCommand;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;

class ReindexElasticsearchForCategoryTaxonomiesChanges extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Artisan::call(ReindexElasticsearchCommand::class);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Artisan::call(ReindexElasticsearchCommand::class);
    }
}
