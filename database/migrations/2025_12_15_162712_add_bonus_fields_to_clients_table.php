<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->foreignId('bonus_card_id')->nullable()->constrained('bonus_cards', 'IDBonusCard')->nullOnDelete();
            $table->integer('bonus_points')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropForeign(['bonus_card_id']);
            $table->dropColumn(['bonus_card_id', 'bonus_points']);
        });
    }
};