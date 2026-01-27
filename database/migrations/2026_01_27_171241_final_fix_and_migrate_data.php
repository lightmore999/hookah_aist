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
        echo "=== ФИНАЛЬНОЕ ИСПРАВЛЕНИЕ И ПЕРЕНОС ДАННЫХ ===\n\n";
        
        // 0. Проверяем текущие данные
        echo "0. Текущие данные:\n";
        echo "   - users: " . DB::table('users')->count() . " записей\n";
        echo "   - employees: " . DB::table('employees')->count() . " записей\n";
        echo "   - shift_user: " . DB::table('shift_user')->count() . " записей\n\n";
        
        // 1. СНАЧАЛА переносим данные из employees в users
        echo "1. Переносим данные из employees в users:\n";
        
        // Для каждого сотрудника создаем/обновляем пользователя
        $employees = DB::table('employees')->get();
        $created = 0;
        $updated = 0;
        
        foreach ($employees as $employee) {
            // Проверяем, есть ли пользователь с таким email
            $existingUser = DB::table('users')->where('email', $employee->email)->first();
            
            if ($existingUser) {
                // Обновляем существующего пользователя
                DB::table('users')->where('id', $existingUser->id)->update([
                    'role' => 'employee',
                    'position' => $employee->position,
                    'social_network' => $employee->social_network,
                    'phone' => $employee->phone,
                    'notes' => $employee->notes,
                    'shift_salary' => $employee->shift_salary,
                    'revenue_percentage' => $employee->revenue_percentage,
                    'inn' => $employee->inn,
                    'tips_link' => $employee->tips_link,
                    'deleted_at' => $employee->deleted_at ?? null,
                    'updated_at' => now(),
                ]);
                $updated++;
                echo "   - Обновлен: {$employee->name} ({$employee->email})\n";
            } else {
                // Создаем нового пользователя
                $userData = [
                    'name' => $employee->name,
                    'email' => $employee->email,
                    'password' => $employee->password,
                    'role' => 'employee',
                    'position' => $employee->position,
                    'social_network' => $employee->social_network,
                    'phone' => $employee->phone,
                    'notes' => $employee->notes,
                    'shift_salary' => $employee->shift_salary,
                    'revenue_percentage' => $employee->revenue_percentage,
                    'inn' => $employee->inn,
                    'tips_link' => $employee->tips_link,
                    'deleted_at' => $employee->deleted_at ?? null,
                    'created_at' => $employee->created_at ?? now(),
                    'updated_at' => $employee->updated_at ?? now(),
                ];
                
                // Добавляем только если поля существуют в employees
                if (property_exists($employee, 'email_verified_at')) {
                    $userData['email_verified_at'] = $employee->email_verified_at;
                }
                
                if (property_exists($employee, 'remember_token')) {
                    $userData['remember_token'] = $employee->remember_token;
                }
                
                DB::table('users')->insert($userData);
                $created++;
                echo "   - Создан: {$employee->name} ({$employee->email})\n";
            }
        }
        
        echo "   Итого: создано {$created}, обновлено {$updated}\n\n";
        
        // 2. Теперь исправляем внешний ключ в shift_user
        echo "2. Исправляем внешний ключ в shift_user:\n";
        
        // А) Создаем временную таблицу для маппинга employee.id → user.id
        echo "   - Создаем маппинг employee_id → user_id...\n";
        $mapping = [];
        
        $employeesWithUsers = DB::select("
            SELECT e.id as employee_id, u.id as user_id
            FROM employees e
            JOIN users u ON e.email = u.email OR e.name = u.name
        ");
        
        foreach ($employeesWithUsers as $map) {
            $mapping[$map->employee_id] = $map->user_id;
        }
        
        echo "   - Найдено соответствий: " . count($mapping) . "\n";
        
        // Если нет соответствий по email, пробуем по name
        if (count($mapping) < count($employees)) {
            echo "   - Ищем дополнительные соответствия по имени...\n";
            
            foreach ($employees as $employee) {
                if (!isset($mapping[$employee->id])) {
                    $user = DB::table('users')
                        ->where('name', $employee->name)
                        ->where('role', 'employee')
                        ->first();
                    
                    if ($user) {
                        $mapping[$employee->id] = $user->id;
                        echo "     Найдено по имени: {$employee->name} → user_id: {$user->id}\n";
                    }
                }
            }
        }
        
        // Б) Обновляем записи в shift_user
        echo "   - Обновляем shift_user...\n";
        $updatedShifts = 0;
        $deletedShifts = 0;
        
        $shiftUsers = DB::table('shift_user')->get();
        
        foreach ($shiftUsers as $shiftUser) {
            if (isset($mapping[$shiftUser->user_id])) {
                // Обновляем user_id на новый ID из users
                DB::table('shift_user')
                    ->where('id', $shiftUser->id)
                    ->update(['user_id' => $mapping[$shiftUser->user_id]]);
                $updatedShifts++;
            } else {
                // Удаляем запись, если сотрудник не найден в users
                DB::table('shift_user')->where('id', $shiftUser->id)->delete();
                $deletedShifts++;
            }
        }
        
        echo "   - Обновлено записей: {$updatedShifts}\n";
        echo "   - Удалено записей (нет соответствия): {$deletedShifts}\n";
        
        // В) Удаляем старый внешний ключ
        echo "   - Удаляем старый внешний ключ...\n";
        DB::statement('ALTER TABLE shift_user DROP CONSTRAINT IF EXISTS shift_user_user_id_foreign');
        
        // Г) Добавляем новый внешний ключ
        echo "   - Добавляем новый внешний ключ user_id → users.id...\n";
        DB::statement('
            ALTER TABLE shift_user 
            ADD CONSTRAINT shift_user_user_id_foreign 
            FOREIGN KEY (user_id) REFERENCES users(id) 
            ON DELETE CASCADE
        ');
        
        // 3. Проверяем/исправляем fines
        echo "\n3. Проверяем таблицу fines:\n";
        
        if (Schema::hasTable('fines') && Schema::hasColumn('fines', 'user_id')) {
            // Обновляем user_id в fines по тому же принципу
            $fines = DB::table('fines')->get();
            $updatedFines = 0;
            
            foreach ($fines as $fine) {
                if (isset($mapping[$fine->user_id])) {
                    DB::table('fines')
                        ->where('id', $fine->id)
                        ->update(['user_id' => $mapping[$fine->user_id]]);
                    $updatedFines++;
                }
            }
            
            echo "   - Обновлено штрафов: {$updatedFines}\n";
            
            // Убеждаемся, что есть внешний ключ на users
            $hasKey = DB::select("
                SELECT 1 
                FROM pg_constraint 
                WHERE conname = 'fines_user_id_foreign' 
                AND conrelid = 'fines'::regclass
            ");
            
            if (empty($hasKey)) {
                DB::statement('
                    ALTER TABLE fines 
                    ADD CONSTRAINT fines_user_id_foreign 
                    FOREIGN KEY (user_id) REFERENCES users(id) 
                    ON DELETE CASCADE
                ');
                echo "   - Добавлен внешний ключ\n";
            }
        }
        
        // 4. Устанавливаем роль для существующих пользователей (не сотрудников)
        echo "\n4. Устанавливаем роли пользователей:\n";
        
        $adminCount = DB::table('users')
            ->where('role', '!=', 'employee')
            ->update(['role' => 'admin']);
        
        echo "   - Назначена роль 'admin' для {$adminCount} пользователей\n";
        
        echo "\n✅ ВСЁ ГОТОВО!\n";
        echo "   - Данные перенесены из employees в users\n";
        echo "   - Внешние ключи исправлены\n";
        echo "   - Связи обновлены\n";
        echo "   - Теперь можно удалить таблицу employees\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        echo "=== ОТКАТ МИГРАЦИИ ===\n";
        echo "Внимание: полный откат сложен. Рекомендуется использовать бэкап.\n";
        
        // 1. Восстанавливаем внешний ключ shift_user на employees
        DB::statement('ALTER TABLE shift_user DROP CONSTRAINT IF EXISTS shift_user_user_id_foreign');
        
        if (Schema::hasTable('employees')) {
            DB::statement('
                ALTER TABLE shift_user 
                ADD CONSTRAINT shift_user_user_id_foreign 
                FOREIGN KEY (user_id) REFERENCES employees(id) 
                ON DELETE CASCADE
            ');
            echo "Восстановлен ключ на employees\n";
        }
        
        // 2. Частично очищаем users (удаляем добавленных сотрудников)
        // Это сложно, т.к. мы смешали данные
        echo "Данные в users остаются (нужен бэкап для полного отката)\n";
    }
};