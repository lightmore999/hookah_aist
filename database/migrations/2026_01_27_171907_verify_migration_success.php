<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        echo "=== ПРОВЕРКА РЕЗУЛЬТАТОВ МИГРАЦИИ ===\n\n";
        
        // 1. Проверяем users
        echo "1. Таблица users:\n";
        $userCount = DB::table('users')->count();
        echo "   - Всего записей: {$userCount}\n";
        
        $employeeCount = DB::table('users')->where('role', 'employee')->count();
        echo "   - Сотрудников (role='employee'): {$employeeCount}\n";
        
        $adminCount = DB::table('users')->where('role', 'admin')->count();
        echo "   - Админов (role='admin'): {$adminCount}\n";
        
        $nullRoleCount = DB::table('users')->whereNull('role')->orWhere('role', '')->count();
        echo "   - Без роли: {$nullRoleCount}\n\n";
        
        // 2. Проверяем внешние ключи shift_user
        echo "2. Таблица shift_user:\n";
        $shiftUserCount = DB::table('shift_user')->count();
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
            
            // Проверяем, куда ссылается user_id
            if (strpos($fk->definition, 'user_id') !== false) {
                if (strpos($fk->definition, 'users(id)') !== false) {
                    echo "       ✓ ПРАВИЛЬНО: ссылается на users\n";
                } else {
                    echo "       ✗ ОШИБКА: должен ссылаться на users\n";
                }
            }
        }
        
        // 3. Проверяем целостность данных
        echo "\n3. Проверка целостности данных:\n";
        
        // Проверяем, нет ли в shift_user записей с user_id, которых нет в users
        $orphaned = DB::select("
            SELECT COUNT(*) as count
            FROM shift_user su
            LEFT JOIN users u ON su.user_id = u.id
            WHERE u.id IS NULL
        ")[0]->count;
        
        if ($orphaned > 0) {
            echo "   ✗ Найдено 'сиротских' записей в shift_user: {$orphaned}\n";
        } else {
            echo "   ✓ Все записи в shift_user ссылаются на существующих users\n";
        }
        
        // 4. Проверяем таблицу employees (должна быть пустой или можно удалить)
        echo "\n4. Таблица employees:\n";
        if (Schema::hasTable('employees')) {
            $employeeCount = DB::table('employees')->count();
            echo "   - Существует\n";
            echo "   - Записей осталось: {$employeeCount}\n";
            
            if ($employeeCount > 0) {
                echo "   ⚠ Внимание: В таблице employees еще есть данные\n";
            } else {
                echo "   ✓ Таблица employees пуста, можно удалить\n";
            }
        } else {
            echo "   ✓ Таблица employees удалена\n";
        }
        
        // 5. Проверяем fines
        echo "\n5. Таблица fines:\n";
        if (Schema::hasTable('fines')) {
            $finesCount = DB::table('fines')->count();
            echo "   - Записей: {$finesCount}\n";
            
            // Проверяем внешний ключ
            $finesKeys = DB::select("
                SELECT 
                    conname as constraint_name,
                    confrelid::regclass as references_table
                FROM pg_constraint 
                WHERE contype = 'f' 
                AND conrelid = 'fines'::regclass
                AND conkey::text LIKE '%user_id%'
            ");
            
            if (!empty($finesKeys)) {
                foreach ($finesKeys as $key) {
                    echo "   - Внешний ключ: {$key->constraint_name} → {$key->references_table}\n";
                    if ($key->references_table === 'users') {
                        echo "     ✓ ПРАВИЛЬНО: ссылается на users\n";
                    }
                }
            } else {
                echo "   ⚠ Нет внешнего ключа на user_id\n";
            }
        }
        
        echo "\n=== ПРОВЕРКА ЗАВЕРШЕНА ===\n";
        
        if ($orphaned === 0 && !empty($foreignKeys)) {
            echo "\n✅ ВСЁ ОТЛИЧНО! Можно переходить к обновлению моделей.\n";
        } else {
            echo "\n⚠ Есть проблемы, которые нужно исправить перед обновлением моделей.\n";
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Ничего не делаем, это проверочная миграция
    }
};