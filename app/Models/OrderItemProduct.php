<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItemProduct extends Model
{
    use HasFactory;

    // Если используете нестандартное имя первичного ключа
    protected $primaryKey = 'IDHookah';
    
    protected $table = 'order_item_products';
    
    protected $fillable = [
        'IDProduct',
        'Quantity',
        'UnitPrice',
        'IDOrder'
    ];

    protected $casts = [
        'UnitPrice' => 'decimal:2',
    ];

    // Отношение к продукту
    public function product()
    {
        return $this->belongsTo(Product::class, 'IDProduct');
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