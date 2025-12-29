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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Имя
            $table->string('email')->unique(); // Email
            $table->string('password'); // Пароль
            $table->string('position'); // Должность
            $table->string('social_network')->nullable(); // Соц сеть
            $table->string('phone')->nullable(); // номер телефона
            $table->text('notes')->nullable(); // заметки
            $table->decimal('hookah_percentage', 5, 2)->default(0); // Процент от кальяна за столом
            $table->decimal('hookah_rate', 10, 2)->default(0); // Ставка за кальян
            $table->decimal('shift_rate', 10, 2)->default(0); // Ставка за смену
            $table->decimal('hourly_rate', 10, 2)->default(0); // Почасовая ставка
            $table->string('inn')->nullable(); // ИНН
            $table->string('tips_link')->nullable(); // Ссылка для электронных чаевых
            $table->timestamps(); // дата добавления и обновления
            $table->softDeletes(); // мягкое удаление (опционально)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};