<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    use HasFactory;

    protected $table = 'stock';

    protected $fillable = [
        'warehouse_id',
        'product_id',
        'quantity',        // Количество в базовых единицах (мл, г, шт)
        'last_updated',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'last_updated' => 'datetime',
    ];

    /**
     * Автоматическое обновление last_updated при изменении
     */
    protected static function booted()
    {
        static::saving(function ($stock) {
            $stock->last_updated = now();
        });
    }

    /**
     * Склад
     */
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Продукт
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Использовать продукт (списать количество)
     */
    public function useQuantity($neededQuantity)
    {
        $product = $this->product;
        $unit = $product->unit ?? 'шт';
        
        if ($this->quantity < $neededQuantity) {
            return [
                'success' => false,
                'message' => "Недостаточно товара. Нужно: {$neededQuantity} {$unit}, есть: {$this->quantity} {$unit}",
            ];
        }

        $this->quantity -= $neededQuantity;
        $this->save();

        return [
            'success' => true,
            'message' => "Использовано: {$neededQuantity} {$unit}",
            'new_quantity' => $this->quantity,
        ];
    }

    /**
     * Добавить количество
     */
    public function addQuantity($quantity)
    {
        $this->quantity += $quantity;
        $this->save();

        return [
            'success' => true,
            'message' => "Добавлено: {$quantity}",
            'new_quantity' => $this->quantity,
        ];
    }

    /**
     * Получить форматированную информацию
     */
    public function getStockInfoAttribute()
    {
        $product = $this->product;
        $unit = $product->unit ?? 'шт';

        return [
            'quantity' => $this->quantity,
            'unit' => $unit,
            'formatted' => "{$this->quantity} {$unit}",
        ];
    }

    /**
     * Проверить, достаточно ли продукта
     */
    public function hasEnough($neededQuantity)
    {
        return $this->quantity >= $neededQuantity;
    }

    /**
     * Accessor для quantity (гарантируем тип)
     */
    public function getQuantityAttribute($value)
    {
        return (float) $value;
    }

    /**
     * Mutator для quantity (округление)
     */
    public function setQuantityAttribute($value)
    {
        $this->attributes['quantity'] = round($value, 3);
    }
}