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
     * Пользователь (бывший employee)
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}