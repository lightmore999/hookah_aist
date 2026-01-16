<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bonus_cards', function (Blueprint $table) {
            // Удаляем ненужные колонки
            $table->dropColumn(['EarntRantTable', 'EarntRantTakeaway', 'TableCloseDiscountPercent']);
            
            // Добавляем одно поле для начисления бонусов с продаж
            $table->integer('BonusPercent')->default(0)->comment('Начисление бонусов с продажи (в %)');
        });
    }

    public function down(): void
    {
        Schema::table('bonus_cards', function (Blueprint $table) {
            // Восстанавливаем удаленные колонки
            $table->integer('EarntRantTable')->default(0)->comment('Начисление баллов за стол (в %)');
            $table->integer('EarntRantTakeaway')->default(0)->comment('Начисление баллов за доставку/с собой (в %)');
            $table->integer('TableCloseDiscountPercent')->default(0)->comment('Процент скидки при закрытии стола');
            
            // Удаляем новое поле
            $table->dropColumn('BonusPercent');
        });
    }
};