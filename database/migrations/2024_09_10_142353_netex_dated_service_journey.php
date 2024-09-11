<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('netex_operating_days', function (Blueprint $table) {
            $table->char('id')->primary()->comment("Netex ID of operating day");
            $table->date('calendar_date')->comment('Calendar date of day in ISO format');
        });

        Schema::create('netex_dated_service_journeys', function (Blueprint $table) {
            $table->char('id')->primary()->comment('Neted ID of dated service journey');
            $table->char('service_journey_ref')->comment('Relation to netex_vehicle_journeys.id');
            $table->char('operating_day_ref')->comment('Relation to nextex_operating_days.id');
        });

        Schema::table('netex_vehicle_journeys', function (Blueprint $table) {
            $table->char('calendar_ref')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('netex_operating_days');
        Schema::dropIfExists('netex_dated_service_journeys');
    }
};
