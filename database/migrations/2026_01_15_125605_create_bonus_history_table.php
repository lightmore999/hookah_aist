<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bonus_history', function (Blueprint $table) {
            $table->id();
            
            // Пользователь, чей баланс изменяется
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            
            // Сумма операции
            $table->decimal('amount', 12, 2);
            
            // Тип операции: credit (начисление) или debit (списание)
            $table->enum('operation_type', ['credit', 'debit']);
            
            // Баланс пользователя ПОСЛЕ операции
            $table->decimal('balance_after', 12, 2);
            
            // Причина операции (текстовое описание) - необязательное
            $table->string('reason')->nullable();
            
            // Ссылка на продажу, если операция связана с продажей (необязательно)
            $table->foreignId('sale_id')->nullable()->constrained('sales')->nullOnDelete();
            
            // Индексы для производительности
            $table->index('client_id');
            $table->index('sale_id');
            $table->index('created_at');
            $table->index(['client_id', 'created_at']);
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bonus_history');
    }
};