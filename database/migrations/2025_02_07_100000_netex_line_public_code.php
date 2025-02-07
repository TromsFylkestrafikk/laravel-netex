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
        Schema::table('netex_active_calls', function(Blueprint $table) {
            $table->char('line_public_code')
                ->comment('Publicly facing line number')
                ->after('active_journey_id');
            $table->dropColumn('line_private_code');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('netex_active_calls', function(Blueprint $table) {
            $table->unsignedInteger('line_private_code')
                ->comment('Internal numeric line number')
                ->after('active_journey_id');
            $table->dropColumn('line_public_code');
        });
    }
};
