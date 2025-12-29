<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Просто удаляем констрейнт и создаем новый
        DB::statement('ALTER TABLE shifts DROP CONSTRAINT IF EXISTS shifts_status_check');
        DB::statement("ALTER TABLE shifts ADD CONSTRAINT shifts_status_check CHECK (status IN ('planned', 'open', 'closed'))");
    }

    public function down()
    {
        DB::statement('ALTER TABLE shifts DROP CONSTRAINT IF EXISTS shifts_status_check');
        DB::statement("ALTER TABLE shifts ADD CONSTRAINT shifts_status_check CHECK (status IN ('open', 'closed'))");
    }
};