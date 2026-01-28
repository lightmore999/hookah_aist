<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('table_names', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique()->comment('Название стола (например: "Первый стол", "Барная стойка")');
            $table->boolean('is_active')->default(true)->comment('Активен ли стол');
            $table->integer('sort_order')->default(0)->comment('Порядок сортировки');
            $table->timestamps();
        });

        // Добавляем стандартные столы
        DB::table('table_names')->insert([
            [
                'name' => '1',
                'is_active' => true,
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => '2',
                'is_active' => true,
                'sort_order' => 2,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => '3',
                'is_active' => true,
                'sort_order' => 3,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => '4',
                'is_active' => true,
                'sort_order' => 4,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Барная стойка',
                'is_active' => true,
                'sort_order' => 5,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => '6',
                'is_active' => true,
                'sort_order' => 6,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => '7',
                'is_active' => true,
                'sort_order' => 7,
                'created_at' => now(),
                'updated_at' => now()
            ],
        ]);
    }

    public function down()
    {
        Schema::dropIfExists('table_names');
    }
};