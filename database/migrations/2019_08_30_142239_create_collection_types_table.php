<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateCollectionTypesTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('collection_types', function (Blueprint $table) {
            $table->string('type')->primary();
        });

        DB::table('collection_types')->insert([
            ['type' => 'category'],
            ['type' => 'persona'],
            ['type' => 'snomed'],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('collection_types');
    }
}
