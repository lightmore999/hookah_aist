<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operation_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action_type'); // create, update, delete, close, open
            $table->string('entity_type'); // table, sale, expense, hookah
            $table->unsignedBigInteger('entity_id'); // ID сущности
            $table->json('old_data')->nullable(); // Данные до изменения
            $table->json('new_data')->nullable(); // Данные после изменения
            $table->text('comment')->nullable(); // Комментарий
            $table->timestamps();
            
            // Индексы для быстрого поиска
            $table->index(['entity_type', 'entity_id']);
            $table->index('action_type');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operation_histories');
    }
};