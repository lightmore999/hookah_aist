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
        'hookah_maker_rate',
        'administrator_rate',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'cost' => 'decimal:2',
        'hookah_maker_rate' => 'decimal:2',
        'administrator_rate' => 'decimal:2',
    ];
}
