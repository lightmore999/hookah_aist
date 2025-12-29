<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Добавляем поля если их нет
            if (!Schema::hasColumn('products', 'barcode')) {
                $table->string('barcode')->nullable()->after('cost');
            }
            
            if (!Schema::hasColumn('products', 'article_number')) {
                $table->string('article_number')->nullable()->after('barcode');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['barcode', 'article_number']);
        });
    }
};