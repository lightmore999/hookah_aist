<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_item_recipes', function (Blueprint $table) {
            $table->id('IDSales');
            $table->unsignedBigInteger('IDRecipes');
            $table->integer('Quantity')->default(1);
            $table->decimal('UnitPrice', 10, 2);
            $table->unsignedBigInteger('IDOrder');
            $table->timestamps();

            // Внешние ключи
            $table->foreign('IDRecipes')->references('id')->on('recipes')->onDelete('cascade');
            $table->foreign('IDOrder')->references('IDOrder')->on('orders')->onDelete('cascade');
            
            // Индексы
            $table->index('IDRecipes');
            $table->index('IDOrder');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_item_recipes');
    }
};