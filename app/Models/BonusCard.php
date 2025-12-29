<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BonusCard extends Model
{
    use HasFactory;

    // Если первичный ключ не 'id', укажите его
    protected $primaryKey = 'IDBonusCard';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'Name',
        'RequiredSpendAmount',
        'EarntRantTable',
        'EarntRantTakeaway',
        'MaxSpendPercent',
        'TableCloseDiscountPercent'
    ];

    protected $casts = [
        'RequiredSpendAmount' => 'integer',
        'EarntRantTable' => 'integer',
        'EarntRantTakeaway' => 'integer',
        'MaxSpendPercent' => 'integer',
        'TableCloseDiscountPercent' => 'integer',
    ];

    public function clients()
    {
        return $this->hasMany(Client::class, 'bonus_card_id', 'IDBonusCard');
    }
}