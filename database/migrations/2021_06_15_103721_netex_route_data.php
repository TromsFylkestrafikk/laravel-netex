<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class NetexRouteData extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('netex_calendar', function (Blueprint $table) {
            $table->char('id', 25);
            $table->integer('ref');
            $table->date('date');
            $table->primary('id');
            $table->index('ref');
        });

        Schema::create('netex_operators', function (Blueprint $table) {
            $table->integer('id');
            $table->char('name', 45);
            $table->char('legal_name', 45);
            $table->integer('company_number');
            $table->primary('id');
        });

        Schema::create('netex_line_groups', function (Blueprint $table) {
            $table->char('id', 20);
            $table->char('name', 45);
            $table->primary('id');
        });

        Schema::create('netex_lines', function (Blueprint $table) {
            $table->char('id', 45);
            $table->char('name', 45);
            $table->char('transport_mode', 45);
            $table->char('transport_submode', 45);
            $table->char('public_code', 45);
            $table->integer('private_code');
            $table->integer('operator_ref');
            $table->char('line_group_ref', 20);
            $table->primary('id');
        });

        Schema::create('netex_stop_points', function (Blueprint $table) {
            $table->char('id', 20);
            $table->char('name', 45);
            $table->primary('id');
        });

        Schema::create('netex_stop_assignments', function (Blueprint $table) {
            $table->char('id', 20);
            $table->integer('order');
            $table->char('stop_point_ref', 20);
            $table->char('quay_ref', 20);
            $table->primary('id');
        });

        Schema::create('netex_service_links', function (Blueprint $table) {
            $table->char('id', 50);
            $table->double('distance');
            $table->integer('srs_dimension');
            $table->integer('count');
            $table->mediumText('pos_list')->nullable();
            $table->primary('id');
        });

        Schema::create('netex_vehicle_schedules', function (Blueprint $table) {
            $table->integer('id')->autoIncrement();
            $table->integer('calendar_ref');
            $table->char('vehicle_journey_ref', 45);
        });

        Schema::create('netex_vehicle_journeys', function (Blueprint $table) {
            $table->char('id', 45);
            $table->char('name', 45);
            $table->integer('private_code');
            $table->char('journey_pattern_ref', 60);
            $table->integer('operator_ref');
            $table->char('line_ref', 45);
            $table->integer('calendar_ref');
            $table->primary('id');
        });

        Schema::create('netex_passing_times', function (Blueprint $table) {
            $table->integer('id')->autoIncrement();
            $table->char('vehicle_journey_ref', 45);
            $table->time('arrival_time')->nullable();
            $table->time('departure_time')->nullable();
            $table->char('journey_pattern_stop_point_ref', 60);
        });

        Schema::create('netex_journey_patterns', function (Blueprint $table) {
            $table->char('id', 60);
            $table->char('name', 45);
            $table->char('route_ref', 60);
            $table->primary('id');
        });

        Schema::create('netex_journey_pattern_stop_point', function (Blueprint $table) {
            $table->char('id', 60);
            $table->char('journey_pattern_ref', 60);
            $table->integer('order');
            $table->char('stop_point_ref', 45);
            $table->tinyInteger('alighting');
            $table->tinyInteger('boarding');
            $table->primary('id');
        });

        Schema::create('netex_journey_pattern_link', function (Blueprint $table) {
            $table->char('id', 45);
            $table->char('journey_pattern_ref', 60);
            $table->integer('order');
            $table->char('service_link_ref', 60);
            $table->primary('id');
        });

        Schema::create('netex_routes', function (Blueprint $table) {
            $table->char('id', 60);
            $table->char('name', 45);
            $table->char('short_name', 45);
            $table->char('line_ref', 45);
            $table->char('direction', 45);
            $table->primary('id');
        });

        Schema::create('netex_route_point_sequence', function (Blueprint $table) {
            $table->integer('id')->autoIncrement();
            $table->char('route_ref', 60);
            $table->integer('order');
            $table->char('stop_point_ref', 45);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('netex_calendar');
        Schema::dropIfExists('netex_operators');
        Schema::dropIfExists('netex_line_groups');
        Schema::dropIfExists('netex_lines');
        Schema::dropIfExists('netex_stop_points');
        Schema::dropIfExists('netex_stop_assignments');
        Schema::dropIfExists('netex_service_links');
        Schema::dropIfExists('netex_vehicle_schedules');
        Schema::dropIfExists('netex_vehicle_journeys');
        Schema::dropIfExists('netex_passing_times');
        Schema::dropIfExists('netex_journey_patterns');
        Schema::dropIfExists('netex_journey_pattern_stop_point');
        Schema::dropIfExists('netex_journey_pattern_link');
        Schema::dropIfExists('netex_routes');
        Schema::dropIfExists('netex_route_point_sequence');
    }
}
