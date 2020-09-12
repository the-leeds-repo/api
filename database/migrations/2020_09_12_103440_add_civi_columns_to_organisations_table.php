<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCiviColumnsToOrganisationsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('organisations', function (Blueprint $table) {
            $table->boolean('civi_sync_enabled')->default(false)->after('is_hidden');
            $table->string('civi_id')->nullable()->after('civi_sync_enabled');
        });

        Schema::table('organisations', function (Blueprint $table) {
            $table->boolean('civi_sync_enabled')->default(null)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('organisations', function (Blueprint $table) {
            $table->dropColumn('civi_sync_enabled');
            $table->dropColumn('civi_id');
        });
    }
}
