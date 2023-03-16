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
            $table->char('version', 16)->nullable()->comment('Version attached to route set, if present');
            $table->unsignedInteger('size')->comment('Collected size of XMLs in route set');
            $table->unsignedInteger('files')->comment('Number of XMLs in route set');
            $table->date('available_from')->nullable()->comment('Route set vailability from date');
            $table->date('available_to')->nullable()->comment('Route set availability to date');
            $table->enum('import_status', ['new', 'importing', 'imported', 'error'])
                ->default('new')
                ->comment('Status of this import');
            $table->string('message')->nullable()->comment('Message of what failed during import');
            $table->timestamps();
        });

        Schema::create('netex_active_status', function (Blueprint $table) {
            $table->date('id')->index()->comment('Date of activation');
            $table->unsignedBigInteger('import_id')->comment('Reference to import ID');
            $table->unsignedInteger('journeys')->nullable()->comment('Number of journeys this day');
            $table->unsignedInteger('calls')->nullable()->comment('Number of calls for this day');
            $table->enum('status', ['empty', 'incomplete', 'activated'])
                ->default('empty')
                ->comment('Activation status for given day');
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
        Schema::dropIfExists('netex_active_status');
    }
};
