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
        Schema::table('hookahs', function (Blueprint $table) {
            $table->dropColumn(['hookah_maker_rate', 'administrator_rate']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hookahs', function (Blueprint $table) {
            $table->decimal('hookah_maker_rate', 10, 2)->nullable();
            $table->decimal('administrator_rate', 10, 2)->nullable();
        });
    }
};