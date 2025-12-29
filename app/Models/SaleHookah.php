<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaleHookah extends Model
{
    use HasFactory;

    protected $table = 'sale_hookahs';

    protected $fillable = [
        'sale_id',
        'hookah_id'
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function hookah()
    {
        return $this->belongsTo(Hookah::class);
    }
}