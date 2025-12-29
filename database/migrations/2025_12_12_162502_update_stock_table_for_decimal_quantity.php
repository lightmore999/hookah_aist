<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock', function (Blueprint $table) {
            // Убираем старое quantity
            $table->dropColumn('quantity');
            
            // Добавляем новые поля
            $table->integer('whole_packages')->default(0)->after('product_id');
            $table->decimal('opened_quantity', 12, 3)->default(0)->after('whole_packages');
            $table->decimal('total_quantity', 12, 3)->default(0)->after('opened_quantity');
            
            // total_quantity будет рассчитываться в модели, не в БД
        });
    }

    public function down(): void
    {
        Schema::table('stock', function (Blueprint $table) {
            // Возвращаем обратно
            $table->dropColumn(['whole_packages', 'opened_quantity', 'total_quantity']);
            $table->integer('quantity')->default(0);
        });
    }
};