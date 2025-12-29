<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recipes', function (Blueprint $table) {
            $table->id(); // ID RecipesItem (автоинкремент)
            $table->string('name'); // Name (string)
            $table->decimal('cost', 10, 2)->default(0); // Cost (decimal)
            $table->text('description')->nullable(); // Description (string/text)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recipes');
    }
};