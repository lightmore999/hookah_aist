<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Удаляем старую таблицу sales если она есть
        Schema::dropIfExists('sales');
        
        // 2. Создаем новую таблицу sales
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->decimal('total', 12, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            $table->string('payment_method')->nullable();
            $table->string('status')->default('new'); // new, in_progress, completed, cancelled
            $table->text('comment')->nullable();
            $table->timestamp('sale_date')->useCurrent();
            $table->timestamps();
        });
        
        // 3. Создаем таблицу sale_items
        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity', 12, 3); // количество в базовых единицах
            $table->decimal('unit_price', 12, 2); // цена за единицу на момент продажи
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_items');
        Schema::dropIfExists('sales');
    }
};