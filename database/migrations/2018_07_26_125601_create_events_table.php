<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEventsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('events', function (Blueprint $table) {
            $table->increments('id');

            $table->string('client_id')->nullable()->default('');
            $table->string('client_email')->nullable()->default('');
            $table->string('client_name')->nullable()->default('');
            $table->string('event_id')->nullable()->default('');
            $table->string('kind')->nullable()->default('');
            $table->string('htmlLink')->nullable()->default('');
            $table->string('summary')->nullable()->default('');

            $table->string('start_date')->nullable();
            $table->string('start_date_time')->nullable();
            $table->string('start_time_zone')->nullable();

            $table->string('end_date')->nullable();
            $table->string('end_date_time')->nullable();
            $table->string('end_time_zone')->nullable();

            $table->string('recurrence')->nullable()->default('');


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
        Schema::dropIfExists('events');
    }
}
