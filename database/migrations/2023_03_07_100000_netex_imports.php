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
            $table->id()->comment('Incremental import ID.');
            $table->char('path')->comment('Path to raw XML set relative to netex disk.');
            $table->char('md5')->nullable()->comment('MD5 sum of entire set');
            $table->unsignedInteger('size')->comment('Collected size of XMLs in route set');
            $table->unsignedInteger('files')->comment('Number of XMLs in route set');
            $table->unsignedInteger('journeys')->nullable()->comment('Number of journeys found in set');
            $table->unsignedInteger('calls')->nullable()->comment('Number of calls found in set');
            $table->date('available_from')->nullable()->comment('Route set vailability from date');
            $table->date('available_to')->nullable()->comment('Route set availability to date');
            $table->boolean('activated')->nullable()->comment('Route set is activated');
            $table->enum('import_status', ['new', 'importing', 'imported', 'error'])->default('new')->comment('Status of this import');
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
