<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // 1. Удаляем старое текстовое поле
        Schema::table('sales', function (Blueprint $table) {
            if (Schema::hasColumn('sales', 'payment_method')) {
                $table->dropColumn('payment_method');
            }
        });
        
        // 2. Добавляем новое поле как nullable сначала
        Schema::table('sales', function (Blueprint $table) {
            if (!Schema::hasColumn('sales', 'payment_method_id')) {
                $table->unsignedBigInteger('payment_method_id')->nullable()->after('used_bonus_points');
            }
        });
        
        // 3. Получаем первый способ оплаты (или создаем если нет)
        $paymentMethodId = DB::table('payment_methods')->value('IDPaymentMethod');
        
        if (!$paymentMethodId) {
            // Создаем способ оплаты по умолчанию
            $paymentMethodId = DB::table('payment_methods')->insertGetId([
                'Name' => 'Наличные',
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
        
        // 4. Заполняем все существующие записи
        DB::table('sales')->update(['payment_method_id' => $paymentMethodId]);
        
        // 5. Теперь делаем поле NOT NULL
        Schema::table('sales', function (Blueprint $table) {
            $table->unsignedBigInteger('payment_method_id')->nullable(false)->change();
            
            // Добавляем внешний ключ
            $table->foreign('payment_method_id')
                  ->references('IDPaymentMethod')
                  ->on('payment_methods')
                  ->onDelete('restrict');
        });
    }

    public function down()
    {
        // 1. Удаляем внешний ключ
        Schema::table('sales', function (Blueprint $table) {
            $table->dropForeign(['payment_method_id']);
        });
        
        // 2. Удаляем поле
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn('payment_method_id');
        });
        
        // 3. Восстанавливаем старое поле (nullable)
        Schema::table('sales', function (Blueprint $table) {
            $table->string('payment_method')->nullable()->after('used_bonus_points');
        });
    }
};