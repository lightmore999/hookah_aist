<?php
// database/migrations/2025_12_15_092936_create_orders_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ЗАМЕНИТЕ ЭТУ СТРОКУ:
// class CreateOrdersTable extends Migration
// НА ЭТУ:
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id('IDOrder');
            $table->unsignedBigInteger('IDClient')->nullable();
            $table->unsignedBigInteger('IDTable')->nullable();
            $table->unsignedBigInteger('IDWarehouses')->nullable();
            $table->decimal('Tips', 10, 2)->default(0);
            $table->decimal('Discount', 10, 2)->default(0);
            $table->decimal('On_loan', 10, 2)->default(0);
            $table->decimal('Total', 10, 2)->default(0);
            $table->unsignedBigInteger('UserId')->nullable();
            $table->text('Comment')->nullable();
            $table->string('Status', 50)->default('new');
            $table->timestamps();

            // Пока БЕЗ внешних ключей
            // $table->foreign('IDClient')->references('id')->on('clients')->onDelete('set null');
            // $table->foreign('IDTable')->references('id')->on('tables')->onDelete('set null');
            // $table->foreign('IDWarehouses')->references('id')->on('warehouses')->onDelete('set null');
            // $table->foreign('UserId')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
}; // ← Не забудьте точку с запятой