<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'product_category_id',
        'price',
        'cost',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'cost' => 'decimal:2',
    ];
    
    public function category()
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id');
    }
}
