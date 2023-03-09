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
        Schema::create('netex_imports', function (Blueprint $table) {
            $table->char('id')->primary()->comment('Unique identifier for set.');
            $table->char('path')->comment('Path to raw XML set relative to netex disk.');
            $table->char('md5')->nullable()->comment('MD5 sum of entire set');
            $table->enum('import_status', ['init', 'loading', 'error', 'imported'])->default('init')->comment('Status of this import');
            $table->string('message')->nullable()->comment('Message of what failed during import');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('netex_imports');
    }
};
