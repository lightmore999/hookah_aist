<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->integer('system_quantity')->default(0);
            $table->integer('actual_quantity')->default(0);
            $table->timestamps();
            
            $table->unique(['inventory_id', 'product_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('inventory_items');
    }
};