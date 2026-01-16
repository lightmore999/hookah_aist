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
        Schema::table('employees', function (Blueprint $table) {
            // Удаляем ненужные поля
            $table->dropColumn(['hookah_percentage', 'hookah_rate', 'hourly_rate']);
            
            // Переименовываем shift_rate в shift_salary (ставка за смену)
            $table->renameColumn('shift_rate', 'shift_salary');
            
            // Добавляем поле для процента со всей выручки
            $table->decimal('revenue_percentage', 5, 2)->nullable()->after('shift_salary')->comment('Процент со всей выручки');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            // Возвращаем удаленные поля
            $table->decimal('hookah_percentage', 5, 2)->nullable();
            $table->decimal('hookah_rate', 10, 2)->nullable();
            $table->decimal('hourly_rate', 10, 2)->nullable();
            
            // Возвращаем старое название поля
            $table->renameColumn('shift_salary', 'shift_rate');
            
            // Удаляем новое поле
            $table->dropColumn('revenue_percentage');
        });
    }
};