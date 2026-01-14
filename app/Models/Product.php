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
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'cost' => 'decimal:2',
    ];

    public function category()
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id');
    }

    // УДАЛИТЕ или ЗАКОММЕНТИРУЙТЕ эту связь, если она не используется
    // public function recipeItems()
    // {
    //     return $this->hasMany(RecipeItem::class);
    // }

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