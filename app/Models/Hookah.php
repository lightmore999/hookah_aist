<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Hookah extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'price',
        'cost',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'cost' => 'decimal:2',
    ];

    public function orderItems()
    {
        return $this->hasMany(OrderItemHookah::class, 'IDHookah');
    }

    // Новая связь для продаж
    public function sales()
    {
        return $this->belongsToMany(Sale::class, 'sale_hookahs')
                    ->withTimestamps();
    }
}