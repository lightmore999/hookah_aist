<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BonusCard extends Model
{
    use HasFactory;

    protected $primaryKey = 'IDBonusCard';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'Name',
        'RequiredSpendAmount',
        'MaxSpendPercent',
        'BonusPercent'
    ];

    protected $casts = [
        'RequiredSpendAmount' => 'integer',
        'MaxSpendPercent' => 'integer',
        'BonusPercent' => 'integer',
    ];

    public function clients()
    {
        return $this->hasMany(Client::class, 'bonus_card_id', 'IDBonusCard');
    }
}