<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAddressColumnsToOrganisationsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('organisations', function (Blueprint $table) {
            $table->string('address_line_1')->nullable()->after('phone');
            $table->string('address_line_2')->nullable()->after('address_line_1');
            $table->string('address_line_3')->nullable()->after('address_line_2');
            $table->string('city')->nullable()->after('address_line_3');
            $table->string('county')->nullable()->after('city');
            $table->string('postcode')->nullable()->after('county');
            $table->string('country')->nullable()->after('postcode');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('organisations', function (Blueprint $table) {
            $table->dropColumn('address_line_1');
            $table->dropColumn('address_line_2');
            $table->dropColumn('address_line_3');
            $table->dropColumn('city');
            $table->dropColumn('county');
            $table->dropColumn('postcode');
            $table->dropColumn('country');
        });
    }
}
