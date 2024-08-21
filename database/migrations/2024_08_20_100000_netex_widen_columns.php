<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // None of these operations require any down/rollback tratment.
        $this->widenStops();
        $this->widenRoutedata();
        $this->widenImports();
        $this->widenActiveTables();

        // Create indices
        Schema::table('netex_stop_assignments', function (Blueprint $table) {
            $table->index('stop_point_ref');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('netex_stop_assignment', function (Blueprint $table) {
            $table->dropIndex('netex_stop_assignments_stop_point_ref_index');
        });
    }

    protected function widenStops(): void
    {
        Schema::table('netex_operators', function (Blueprint $table) {
            $table->char('id')->change();
        });

        Schema::table('netex_stop_place', function (Blueprint $table) {
            $table->char('id')->change();
            $table->char('version')->change();
            $table->string('name')->change();
            $table->char('stopPlaceType')->nullable()->change();
            $table->char('topographicPlaceRef')->nullable()->change();
            $table->char('parentSiteRef')->nullable()->change();
        });

        Schema::table('netex_stop_place_alt_id', function (Blueprint $table) {
            $table->char('alt_id')->change();
            $table->char('stop_place_id')->change();
        });

        Schema::table('netex_stop_quay', function (Blueprint $table) {
            $table->char('id')->change();
            $table->char('version')->change();
            $table->char('stop_place_id')->change();
            $table->char('privateCode')->change();
            $table->char('publicCode')->nullable()->change();
        });

        Schema::table('netex_stop_quay_alt_id', function (Blueprint $table) {
            $table->char('alt_id')->change();
            $table->char('stop_quay_id')->change();
        });

        Schema::table('netex_group_of_stop_places', function (Blueprint $table) {
            $table->char('id')->change();
            $table->char('version')->change();
        });

        Schema::table('netex_stop_place_group_member', function (Blueprint $table) {
            $table->char('stop_place_id')->change();
            $table->char('group_of_stop_places_id')->change();
        });

        Schema::table('netex_topographic_place', function (Blueprint $table) {
            $table->char('id')->change();
            $table->char('version')->change();
            $table->char('isoCode')->nullable()->change();
            $table->char('topographicPlaceType')->change();
            $table->char('parentTopographicPlaceref')->nullable()->change();
        });

        Schema::table('netex_tariff_zone', function (Blueprint $table) {
            $table->char('id')->change();
            $table->char('version')->change();
        });

        Schema::table('netex_stop_tariff_zone', function (Blueprint $table) {
            $table->char('stop_place_id')->change();
            $table->char('tariff_zone_id')->change();
        });
    }

    protected function widenRoutedata(): void
    {
        Schema::table('netex_calendar', function (Blueprint $table) {
            $table->char('id')->change();
            $table->char('ref')->change();
        });

        Schema::table('netex_line_groups', function (Blueprint $table) {
            $table->char('id')->change();
            $table->char('name')->change();
        });

        Schema::table('netex_lines', function (Blueprint $table) {
            $table->char('id')->change();
            $table->char('name')->change();
            $table->char('transport_mode')->change();
            $table->char('transport_submode')->change();
            $table->char('public_code')->change();
            $table->char('operator_ref')->change();
            $table->char('line_group_ref')->change();
        });

        Schema::table('netex_stop_points', function (Blueprint $table) {
            $table->char('id')->change();
            $table->char('name')->change();
        });

        Schema::table('netex_stop_assignments', function (Blueprint $table) {
            $table->char('id')->change();
            $table->char('stop_point_ref')->change();
            $table->char('quay_ref')->change();
        });

        Schema::table('netex_service_links', function (Blueprint $table) {
            $table->char('id')->change();
        });

        Schema::table('netex_vehicle_blocks', function (Blueprint $table) {
            $table->char('id')->change();
            $table->char('calendar_ref')->change();
        });

        Schema::table('netex_vehicle_schedules', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->autoIncrement()->change();
            $table->char('vehicle_block_ref')->change();
            $table->char('vehicle_journey_ref')->change();
        });

        Schema::table('netex_vehicle_journeys', function (Blueprint $table) {
            $table->char('id')->change();
            $table->char('name')->change();
            $table->char('journey_pattern_ref')->change();
            $table->char('operator_ref')->change();
            $table->char('line_ref')->change();
            $table->char('calendar_ref')->change();
        });

        Schema::table('netex_passing_times', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->autoIncrement()->change();
            $table->char('vehicle_journey_ref')->change();
            $table->char('journey_pattern_stop_point_ref')->change();
        });

        Schema::table('netex_destination_displays', function (Blueprint $table) {
            $table->char('id')->change();
        });

        Schema::table('netex_journey_patterns', function (Blueprint $table) {
            $table->char('id')->change();
            $table->char('name')->change();
            $table->char('route_ref')->change();
        });

        Schema::table('netex_journey_pattern_stop_point', function (Blueprint $table) {
            $table->char('id')->change();
            $table->char('journey_pattern_ref')->change();
            $table->char('stop_point_ref')->change();
            $table->char('destination_display_ref')->nullable()->comment("Reference to current destination on route.")->change();
        });

        Schema::table('netex_journey_pattern_link', function (Blueprint $table) {
            $table->char('id')->change();
            $table->char('journey_pattern_ref')->change();
            $table->char('service_link_ref')->change();
        });

        Schema::table('netex_routes', function (Blueprint $table) {
            $table->char('id')->change();
            $table->char('line_ref')->change();
            $table->char('direction')->change();
        });

        Schema::table('netex_route_point_sequence', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->autoIncrement()->change();
            $table->char('route_ref')->change();
            $table->char('stop_point_ref')->change();
        });

        Schema::table('netex_notices', function (Blueprint $table) {
            $table->char('id')->change();
            $table->char('public_code')->change();
        });
    }

    protected function widenImports(): void
    {
        Schema::table('netex_imports', function (Blueprint $table) {
            $table->char('version')->nullable()->comment('Version attached to route set, if present')->change();
        });
    }

    protected function widenActiveTables(): void
    {
        Schema::table('netex_active_journeys', function (Blueprint $table) {
            $table->char('id')->comment('Unique ID for journey/day')->change();
            $table->char('vehicle_journey_id')->comment('Journey ID')->change();
            $table->char('line_id')->comment('Reference to netex_lines table')->change();
            $table->char('name')->comment('Journey name')->change();
            $table->char('direction')->comment("'inbound' or 'outbound'")->change();
            $table->char('line_public_code')->comment('Line number as shown to the public')->change();
            $table->char('transport_mode')->comment("'bus', 'water', 'rail' or similar")->change();
            $table->char('transport_submode')->comment('Detailed type of transport mode')->change();
            $table->char('first_stop_quay_id')->nullable()->change();
            $table->char('last_stop_quay_id')->nullable()->change();
        });

        Schema::table('netex_active_calls', function (Blueprint $table) {
            $table->char('id')->comment('Unique ID of call for stop/journey/day/order')->change();
            $table->char('active_journey_id')->change();
            $table->char('stop_quay_id')->comment('Stop place quay ID')->change();
        });
    }
};
