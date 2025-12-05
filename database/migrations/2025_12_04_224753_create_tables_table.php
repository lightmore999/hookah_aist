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
        Schema::create('table_bookings', function (Blueprint $table) {
            $table->id();
            $table->string('table_number'); 
            $table->date('booking_date');
            $table->time('booking_time');
            $table->integer('duration'); 
            $table->integer('guests_count');
            $table->text('comment')->nullable();
            $table->string('phone')->nullable();
            $table->string('guest_name')->nullable();
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
        Schema::dropIfExists('tables');
    }
};
