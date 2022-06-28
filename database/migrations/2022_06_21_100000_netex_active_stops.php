<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class NetexActiveStops extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('netex_stop_place', function (Blueprint $table) {
            $table->boolean('active')->default(false)->after('parentSiteRef');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('netex_stop_place', function (Blueprint $table) {
            $table->dropColumn('active');
        });
    }
}
