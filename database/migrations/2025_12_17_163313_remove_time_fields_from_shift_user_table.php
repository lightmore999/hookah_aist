<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('shift_user', function (Blueprint $table) {
            $table->dropColumn(['start_time', 'end_time']);
        });
    }

    public function down()
    {
        Schema::table('shift_user', function (Blueprint $table) {
            $table->datetime('start_time')->nullable();
            $table->datetime('end_time')->nullable();
        });
    }
};