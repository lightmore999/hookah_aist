<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('table_bookings', function (Blueprint $table) {
            // Меняем колонку guests_count на nullable
            $table->integer('guests_count')->nullable()->change();
            
            // Также можно установить значение по умолчанию (опционально)
            // $table->integer('guests_count')->nullable()->default(1)->change();
        });
    }

    public function down()
    {
        Schema::table('table_bookings', function (Blueprint $table) {
            // Откат изменений - снова делаем NOT NULL
            $table->integer('guests_count')->nullable(false)->change();
        });
    }
};