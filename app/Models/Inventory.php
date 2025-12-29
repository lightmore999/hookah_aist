<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Inventory extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Статусы инвентаризации
     */
    const STATUS_CREATED = 'created';
    const STATUS_CLOSED = 'closed';

    /**
     * Атрибуты, которые можно массово назначать
     */
    protected $fillable = [
        'name',
        'status',
        'inventory_date',
        'warehouse_id',
        'created_by',
        'completed_by'
    ];

    /**
     * Атрибуты, которые должны быть преобразованы
     */
    protected $casts = [
        'inventory_date' => 'datetime',
    ];

    /**
     * Значения по умолчанию для атрибутов
     */
    protected $attributes = [
        'status' => self::STATUS_CREATED,
    ];

    /**
     * Обработчики событий модели
     */
    protected static function booted(): void
    {
        // Автоматическое заполнение имени и даты при создании
        static::creating(function (Inventory $inventory) {
            if (empty($inventory->name)) {
                $inventory->name = 'Инвентаризация от ' . now()->format('d.m.Y H:i');
            }
            
            if (empty($inventory->inventory_date)) {
                $inventory->inventory_date = now();
            }
            
            // Автоматически устанавливаем created_by если пользователь авторизован
            if (auth()->check() && empty($inventory->created_by)) {
                $inventory->created_by = auth()->id();
            }
        });

        // Автоматическое обновление остатков при закрытии инвентаризации
        static::updated(function (Inventory $inventory) {
            if ($inventory->isDirty('status') && $inventory->status === self::STATUS_CLOSED) {
                $inventory->applyStockAdjustments();
            }
        });
    }

    /**
     * Отношение к складу
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
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

    /**
     * Проверка, создана ли инвентаризация (можно редактировать)
     */
    public function isCreated(): bool
    {
        return $this->status === self::STATUS_CREATED;
    }

    /**
     * Проверка, закрыта ли инвентаризация (нельзя редактировать)
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
            $item->adjustStock();
        }
    }

    /**
     * Получить общую разницу по всем товарам
     */
    public function getTotalDifferenceAttribute(): int
    {
        return $this->items->sum('difference');
    }

    /**
     * Получить количество товаров в инвентаризации
     */
    public function getItemsCountAttribute(): int
    {
        return $this->items->count();
    }

    /**
     * Проверка, есть ли различия в инвентаризации
     */
    public function hasDifferences(): bool
    {
        return $this->items()->whereColumn('actual_quantity', '!=', 'system_quantity')->exists();
    }
}