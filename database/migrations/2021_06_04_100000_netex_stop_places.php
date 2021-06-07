<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class NetexStopPlaces extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('netex_stop_place', function (Blueprint $table) {
            $table->char('id', 64);
            $table->char('version', 10);
            $table->dateTime('created')->nullable();
            $table->dateTime('changed')->nullable();
            $table->string('name');
            $table->char('stopPlaceType', 64)->nullable();
            $table->double('latitude');
            $table->double('longitude');
            $table->dateTime('validFromDate')->nullable();
            $table->dateTime('validToDate')->nullable();
            $table->char('topographicPlaceRef', 64)->nullable();
            $table->char('parentSiteRef', 64)->nullable();
            $table->timestamps();
            $table->primary('id');
            $table->index('name');
        });

        // Create a map of alternative IDs for stop places.
        Schema::create('netex_stop_place_alt_id', function (Blueprint $table) {
            $table->char('alt_id', 64);
            $table->char('stop_place_id', 64);
            $table->unique(['alt_id', 'stop_place_id']);
        });

        Schema::create('netex_stop_quay', function (Blueprint $table) {
            $table->char('id', 64);
            $table->char('version', 10);
            $table->dateTime('created')->nullable();
            $table->dateTime('changed')->nullable();
            $table->char('stop_place_id', 64);
            $table->char('privateCode', 32);
            $table->char('publicCode', 32)->nullable();
            $table->double('latitude');
            $table->double('longitude');
            $table->timestamps();
            $table->primary('id');
            $table->index('stop_place_id');
        });

        // Create a map of alternative IDs for stop quays.
        Schema::create('netex_stop_quay_alt_id', function (Blueprint $table) {
            $table->char('alt_id', 64);
            $table->char('stop_quay_id', 64);
            $table->unique(['alt_id', 'stop_quay_id']);
        });

        Schema::create('netex_group_of_stop_places', function (Blueprint $table) {
            $table->char('id', 64);
            $table->char('version', 10);
            $table->dateTime('created')->nullable();
            $table->dateTime('changed')->nullable();
            $table->string('name');
            $table->double('latitude');
            $table->double('longitude');
            $table->timestamps();
            $table->primary('id');
            $table->index('name');
        });

        Schema::create('netex_stop_place_group_member', function (Blueprint $table) {
            $table->increments('id');
            $table->char('stop_place_id', 64);
            $table->char('group_of_stop_places_id', 64);
            $table->unique(['stop_place_id', 'group_of_stop_places_id'], 'stop_place_group_member_key');
        });

        Schema::create('netex_topographic_place', function (Blueprint $table) {
            $table->char('id', 64);
            $table->char('version', 10);
            $table->dateTime('created')->nullable();
            $table->dateTime('changed')->nullable();
            $table->string('name');
            $table->dateTime('validFromDate')->nullable();
            $table->dateTime('validToDate')->nullable();
            $table->char('isoCode', 16)->nullable();
            $table->char('topographicPlaceType', 20);
            $table->char('parentTopographicPlaceref', 64)->nullable();
            $table->timestamps();
            $table->primary('id');
            $table->index('name');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('netex_stop_place');
        Schema::dropIfExists('netex_stop_place_alt_id');
        Schema::dropIfExists('netex_stop_quay');
        Schema::dropIfExists('netex_stop_quay_alt_id');
        Schema::dropIfExists('netex_group_of_stop_places');
        Schema::dropIfExists('netex_stop_place_group_member');
        Schema::dropIfExists('netex_topographic_place');
    }
}
