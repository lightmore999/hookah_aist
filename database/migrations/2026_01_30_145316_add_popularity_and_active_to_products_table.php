<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            // Поле популярности (обязательное, по умолчанию 0)
            $table->integer('popularity')->default(0)->after('article_number');
            
            // Поле активности товара (по умолчанию активный)
            $table->boolean('is_active')->default(true)->after('popularity');
        });
    }

    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['popularity', 'is_active']);
        });
    }
};