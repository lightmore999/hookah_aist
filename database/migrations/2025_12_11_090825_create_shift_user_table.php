<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shift_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shift_id')->constrained('shifts')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('employees')->onDelete('cascade');
            $table->datetime('start_time')->nullable(); // Время начала работы
            $table->datetime('end_time')->nullable();   // Время окончания работы
            $table->timestamps();
            
            // Уникальный индекс чтобы один сотрудник не был в одной смене дважды
            $table->unique(['shift_id', 'user_id']);
            
            // Индексы для быстрого поиска
            $table->index('shift_id');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shift_user');
    }
};