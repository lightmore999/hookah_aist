<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecipeItem extends Model
{
    protected $table = 'recipe_items';

    protected $fillable = [
        'recipe_id',
        'product_id',
        'quantity',
    ];

    /**
     * Рецепт
     */
    public function recipe()
    {
        return $this->belongsTo(Recipe::class);
    }

    /**
     * Продукт
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Получить стоимость позиции с учетом упаковки
     */
    public function getItemCostAttribute()
    {
        if ($this->product && $this->product->packaging > 0) {
            // Цена за 1 единицу = цена упаковки / размер упаковки
            $pricePerUnit = $this->product->price / $this->product->packaging;
            return $pricePerUnit * $this->quantity;
        }
        return 0;
    }

    /**
     * Получить форматированную стоимость
     */
    public function getFormattedItemCostAttribute()
    {
        return number_format($this->item_cost, 2) . ' ₽';
    }

    /**
     * Получить цену за единицу продукта
     */
    public function getProductPricePerUnitAttribute()
    {
        if ($this->product && $this->product->packaging > 0) {
            return $this->product->price / $this->product->packaging;
        }
        return 0;
    }

    /**
     * Обновить стоимость рецепта при изменении
     */
    protected static function booted()
    {
        static::saved(function ($item) {
            $item->recipe->calculateCost();
        });

        static::deleted(function ($item) {
            $item->recipe->calculateCost();
        });
    }
}