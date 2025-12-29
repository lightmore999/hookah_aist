<?php

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
        Schema::dropIfExists('stock');
        
        Schema::create('stock', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')
                  ->constrained()
                  ->onDelete('cascade');
            $table->foreignId('product_id')
                  ->constrained()
                  ->onDelete('cascade');
            
            // ТОЛЬКО самое необходимое
            $table->decimal('quantity', 12, 3)->default(0)->comment('Количество в базовых единицах (мл, г, шт)');
            $table->timestamp('last_updated')->useCurrent()->comment('Время последнего обновления');
            $table->timestamps();
            
            // Уникальный индекс - одна запись на товар в одном складе
            $table->unique(['warehouse_id', 'product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock');
        
        // При откате создаем старую структуру (если понадобится)
        Schema::create('stock', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->integer('whole_packages')->default(0);
            $table->decimal('opened_quantity', 12, 3)->default(0);
            $table->decimal('total_quantity', 12, 3)->default(0);
            $table->timestamp('last_updated')->nullable();
            $table->timestamps();
            
            $table->unique(['warehouse_id', 'product_id']);
        });
    }
};