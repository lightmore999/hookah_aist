<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Используем RAW SQL для удаления таблицы со всеми зависимостями
        DB::statement('DROP TABLE IF EXISTS recipes CASCADE');
    }

    public function down(): void
    {
        // Восстанавливаем таблицу при откате миграции
        Schema::create('recipes', function (Blueprint $table) {
            $table->id(); // ID RecipesItem (автоинкремент)
            $table->string('name'); // Name (string)
            $table->decimal('cost', 10, 2)->default(0); // Cost (decimal)
            $table->text('description')->nullable(); // Description (string/text)
            $table->timestamps();
        });
    }
};