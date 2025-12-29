<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItemRecipe extends Model
{
    use HasFactory;

    protected $primaryKey = 'IDSales';
    
    protected $table = 'order_item_recipes';
    
    protected $fillable = [
        'IDRecipes',
        'Quantity',
        'UnitPrice',
        'IDOrder'
    ];

    protected $casts = [
        'UnitPrice' => 'decimal:2',
    ];

    // Отношение к рецепту
    public function recipe()
    {
        return $this->belongsTo(Recipe::class, 'IDRecipes');
    }

    // Отношение к заказу
    public function order()
    {
        return $this->belongsTo(Order::class, 'IDOrder');
    }

    // Вычисляемое поле - общая стоимость позиции
    public function getTotalAttribute()
    {
        return $this->Quantity * $this->UnitPrice;
    }
}