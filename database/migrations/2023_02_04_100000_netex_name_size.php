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
        $tablesWithName = [
            'netex_active_journeys',
            'netex_journey_patterns',
            'netex_line_groups',
            'netex_lines',
            'netex_operators',
            'netex_routes',
            'netex_stop_points',
            'netex_tariff_zone',
            'netex_vehicle_journeys',
        ];
        foreach ($tablesWithName as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->char('name')->change();
            });
        }

        // Some special cases
        Schema::table('netex_operators', function (Blueprint $table) {
            $table->char('legal_name')->change();
        });
        Schema::table('netex_routes', function (Blueprint $table) {
            $table->char('short_name')->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
};
