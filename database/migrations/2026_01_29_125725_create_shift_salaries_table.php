<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('shift_salaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('shift_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 10, 2)->default(0);
            $table->timestamps();
            
            // Уникальная комбинация: один сотрудник может иметь только одну запись за смену
            $table->unique(['user_id', 'shift_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('shift_salaries');
    }
};