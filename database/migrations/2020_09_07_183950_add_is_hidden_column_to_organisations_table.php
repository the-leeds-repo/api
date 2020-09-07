<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsHiddenColumnToOrganisationsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('organisations', function (Blueprint $table) {
            $table->boolean('is_hidden')->default(false)->after('phone');
        });

        Schema::table('organisations', function (Blueprint $table) {
            $table->boolean('is_hidden')->default(null)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('organisations', function (Blueprint $table) {
            $table->dropColumn('is_hidden');
        });
    }
}
