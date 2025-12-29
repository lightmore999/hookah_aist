<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_hookahs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $table->foreignId('hookah_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            
            // Уникальная связь, чтобы нельзя было добавить один кальян дважды
            $table->unique(['sale_id', 'hookah_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_hookahs');
    }
};