<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        echo "=== ДИАГНОСТИКА ТЕКУЩЕГО СОСТОЯНИЯ ===\n\n";
        
        // 1. Проверяем таблицу users
        echo "1. Таблица users:\n";
        $userCount = DB::select("SELECT COUNT(*) as count FROM users")[0]->count;
        echo "   - Записей: {$userCount}\n";
        
        $userColumns = DB::select("
            SELECT column_name, data_type, is_nullable
            FROM information_schema.columns
            WHERE table_name = 'users'
            ORDER BY ordinal_position
        ");
        
        echo "   - Колонки: ";
        $columns = [];
        foreach ($userColumns as $col) {
            $columns[] = $col->column_name;
        }
        echo implode(', ', $columns) . "\n\n";
        
        // 2. Проверяем внешние ключи shift_user
        echo "2. Таблица shift_user:\n";
        $shiftUserCount = DB::select("SELECT COUNT(*) as count FROM shift_user")[0]->count;
        echo "   - Записей: {$shiftUserCount}\n";
        
        $foreignKeys = DB::select("
            SELECT 
                conname as constraint_name,
                confrelid::regclass as references_table,
                pg_get_constraintdef(oid) as definition
            FROM pg_constraint 
            WHERE contype = 'f' 
            AND conrelid = 'shift_user'::regclass
        ");
        
        echo "   - Внешние ключи:\n";
        foreach ($foreignKeys as $fk) {
            echo "     * {$fk->constraint_name}: {$fk->definition}\n";
        }
        echo "\n";
        
        // 3. Проверяем таблицу employees
        echo "3. Таблица employees:\n";
        $employeesExists = DB::select("
            SELECT EXISTS (
                SELECT FROM information_schema.tables 
                WHERE table_name = 'employees'
            ) as exists
        ")[0]->exists;
        
        if ($employeesExists) {
            $employeeCount = DB::select("SELECT COUNT(*) as count FROM employees")[0]->count;
            echo "   - Существует: ДА\n";
            echo "   - Записей: {$employeeCount}\n";
            
            // Проверяем есть ли связи с employees
            $employeeKeys = DB::select("
                SELECT conname, conrelid::regclass as table_name
                FROM pg_constraint 
                WHERE contype = 'f' 
                AND confrelid = 'employees'::regclass
            ");
            
            if (!empty($employeeKeys)) {
                echo "   - Ссылаются на employees:\n";
                foreach ($employeeKeys as $key) {
                    echo "     * {$key->table_name} → employees\n";
                }
            }
        } else {
            echo "   - Существует: НЕТ\n";
        }
        
        echo "\n=== ДИАГНОСТИКА ЗАВЕРШЕНА ===\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Ничего не делаем, это диагностическая миграция
    }
};