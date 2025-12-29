<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'warehouse_id',
        'quantity',
        'unit_price',
        'purchase_date',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'purchase_date' => 'datetime',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Принять закупку на склад (просто добавляет в остатки)
     */
    public function addToStock()
    {
        // Находим или создаем запись на складе
        $stock = Stock::firstOrCreate(
            [
                'warehouse_id' => $this->warehouse_id,
                'product_id' => $this->product_id,
            ],
            [
                'quantity' => 0,
            ]
        );

        // Добавляем количество
        return $stock->addQuantity($this->quantity);
    }

    /**
     * Получить количество в упаковках
     */
    public function getQuantityInPackagesAttribute()
    {
        $product = $this->product;
        $packaging = $product->packaging ?? 1;
        return $packaging > 0 ? $this->quantity / $packaging : 0;
    }

    /**
     * Получить цену за упаковку
     */
    public function getUnitPricePerPackageAttribute()
    {
        $product = $this->product;
        $packaging = $product->packaging ?? 1;
        return $this->unit_price * $packaging;
    }

    /**
     * Автоматический расчет итоговой цены
     */
    public function getTotalPriceAttribute()
    {
        return $this->quantity * $this->unit_price;
    }

    /**
     * Получить информацию о единице измерения
     */
    public function getUnitInfoAttribute()
    {
        return $this->product ? $this->product->unit : 'шт';
    }

    /**
     * Получить форматированную информацию о количестве
     */
    public function getFormattedQuantityAttribute()
    {
        $unit = $this->unit_info;
        return "{$this->quantity} {$unit}";
    }
}