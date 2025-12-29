<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShiftUser extends Model
{
    use HasFactory;

    protected $table = 'shift_user';

    protected $fillable = [
        'shift_id',
        'user_id',
    ];

    /**
     * Смена
     */
    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    /**
     * Сотрудник
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'user_id');
    }
}