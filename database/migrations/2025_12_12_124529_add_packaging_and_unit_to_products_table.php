<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            // Размер упаковки (сколько единиц в упаковке)
            // Для штучных товаров = 1, для весовых = количество грамм/мл
            $table->decimal('packaging', 10, 3)->default(1)->after('article_number');
            
            // Единица измерения - САМОЕ ВАЖНОЕ ПОЛЕ!
            $table->string('unit', 10)->default('шт')->after('packaging');
            
            // Индекс для быстрого поиска по unit
            $table->index('unit');
        });
    }

    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['unit']);
            $table->dropColumn(['packaging', 'unit']);
        });
    }
};