<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UpdateTypeColumnOnCollectionsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('collections', function (Blueprint $table) {
            $table->string('type_new')->after('type');
        });

        DB::table('collections')->update([
            'type_new' => DB::raw('type'),
        ]);

        Schema::table('collections', function (Blueprint $table) {
            $table->dropIndex(['type']);
            $table->dropColumn('type');
        });

        Schema::table('collections', function (Blueprint $table) {
            $table->renameColumn('type_new', 'type');
        });

        Schema::table('collections', function (Blueprint $table) {
            $table->foreign('type')
                ->references('type')
                ->on('collection_types');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('collections', function (Blueprint $table) {
            $table->enum('type_new', ['collection', 'persona'])->after('type');
        });

        DB::table('collections')->update([
            'type_new' => DB::raw('type'),
        ]);

        Schema::table('collections', function (Blueprint $table) {
            $table->dropForeign(['type']);
            $table->dropColumn('type');
        });

        DB::statement('ALTER TABLE `collections` RENAME COLUMN `type_new` TO `type`');

        Schema::table('collections', function (Blueprint $table) {
            $table->index('type');
        });
    }
}
