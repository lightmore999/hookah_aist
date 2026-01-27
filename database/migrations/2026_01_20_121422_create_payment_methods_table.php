<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenditures', function (Blueprint $table) {
            // Добавляем новый столбец для связи
            $table->foreignId('payment_method_id')
                  ->nullable()
                  ->after('payment_method')
                  ->constrained('payment_methods', 'IDPaymentMethod')
                  ->onDelete('set null');
        });

        // Преобразуем старые значения payment_method в новые ID
        $this->migratePaymentMethods();
        
        // Удаляем старый столбец после миграции данных
        Schema::table('expenditures', function (Blueprint $table) {
            $table->dropColumn('payment_method');
        });
    }

    private function migratePaymentMethods(): void
    {
        $methods = DB::table('payment_methods')->pluck('IDPaymentMethod', 'Name')->toArray();
        
        // Маппинг старых значений на новые ID
        $mapping = [
            'cash' => 'Наличные',
            'card' => 'Карта',
        ];
        
        foreach ($mapping as $oldValue => $methodName) {
            if (isset($methods[$methodName])) {
                DB::table('expenditures')
                    ->where('payment_method', $oldValue)
                    ->update(['payment_method_id' => $methods[$methodName]]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('expenditures', function (Blueprint $table) {
            // Восстанавливаем старый столбец
            $table->string('payment_method', 10)->nullable()->after('cost');
            
            // Удаляем связь и столбец
            $table->dropForeign(['payment_method_id']);
            $table->dropColumn('payment_method_id');
        });

        // Возвращаем данные обратно
        $this->rollbackPaymentMethods();
    }

    private function rollbackPaymentMethods(): void
    {
        $methods = DB::table('payment_methods')->pluck('Name', 'IDPaymentMethod')->toArray();
        
        $reverseMapping = [
            'Наличные' => 'cash',
            'Карта' => 'card',
        ];
        
        foreach ($methods as $id => $name) {
            if (isset($reverseMapping[$name])) {
                DB::table('expenditures')
                    ->where('payment_method_id', $id)
                    ->update(['payment_method' => $reverseMapping[$name]]);
            }
        }
    }
};