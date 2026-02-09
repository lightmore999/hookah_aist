<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShiftSalary extends Model
{
    use HasFactory;

    protected $table = 'shift_salaries';

    protected $fillable = [
        'user_id',
        'shift_id',
        'amount' // Сколько заработал в смене
    ];

    protected $casts = [
        'amount' => 'decimal:2'
    ];

    /**
     * Сотрудник
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Смена
     */
    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }
}