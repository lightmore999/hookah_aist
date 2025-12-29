<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('expenditures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expenditure_type_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->decimal('cost', 10, 2);
            $table->enum('payment_method', ['cash', 'card'])->default('cash');
            $table->text('comment')->nullable();
            $table->dateTime('expenditure_date');
            $table->boolean('is_hidden_admin')->default(false);
            $table->boolean('is_monthly_expense')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenditures');
    }
};