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
            $table->char('id', 64)->primary()->comment('Unique ID for journey/day');
            $table->date('date')->index('netex_active_journeys__date')->comment('The date this journey belongs to. The actual journey is not necessary run on this day');
            $table->char('vehicle_journey_id', 45)->comment('Journey ID');
            $table->char('line_id', 45)->comment('Reference to netex_lines table');
            $table->char('name', 45)->comment('Journey name');
            $table->unsignedInteger('private_code')->comment('Local journey code. Usually four digit code.');
            $table->char('direction', 45)->comment("'inbound' or 'outbound'");
            $table->unsignedInteger('operator_id');
            $table->unsignedInteger('line_private_code')->comment('Internal numeric line number');
            $table->char('line_public_code', 45)->comment('Line number as shown to the public');
            $table->char('transport_mode', 45)->comment("'bus', 'water', 'rail' or similar");
            $table->char('transport_submode', 45)->comment('Detailed type of transport mode');
            $table->char('first_stop_quay_id', 64)->nullable();
            $table->char('last_stop_quay_id', 64)->nullable();
            $table->timestamp('start_at')->nullable()->comment('Departure time from first stop');
            $table->timestamp('end_at')->nullable()->comment('Arrival time on last stop');
            $table->timestamps();
        });

        Schema::create('netex_active_calls', function (Blueprint $table) {
            $table->char('id', 64)->primary()->comment('Unique ID of call for stop/journey/day/order');
            $table->char('active_journey_id', 64)->index('netex_active_calls__active_journey');
            $table->unsignedInteger('line_private_code')->comment('Internal numeric line number');
            $table->string('destination_display', 256)->default('')->comment('Interim/current destination. Often changed during a journey');
            $table->unsignedInteger('order')->comment('Order of call during journey');
            $table->char('stop_quay_id', 64)->index('netex_active_calls__quay')->comment('Stop place quay ID');
            $table->char('stop_place_name')->comment('Stop place name');
            $table->boolean('alighting')->default(true)->comment('Stop allows alighting');
            $table->boolean('boarding')->default(true)->comment('Stop allows boarding');
            $table->timestamp('call_time')->index('netex_active_calls__call_time')->comment('Arrival or departure iso datetime of call');
            $table->timestamp('arrival_time')->nullable()->comment('Full iso datetime of arrival');
            $table->timestamp('departure_time')->nullable()->comment('Full iso datetime of departure');
            $table->timestamps();
        });

        Schema::create('netex_destination_displays', function (Blueprint $table) {
            $table->unsignedInteger('id')->primary();
            $table->string('front_text', 256);
        });

        Schema::table('netex_journey_pattern_stop_point', function (Blueprint $table) {
            $table->unsignedInteger('destination_display_ref')->nullable()->comment("Reference to current destination on route.");
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
        Schema::dropIfExists('netex_destination_displays');
        Schema::table('netex_passing_times', function (Blueprint $table) {
            $table->dropIndex('netex_passing_times__journey_ref');
        });
        Schema::table('netex_journey_pattern_stop_point', function (Blueprint $table) {
            $table->dropColumn('destination_display_ref');
        });
    }
}
