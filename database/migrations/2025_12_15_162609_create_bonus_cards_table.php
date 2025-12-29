<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bonus_cards', function (Blueprint $table) {
            $table->id('IDBonusCard');
            $table->string('Name', 100);
            $table->integer('RequiredSpendAmount')->default(0)->comment('Необходимая сумма трат для получения карты');
            $table->integer('EarntRantTable')->default(0)->comment('Начисление баллов за стол (в %)');
            $table->integer('EarntRantTakeaway')->default(0)->comment('Начисление баллов за доставку/с собой (в %)');
            $table->integer('MaxSpendPercent')->default(0)->comment('Максимальный процент оплаты бонусами');
            $table->integer('TableCloseDiscountPercent')->default(0)->comment('Процент скидки при закрытии стола');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bonus_cards');
    }
};