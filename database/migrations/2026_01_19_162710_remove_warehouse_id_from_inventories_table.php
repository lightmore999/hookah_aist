<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventories', function (Blueprint $table) {
            // Удаляем foreign key constraint если он существует
            $foreignKeys = Schema::getConnection()
                ->getDoctrineSchemaManager()
                ->listTableForeignKeys('inventories');
            
            foreach ($foreignKeys as $foreignKey) {
                if (in_array('warehouse_id', $foreignKey->getColumns())) {
                    $table->dropForeign(['warehouse_id']);
                    break;
                }
            }
            
            // Удаляем колонку
            $table->dropColumn('warehouse_id');
        });
    }

    public function down(): void
    {
        Schema::table('inventories', function (Blueprint $table) {
            $table->foreignId('warehouse_id')->nullable()->constrained()->onDelete('cascade');
        });
    }
};