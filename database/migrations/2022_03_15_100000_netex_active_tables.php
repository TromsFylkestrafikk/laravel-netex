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
            $table->id()->comment('Unique ID for journey/day');
            $table->date('date')->index()->comment('The date this journey belongs to. The actual journey is not necessary run on this day');
            $table->char('journey_ref', 45)->comment('Journey identifier');
            $table->char('name', 45)->comment('Journey name');
            $table->unsignedInteger('private_code')->comment('Local journey code. Usually four digit code.');
            $table->char('direction', 45)->comment("'inbound' or 'outbound'");
            $table->unsignedInteger('operator_ref');
            $table->unsignedInteger('line_private_code')->comment('Internal numeric line number');
            $table->char('line_public_code', 45)->comment('Line number as shown to the public');
            $table->char('transport_mode', 45)->comment("'bus', 'water', 'rail' or similar");
            $table->char('transport_submode', 45)->comment('Detailed type of transport mode');
            $table->char('first_stop_quay_ref', 64)->nullable();
            $table->char('last_stop_quay_ref', 64)->nullable();
            $table->timestamp('start_at')->nullable()->comment('Departure time from first stop');
            $table->timestamp('end_at')->nullable()->comment('Arrival time on last stop');
            $table->timestamps();
        });

        Schema::create('netex_active_calls', function (Blueprint $table) {
            $table->id()->comment('Unique ID of call for stop/journey/day/order');
            $table->unsignedBigInteger('active_journey_id');
            $table->unsignedInteger('line_private_code')->comment('Internal numeric line number');
            $table->char('destination')->default('')->comment('Interim/current destination. Often changed during a journey');
            $table->unsignedInteger('order')->comment('Order of call during journey');
            $table->char('quay_ref', 64)->comment('Stop place quay ID');
            $table->char('stop_place_name')->comment('Stop place name');
            $table->boolean('alighting')->default(true)->comment('Stop allows alighting');
            $table->boolean('boarding')->default(true)->comment('Stop allows boarding');
            $table->timestamp('call_time')->index('netex_active_calls__call_time')->comment('Arrival or departure iso datetime of call');
            $table->timestamp('arrival_time')->nullable()->comment('Full iso datetime of arrival');
            $table->timestamp('departure_time')->nullable()->comment('Full iso datetime of departure');
            $table->timestamps();
        });

        // Add indexes to existing tables to speed up raw queries.
        Schema::table('netex_passing_times', function (Blueprint $table) {
            $table->index('vehicle_journey_ref', 'netex_passing_times__journey_ref');
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
        Schema::table('netex_passing_times', function (Blueprint $table) {
            $table->dropIndex('netex_passing_times__journey_ref');
        });
    }
}