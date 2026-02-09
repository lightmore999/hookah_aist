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
    
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function hookah()
    {
        return $this->belongsTo(Hookah::class);
    }
    
    /**
     * Аксессор для удобного получения времени создания
     */
    public function getCreatedAtFormattedAttribute()
    {
        return $this->created_at ? $this->created_at->format('d.m.Y H:i') : null;
    }
    
    /**
     * Аксессор для получения времени в формате "сколько времени назад"
     */
    public function getCreatedAtHumanAttribute()
    {
        return $this->created_at ? $this->created_at->diffForHumans() : null;
    }
}