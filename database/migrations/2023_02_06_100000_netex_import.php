<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNetexImportTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('netex_import', function (Blueprint $table) {
            $table->char('name', 43)->primary();
            $table->bigInteger('size');
            $table->char('md5', 32);
            $table->date('import_date')->nullable();
            $table->date('valid_to')->nullable();
            $table->integer('status');
            $table->integer('version');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('netex_import');
    }
};
