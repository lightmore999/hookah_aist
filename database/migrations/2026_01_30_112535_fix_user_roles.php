<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Меняем всех 'user' на 'employee'
        DB::table('users')->where('role', 'user')->update(['role' => 'admin']);
    }

    public function down()
    {
        // Откат (меняем обратно на 'user')
        DB::table('users')->where('role', 'admin')->update(['role' => 'user']);
    }
};