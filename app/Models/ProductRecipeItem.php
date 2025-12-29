<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductRecipeItem extends Model
{
    protected $fillable = [
        'parent_product_id',
        'component_product_id',
        'quantity',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
    ];

    public function parentProduct()
    {
        return $this->belongsTo(Product::class, 'parent_product_id');
    }

    public function component()
    {
        return $this->belongsTo(Product::class, 'component_product_id');
    }

    public function getUnitAttribute()
    {
        return $this->component->unit;
    }

    public function getFormattedQuantityAttribute()
    {
        $quantity = number_format($this->quantity, 3);
        $quantity = rtrim($quantity, '0');
        $quantity = rtrim($quantity, '.');
        
        return $quantity . ' ' . $this->unit;
    }
}