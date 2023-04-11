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
        Schema::create('netex_notices', function (Blueprint $table) {
            $table->char('id', 45)->primary();
            $table->mediumText('text');
            $table->char('public_code', 45);
        });

        Schema::create('netex_notice_assignments', function (Blueprint $table) {
            $table->char('id', 45)->primary();
            $table->char('notice_ref', 45);
            $table->char('notice_obj_ref');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('netex_notices');
        Schema::dropIfExists('netex_notice_assignments');
    }
};
