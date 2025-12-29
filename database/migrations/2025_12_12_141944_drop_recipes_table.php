<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Проверяем, существует ли колонка cost
        if (Schema::hasColumn('recipes', 'cost')) {
            Schema::table('recipes', function (Blueprint $table) {
                // Переименовываем cost в price
                $table->renameColumn('cost', 'price');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('recipes', 'price')) {
            Schema::table('recipes', function (Blueprint $table) {
                $table->renameColumn('price', 'cost');
            });
        }
    }
};