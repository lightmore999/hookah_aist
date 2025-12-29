<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryItem extends Model
{
    use HasFactory;

    /**
     * Атрибуты, которые можно массово назначать
     */
    protected $fillable = [
        'inventory_id',
        'product_id',
        'system_quantity',
        'actual_quantity'
    ];

    /**
     * Отношение к инвентаризации
     */
    public function inventory(): BelongsTo
    {
        return $this->belongsTo(Inventory::class);
    }

    /**
     * Отношение к товару
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Получить разницу между фактическим и системным количеством
     */
    public function getDifferenceAttribute(): int
    {
        return $this->actual_quantity - $this->system_quantity;
    }

    /**
     * Проверка, есть ли разница в количестве
     */
    public function hasDifference(): bool
    {
        return $this->actual_quantity !== $this->system_quantity;
    }

    /**
     * Корректировать остатки на складе
     */
    public function adjustStock(): void
    {
        if ($this->hasDifference()) {
            $stock = Stock::where('warehouse_id', $this->inventory->warehouse_id)
                ->where('product_id', $this->product_id)
                ->first();
            
            if ($stock) {
                $stock->quantity = $this->actual_quantity;
                $stock->save();
            }
        }
    }

    /**
     * Scope для фильтрации товаров с различиями
     */
    public function scopeWithDifferences($query)
    {
        return $query->whereColumn('actual_quantity', '!=', 'system_quantity');
    }
}