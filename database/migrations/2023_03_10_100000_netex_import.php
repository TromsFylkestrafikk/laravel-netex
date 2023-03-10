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
        Schema::create('netex_import', function (Blueprint $table) {
            $table->char('id', 32)->primary();
            $table->char('md5', 32);
            $table->unsignedInteger('size');
            $table->unsignedInteger('files');
            $table->date('date')->nullable();
            $table->date('valid_to')->nullable();
            $table->unsignedInteger('days')->nullable();
            $table->unsignedInteger('journeys')->nullable();
            $table->unsignedInteger('calls')->nullable();
            $table->integer('status');
            $table->boolean('activated')->nullable();
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
