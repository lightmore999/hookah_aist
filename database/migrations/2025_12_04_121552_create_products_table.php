<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('product_category_id')->constrained()->onDelete('cascade');
            $table->decimal('price', 10, 2);           // Цена за упаковку
            $table->decimal('cost', 10, 2);            // Себестоимость за упаковку
            $table->string('barcode');
            $table->string('article_number');
            $table->decimal('packaging', 10, 3)->default(1); // Размер упаковки
            $table->timestamps();
        });
    }
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('products');
    }
};
