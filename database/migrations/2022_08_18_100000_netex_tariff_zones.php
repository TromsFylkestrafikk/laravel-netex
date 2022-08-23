<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class NetexTariffZones extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('netex_tariff_zone', function (Blueprint $table) {
            $table->char('id', 64)->primary();
            $table->char('version', 10);
            $table->dateTime('created')->nullable();
            $table->dateTime('changed')->nullable();
            $table->char('name', 64);
            $table->mediumText('polygon_poslist')->nullable();
            $table->dateTime('validFromDate')->nullable();
            $table->dateTime('validToDate')->nullable();
            $table->timestamps();
        });

        Schema::create('netex_stop_tariff_zone', function (Blueprint $table) {
            $table->char('stop_place_id', 64);
            $table->char('tariff_zone_id', 64);
            $table->unique(['stop_place_id', 'tariff_zone_id']);
        });

        Schema::table('netex_topographic_place', function (Blueprint $table) {
            $table->mediumText('polygon_poslist')->nullable()->after('topographicPlaceType');
        });
    }

    /**
     * reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('netex_tariff_zone');
        Schema::dropIfExists('netex_stop_tariff_zone');

        Schema::table('netex_topographic_place', function (Blueprint $table) {
            $table->dropColumn('polygon_poslist');
        });
    }
}
