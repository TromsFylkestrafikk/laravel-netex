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
        Schema::create('netex_journey_day_types', function (Blueprint $table) {
            $table->id()->comment('Laravel internal ID');
            $table->char('service_journey_id')->index()->comment('References netex_vehicle_journey.id');
            $table->char('day_type_ref')->comment('Daytype reference. This can be joined with `netex_calendar.ref` to retrieve dates.');
            $table->unique(['service_journey_id', 'day_type_ref']);
        });

        Schema::table('netex_vehicle_journeys', function (Blueprint $table) {
            $table->dropColumn('calendar_ref');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('netex_journey_day_types');

        Schema::table('netex_vehicle_journeys', function (Blueprint $table) {
            $table->char('calendar_ref');
        });
    }
};
