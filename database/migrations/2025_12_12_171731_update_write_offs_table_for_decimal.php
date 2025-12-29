<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('write_offs', function (Blueprint $table) {
            // Просто меняем тип поля quantity с integer на decimal
            $table->decimal('quantity', 12, 3)->change();
        });
    }

    public function down(): void
    {
        Schema::table('write_offs', function (Blueprint $table) {
            // Возвращаем обратно на integer
            $table->integer('quantity')->change();
        });
    }
};