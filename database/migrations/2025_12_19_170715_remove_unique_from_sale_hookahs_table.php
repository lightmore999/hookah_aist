<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_hookahs', function (Blueprint $table) {
            // Удаляем уникальный индекс
            $table->dropUnique(['sale_id', 'hookah_id']);
        });
    }

    public function down(): void
    {
        Schema::table('sale_hookahs', function (Blueprint $table) {
            // Восстанавливаем уникальный индекс
            $table->unique(['sale_id', 'hookah_id']);
        });
    }
};