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
        Schema::table('sales', function (Blueprint $table) {
            // Удаляем внешний ключ
            if (Schema::hasColumn('sales', 'warehouse_id')) {
                // Сначала удаляем внешний ключ, если он существует
                $table->dropForeign(['warehouse_id']);
                // Затем удаляем столбец
                $table->dropColumn('warehouse_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->onDelete('set null');
        });
    }
};