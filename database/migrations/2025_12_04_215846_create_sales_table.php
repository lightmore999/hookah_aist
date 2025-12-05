<?php

use App\Models\Client;
use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Product::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Warehouse::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Client::class)->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('quantity');
            $table->enum('payment_method', ['cash', 'card']);
            $table->timestamp('sold_at')->useCurrent();
            $table->decimal('total', 12, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sales');
    }
};
