<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Inventory extends Model
{
    use HasFactory, SoftDeletes;

    const STATUS_CREATED = 'created';
    const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'name',
        'status',
        'inventory_date',
        'created_by',
        'completed_by'
        // Убрали warehouse_id
    ];  

    protected $casts = [
        'inventory_date' => 'datetime',
    ];

    protected $attributes = [
        'status' => self::STATUS_CREATED,
    ];

    protected static function booted(): void
    {
        static::creating(function (Inventory $inventory) {
            if (empty($inventory->name)) {
                $inventory->name = 'Инвентаризация от ' . now()->format('d.m.Y H:i');
            }
            
            if (empty($inventory->inventory_date)) {
                $inventory->inventory_date = now();
            }
            
            if (auth()->check() && empty($inventory->created_by)) {
                $inventory->created_by = auth()->id();
            }
        });

        static::updated(function (Inventory $inventory) {
            if ($inventory->isDirty('status') && $inventory->status === self::STATUS_CLOSED) {
                $inventory->applyStockAdjustments();
            }
        });
    }

    // =========== ОТНОШЕНИЯ ===========

    /**
     * Все склады, участвующие в инвентаризации
     */
    public function warehouses(): BelongsToMany
    {
        return $this->belongsToMany(Warehouse::class, 'inventory_warehouse')
                    ->withTimestamps();
    }

    /**
     * Отношение к создателю
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Отношение к пользователю, который закрыл инвентаризацию
     */
    public function completer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    /**
     * Отношение к товарам инвентаризации
     */
    public function items(): HasMany
    {
        return $this->hasMany(InventoryItem::class);
    }

    // =========== МЕТОДЫ ===========

    /**
     * Привязать несколько складов к инвентаризации
     */
    public function attachWarehouses(array $warehouseIds): void
    {
        $this->warehouses()->sync($warehouseIds);
    }

    /**
     * Получить первый склад (для обратной совместимости)
     */
    public function getFirstWarehouseAttribute()
    {
        return $this->warehouses->first();
    }

    /**
     * Проверка, создана ли инвентаризация
     */
    public function isCreated(): bool
    {
        return $this->status === self::STATUS_CREATED;
    }

    /**
     * Проверка, закрыта ли инвентаризация
     */
    public function isClosed(): bool
    {
        return $this->status === self::STATUS_CLOSED;
    }

    /**
     * Закрыть инвентаризацию
     */
    public function close(): bool
    {
        if ($this->isCreated() && auth()->check()) {
            $this->status = self::STATUS_CLOSED;
            $this->completed_by = auth()->id();
            return $this->save();
        }
        
        return false;
    }

    /**
     * Применить корректировки остатков
     */
    public function applyStockAdjustments(): void
    {
        foreach ($this->items as $item) {
            if ($item->hasDifference()) {
                // Для каждого склада в инвентаризации обновляем остатки
                foreach ($this->warehouses as $warehouse) {
                    $stock = Stock::where('warehouse_id', $warehouse->id)
                        ->where('product_id', $item->product_id)
                        ->first();
                    
                    if ($stock) {
                        // Обновляем количество на складе
                        $stock->quantity = $item->actual_quantity;
                        $stock->save();
                    }
                }
            }
        }
    }

    /**
     * Scope для фильтрации по складу
     */
    public function scopeFilterByWarehouse($query, $warehouseId)
    {
        return $query->whereHas('warehouses', function ($q) use ($warehouseId) {
            $q->where('warehouses.id', $warehouseId);
        });
    }

    /**
     * Получить общую разницу по всем товарам
     */
    public function getTotalDifferenceAttribute(): int
    {
        return $this->items->sum('difference');
    }

    /**
     * Получить количество товаров
     */
    public function getItemsCountAttribute(): int
    {
        return $this->items->count();
    }

    /**
     * Проверка, есть ли различия
     */
    public function hasDifferences(): bool
    {
        return $this->items()->whereColumn('actual_quantity', '!=', 'system_quantity')->exists();
    }
}