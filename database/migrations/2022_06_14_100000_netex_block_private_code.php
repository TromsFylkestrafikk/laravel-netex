<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class NetexBlockPrivateCode extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('netex_vehicle_blocks', function (Blueprint $table) {
            $table->char('id', 64)->primary();
            $table->unsignedBigInteger('private_code');
            $table->unsignedInteger('calendar_ref');
        });

        Schema::table('netex_vehicle_schedules', function (Blueprint $table) {
            $table->dropColumn('calendar_ref');
            $table->char('vehicle_block_ref', 64)->after('id')->index('netex_vehicle_schedules__block');
            $table->index('vehicle_journey_ref', 'netex_vehicle_schedules__vehicle_journey');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('netex_vehicle_schedules', function (Blueprint $table) {
            $table->dropColumn('vehicle_block_ref');
            $table->integer('calendar_ref');
            $table->dropIndex('netex_vehicle_schedules__vehicle_journey');
        });

        Schema::dropIfExists('netex_vehicle_blocks');
    }
}
