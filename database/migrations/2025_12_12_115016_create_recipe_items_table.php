<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recipe_items', function (Blueprint $table) {
            $table->id(); // IDRecipesItems (автоинкремент)
            $table->foreignId('recipe_id')->constrained('recipes')->onDelete('cascade'); // FK ID Product (но это рецепт)
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade'); // FK ID Product (продукт)
            $table->integer('quantity')->default(1); // Quantity (int)
            $table->timestamps();
            
            // Индекс для быстрого поиска
            $table->index(['recipe_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recipe_items');
    }
};