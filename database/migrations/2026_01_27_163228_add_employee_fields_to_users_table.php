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
        Schema::table('users', function (Blueprint $table) {
            // Добавляем поле для роли (можно использовать enum или string)
            $table->string('role')->default('user')->after('email');
            
            // Поля из модели Employee
            $table->string('position')->nullable()->after('role');
            $table->string('social_network')->nullable()->after('position');
            $table->string('phone')->nullable()->after('social_network');
            $table->text('notes')->nullable()->after('phone');
            $table->decimal('shift_salary', 10, 2)->nullable()->after('notes');
            $table->decimal('revenue_percentage', 5, 2)->nullable()->after('shift_salary');
            $table->string('inn', 20)->nullable()->after('revenue_percentage');
            $table->string('tips_link')->nullable()->after('inn');
            
            // Добавляем мягкое удаление, если его нет
            if (!Schema::hasColumn('users', 'deleted_at')) {
                $table->softDeletes();
            }
            
            // Индексы для оптимизации
            $table->index('role');
            $table->index('position');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'role',
                'position',
                'social_network',
                'phone',
                'notes',
                'shift_salary',
                'revenue_percentage',
                'inn',
                'tips_link'
            ]);
            
            // Удаляем softDeletes только если мы их добавляли
            if (Schema::hasColumn('users', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};