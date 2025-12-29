<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'comment',
        'birth_date',
        'bonus_card_id',
        'bonus_points',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'bonus_points' => 'integer',
    ];

    /**
     * Получить бонусную карту клиента
     */
    public function bonusCard()
    {
        return $this->belongsTo(BonusCard::class, 'bonus_card_id', 'IDBonusCard');
    }

    /**
     * Получить заказы клиента
     */
    public function orders()
    {
        return $this->hasMany(Order::class, 'IDClient');
    }
}