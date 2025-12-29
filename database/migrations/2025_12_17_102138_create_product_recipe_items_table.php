<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('product_recipe_items', function (Blueprint $table) {
            $table->id();
            
            // Основной продукт (который состоит из других)
            $table->foreignId('parent_product_id')
                  ->constrained('products')
                  ->onDelete('cascade');
            
            // Компонент (продукт, входящий в состав)
            $table->foreignId('component_product_id')
                  ->constrained('products')
                  ->onDelete('cascade');
            
            // Количество компонента
            $table->decimal('quantity', 10, 3);
            
            $table->timestamps();
            
            // Уникальность: компонент не может повторяться в одном продукте
            $table->unique(['parent_product_id', 'component_product_id'], 'unique_product_component');
            
            // Индексы для быстрого поиска
            $table->index('parent_product_id');
            $table->index('component_product_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('product_recipe_items');
    }
};