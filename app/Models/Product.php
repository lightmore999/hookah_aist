<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'name',
        'product_category_id',
        'price',
        'cost',
        'unit',
        'barcode',
        'article_number',
        'popularity',      // Добавляем новое поле
        'is_active',       // Добавляем новое поле
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'cost' => 'decimal:2',
        'popularity' => 'integer',     // Добавляем кастинг
        'is_active' => 'boolean',      // Добавляем кастинг
    ];

    protected $attributes = [
        'popularity' => 0,    // Значение по умолчанию
        'is_active' => true,  // Значение по умолчанию
    ];

    // Остальные методы остаются без изменений...
    public function category()
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id');
    }

    public function stocks()
    {
        return $this->hasMany(Stock::class);
    }

    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }

    public function recipeComponents()
    {
        return $this->hasMany(ProductRecipeItem::class, 'parent_product_id');
    }

    public function usedInRecipes()
    {
        return $this->hasMany(ProductRecipeItem::class, 'component_product_id');
    }

    public function getIsCompositeAttribute()
    {
        return $this->recipeComponents()->exists();
    }

    public function getIsComponentAttribute()
    {
        return $this->usedInRecipes()->exists();
    }

    public function getComponentsTotalCostAttribute()
    {
        if (!$this->is_composite) {
            return 0;
        }
        
        $total = 0;
        foreach ($this->recipeComponents as $component) {
            if ($component->component) {
                $total += $component->component->cost * $component->quantity;
            }
        }
        
        return $total;
    }

    public function getCalculatedCostAttribute()
    {
        if ($this->is_composite && $this->recipeComponents()->exists()) {
            return $this->components_total_cost;
        }
        
        return $this->cost;
    }

    public function getTotalStockAttribute()
    {
        return $this->stocks()->sum('quantity');
    }

    public function getIsPieceAttribute()
    {
        return $this->unit === 'шт';
    }

    public function getIsWeightOrVolumeAttribute()
    {
        return in_array($this->unit, ['г', 'мл', 'кг', 'л']);
    }

    // Новый scope для популярных товаров
    public function scopePopular($query, $limit = 10)
    {
        return $query->where('is_active', true)
                     ->orderBy('popularity', 'desc')
                     ->orderBy('name')
                     ->limit($limit);
    }

    // Новый scope для активных товаров
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Новый scope для неактивных товаров
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    // Метод для увеличения популярности
    public function incrementPopularity($amount = 1)
    {
        $this->increment('popularity', $amount);
        return $this;
    }

    // Метод для уменьшения популярности
    public function decrementPopularity($amount = 1)
    {
        $this->decrement('popularity', $amount);
        return $this;
    }

    // Метод для сброса популярности
    public function resetPopularity()
    {
        $this->update(['popularity' => 0]);
        return $this;
    }

    // Метод для активации товара
    public function activate()
    {
        $this->update(['is_active' => true]);
        return $this;
    }

    // Метод для деактивации товара
    public function deactivate()
    {
        $this->update(['is_active' => false]);
        return $this;
    }

    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('product_category_id', $categoryId);
    }

    public function scopePieces($query)
    {
        return $query->where('unit', 'шт');
    }

    public function scopeWeightVolume($query)
    {
        return $query->whereIn('unit', ['г', 'мл', 'кг', 'л']);
    }

    public static function calculateCost($quantity, $unit, $cost)
    {
        $totalCost = $cost * $quantity;
        return round($totalCost, 2);
    }

    public function getPriceForUnit($targetUnit = null)
    {
        if (!$targetUnit || $targetUnit === $this->unit) {
            return $this->price;
        }
        
        return $this->price;
    }
}