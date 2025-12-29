<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemovePackagingFromProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            // Удаляем поле packaging
            $table->dropColumn('packaging');
            
            // Если нужно, можно добавить поле package_size в будущем
            // $table->decimal('package_size', 8, 3)->nullable()->after('cost');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            // Восстанавливаем поле packaging при откате миграции
            $table->decimal('packaging', 8, 3)->nullable()->after('cost');
        });
    }
}