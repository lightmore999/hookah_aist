<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Проверяем, не была ли уже добавлена колонка table_name_id
        if (!Schema::hasColumn('table_bookings', 'table_name_id')) {
            Schema::table('table_bookings', function (Blueprint $table) {
                $table->unsignedBigInteger('table_name_id')->nullable()->after('table_number');
            });
        }

        // Проверяем, существует ли таблица table_names
        if (!Schema::hasTable('table_names')) {
            Schema::create('table_names', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->boolean('is_active')->default(true);
                $table->integer('sort_order')->default(0);
                $table->timestamps();
            });

            // Создаем базовые столы
            $this->seedInitialTables();
        }

        // Заполняем table_name_id
        $this->migrateTableNumbers();
    }

    /**
     * Seed initial tables
     */
    private function seedInitialTables(): void
    {
        $tables = [
            ['name' => 'Стол 1', 'is_active' => true, 'sort_order' => 1],
            ['name' => 'Стол 2', 'is_active' => true, 'sort_order' => 2],
            ['name' => 'Стол 3', 'is_active' => true, 'sort_order' => 3],
            ['name' => 'Стол 4', 'is_active' => true, 'sort_order' => 4],
            ['name' => 'Стол 5', 'is_active' => true, 'sort_order' => 5],
            ['name' => 'VIP Стол', 'is_active' => true, 'sort_order' => 6],
        ];

        foreach ($tables as $table) {
            DB::table('table_names')->insert([
                ...$table,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Migrate table numbers to table_name_id
     */
    private function migrateTableNumbers(): void
    {
        // Получаем все уникальные table_number
        $tableNumbers = DB::table('table_bookings')
            ->select('table_number')
            ->distinct()
            ->whereNotNull('table_number')
            ->orderBy('table_number')
            ->pluck('table_number');

        foreach ($tableNumbers as $tableNumber) {
            // Находим или создаем запись в table_names
            $tableName = DB::table('table_names')
                ->where('name', 'LIKE', "%{$tableNumber}%")
                ->first();

            if (!$tableName) {
                // Создаем новую запись
                $tableNameId = DB::table('table_names')->insertGetId([
                    'name' => "Стол {$tableNumber}",
                    'is_active' => true,
                    'sort_order' => (int)$tableNumber,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                $tableNameId = $tableName->id;
            }

            // Обновляем записи в table_bookings
            DB::table('table_bookings')
                ->where('table_number', $tableNumber)
                ->update(['table_name_id' => $tableNameId]);
        }

        // Устанавливаем table_name_id для записей без table_number
        $firstTable = DB::table('table_names')->first();
        if ($firstTable) {
            DB::table('table_bookings')
                ->whereNull('table_name_id')
                ->update(['table_name_id' => $firstTable->id]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Восстанавливаем table_number
        if (!Schema::hasColumn('table_bookings', 'table_number')) {
            Schema::table('table_bookings', function (Blueprint $table) {
                $table->string('table_number')->nullable()->after('id');
            });
        }

        // Восстанавливаем данные
        $bookings = DB::table('table_bookings')
            ->join('table_names', 'table_bookings.table_name_id', '=', 'table_names.id')
            ->select('table_bookings.id', 'table_names.name')
            ->get();

        foreach ($bookings as $booking) {
            $tableNumber = preg_replace('/[^0-9]/', '', $booking->name) ?: '1';
            
            DB::table('table_bookings')
                ->where('id', $booking->id)
                ->update(['table_number' => $tableNumber]);
        }

        // Удаляем table_name_id
        Schema::table('table_bookings', function (Blueprint $table) {
            if (Schema::hasColumn('table_bookings', 'table_name_id')) {
                $table->dropColumn('table_name_id');
            }
        });
    }
};