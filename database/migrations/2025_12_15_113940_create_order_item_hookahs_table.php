<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_item_hookahs', function (Blueprint $table) {
            $table->id('IDHookahOrderItem');
            $table->unsignedBigInteger('IDHookah');
            $table->unsignedBigInteger('IDOrder');
            $table->timestamps();

            // Внешние ключи
            $table->foreign('IDHookah')->references('id')->on('hookahs')->onDelete('cascade');
            $table->foreign('IDOrder')->references('IDOrder')->on('orders')->onDelete('cascade');
            
            // Индексы (БЕЗ уникального!)
            $table->index('IDHookah');
            $table->index('IDOrder');
            
            // УДАЛЕНО: $table->unique(['IDHookah', 'IDOrder']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_item_hookahs');
    }
};