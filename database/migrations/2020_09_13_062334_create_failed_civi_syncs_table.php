<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFailedCiviSyncsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('failed_civi_syncs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organisation_id');
            $table->foreign('organisation_id')->references('id')->on('organisations');
            $table->integer('status_code');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('failed_civi_syncs');
    }
}
