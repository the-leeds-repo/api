<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateResourcesTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('resources', function (Blueprint $table) {
            $table->uuid('id')
                ->primary();
            $table->uuid('organisation_id');
            $table->foreign('organisation_id')
                ->references('id')
                ->on('organisations');
            $table->string('slug')->unique();
            $table->string('name');
            $table->text('description');
            $table->string('url', 500);
            $table->string('license')->nullable();
            $table->string('author')->nullable();
            $table->date('published_at')->nullable();
            $table->date('last_updated_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('resources');
    }
}
