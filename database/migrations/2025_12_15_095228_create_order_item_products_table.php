<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_item_products', function (Blueprint $table) {
            $table->id('IDHookah'); // Или можно $table->id() для стандартного имени
            $table->unsignedBigInteger('IDProduct');
            $table->integer('Quantity');
            $table->decimal('UnitPrice', 10, 2);
            $table->unsignedBigInteger('IDOrder');
            $table->timestamps();

            // Внешние ключи
            $table->foreign('IDProduct')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('IDOrder')->references('IDOrder')->on('orders')->onDelete('cascade');
            
            // Индексы для быстрого поиска
            $table->index('IDProduct');
            $table->index('IDOrder');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_item_products');
    }
};