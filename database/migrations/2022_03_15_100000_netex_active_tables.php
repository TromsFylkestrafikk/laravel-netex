<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class NetexActiveTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('netex_active_journeys', function (Blueprint $table) {
            $table->id()->comment('Unique ID for journey / day');
            $table->char('journey_ref', 45)->comment('Journey identifier');
            $table->char('name', 45)->comment('Journey name');
            $table->date('date')->comment('The date this journey belongs to. Not necessary run on this day');
            $table->unsignedInteger('private_code')->comment('Local journey code. Usually four digit code.');
            $table->unsignedInteger('operator_ref');
            $table->char('transport_mode', 45)->comment("'bus', 'water', 'rail' or similar");
            $table->char('transport_submode', 45)->comment('Detailed type of transport mode');
            $table->char('line_public_code', 45)->comment('Line number as shown to the public');
            $table->unsignedInteger('line_private_code')->comment('Internal numeric line number');
            $table->char('first_stop_quay', 64);
            $table->char('last_stop_quay', 64);
            $table->timestamp('timestamp_start')->nullable()->comment('Departure time from first stop');
            $table->timestamp('timestamp_end')->nullable()->comment('Arrival time on last stop');
            $table->char('direction', 45);
        });

        Schema::create('netex_active_calls', function (Blueprint $table) {
            $table->id()->comment('Unique ID of call for stop/journey/day/order');
            $table->unsignedBigInteger('active_journey_id');
            $table->unsignedInteger('line_private_code')->comment('Internal numeric line number');
            $table->unsignedInteger('order')->comment('Order of call during journey');
            $table->char('stop_quay_ref', 64)->comment('Stop place quay ID');
            $table->char('stop_place_name')->comment('Stop place name');
            $table->char('destination')->comment('Interim destination. Often changed during a journey');
            $table->timestamp('call_timestamp')->index()->comment('departure or arrival time of call');
            $table->timestamp('arrival_time')->nullable()->comment('Full iso datetime of arrival');
            $table->timestamp('departure_time')->nullable()->comment('Full iso datetime of departure');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('netex_active_journeys');
        Schema::dropIfExists('netex_active_calls');
    }
}
