<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItemHookah extends Model
{
    use HasFactory;

    protected $primaryKey = 'IDHookahOrderItem';
    
    protected $table = 'order_item_hookahs';
    
    protected $fillable = [
        'IDHookah',
        'IDOrder'
    ];

    // Отношение к кальяну
    public function hookah()
    {
        return $this->belongsTo(Hookah::class, 'IDHookah');
    }

    // Отношение к заказу
    public function order()
    {
        return $this->belongsTo(Order::class, 'IDOrder');
    }

    // Вычисляемое поле - цена кальяна
    public function getUnitPriceAttribute()
    {
        return $this->hookah->price ?? 0;
    }

    // Вычисляемое поле - общая стоимость (всегда 1 шт)
    public function getTotalAttribute()
    {
        return $this->hookah->price ?? 0;
    }
}